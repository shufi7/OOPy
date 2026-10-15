"""Private authenticated grader gateway. No learner Python is executed here.

Run on a dedicated Linux sandbox host with Docker. Do not expose the Docker
socket or this endpoint publicly. Requires only Python's standard library.
"""
import hmac
import json
import os
import re
import subprocess
import threading
import uuid
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer

TOKEN = os.environ.get('CODING_GRADER_TOKEN', '')
IMAGE = os.environ.get('CODING_GRADER_IMAGE', 'oopy-coding-sandbox:1')
RUNTIME = os.environ.get('CODING_GRADER_RUNTIME', '')
SLOTS = threading.BoundedSemaphore(2)


def run_sandbox(payload):
    name = 'oopy-grade-' + uuid.uuid4().hex
    command = [
        'docker', 'run', '--rm', '--pull=never', '--name', name, '-i',
        '--network=none', '--read-only', '--cap-drop=ALL',
        '--security-opt=no-new-privileges', '--user=65534:65534',
        '--memory=128m', '--memory-swap=128m', '--cpus=0.5',
        '--pids-limit=16', '--ulimit=cpu=3:3', '--ulimit=nofile=32:32',
        '--ulimit=fsize=0:0', '--ulimit=core=0:0',
        '--tmpfs=/tmp:rw,noexec,nosuid,nodev,size=8m',
    ]
    if RUNTIME:
        command += ['--runtime', RUNTIME]
    command.append(IMAGE)
    try:
        process = subprocess.run(command, input=json.dumps(payload), text=True,
                                 capture_output=True, timeout=8, check=False)
        if process.returncode == 125:
            raise RuntimeError('Docker sandbox unavailable')
        if process.returncode != 0:
            return error_report(payload['exercise_key'], 'Program berhenti atau melebihi batas sumber daya.')
        if len(process.stdout) > 50000:
            raise RuntimeError('Invalid sandbox response')
        report = json.loads(process.stdout)
        if report.get('exercise_key') != payload['exercise_key'] or report.get('version') != 1:
            raise RuntimeError('Invalid sandbox response')
        return report
    except subprocess.TimeoutExpired:
        return error_report(payload['exercise_key'], 'Batas waktu eksekusi Python terlampaui.')
    finally:
        # Killing the CLI alone can leave a container running. Always remove it.
        try:
            subprocess.run(['docker', 'rm', '-f', name], capture_output=True, timeout=2, check=False)
        except (OSError, subprocess.TimeoutExpired):
            pass


def error_report(key, message):
    return {'version': 1, 'exercise_key': key, 'status': 'error', 'checks': [], 'output': message}


def valid_payload(payload):
    return (isinstance(payload, dict) and set(payload) == {'exercise_key', 'files'}
            and payload['exercise_key'] in ('coding-latihan-1', 'coding-latihan-2')
            and isinstance(payload['files'], dict) and set(payload['files']) == {'main.py'}
            and isinstance(payload['files']['main.py'], str)
            and 0 < len(payload['files']['main.py']) <= 30000
            and len(payload['files']['main.py'].encode('utf-8')) <= 60000)


class Handler(BaseHTTPRequestHandler):
    # Bound read time and body size; never log source, authorization, or hidden tests.
    def setup(self):
        super().setup()
        self.connection.settimeout(5)

    def reply(self, status, body):
        data = json.dumps(body).encode()
        self.send_response(status)
        self.send_header('Content-Type', 'application/json')
        self.send_header('Content-Length', str(len(data)))
        self.end_headers()
        self.wfile.write(data)

    def do_POST(self):
        if self.path != '/grade':
            return self.reply(404, {'message': 'Not found'})
        authorization = self.headers.get('Authorization', '')
        if not hmac.compare_digest(authorization.encode(), ('Bearer ' + TOKEN).encode()):
            return self.reply(401, {'message': 'Unauthorized'})
        length = self.headers.get('Content-Length', '')
        if not re.fullmatch(r'[0-9]{1,7}', length) or not 0 < int(length) <= 200000:
            return self.reply(413, {'message': 'Invalid payload size'})
        try:
            payload = json.loads(self.rfile.read(int(length)))
            if not valid_payload(payload):
                return self.reply(422, {'message': 'Invalid exercise or files'})
        except (ValueError, TypeError, UnicodeError):
            return self.reply(422, {'message': 'Invalid JSON'})
        if not SLOTS.acquire(blocking=False):
            return self.reply(503, {'message': 'Sandbox busy'})
        try:
            report = run_sandbox(payload)
            return self.reply(200, report)
        except (OSError, ValueError, RuntimeError):
            return self.reply(503, {'message': 'Sandbox unavailable'})
        finally:
            SLOTS.release()


if __name__ == '__main__':
    if len(TOKEN) < 32:
        raise SystemExit('Set CODING_GRADER_TOKEN to a shared random secret of at least 32 characters.')
    address = os.environ.get('CODING_GRADER_BIND', '127.0.0.1')
    port = int(os.environ.get('CODING_GRADER_PORT', '8020'))
    ThreadingHTTPServer((address, port), Handler).serve_forever()
