"""Only fixed, trusted test fixtures execute in these unit tests. No submitted code.
Docker integration tests are opt-in: OOPY_TEST_DOCKER=1 python -m unittest ...
"""
import os
import io
import contextlib
import json
import http.client
from pathlib import Path
import subprocess
import sys
import threading
import time
import unittest
from unittest.mock import patch

sys.path.insert(0, str(Path(__file__).parent))
import sandbox
import server

SENSOR = '''class SensorAir:
    def __init__(self, lokasi, ph):
        self.lokasi = lokasi
        self.ph = ph
    def status_air(self):
        return "Normal" if 6.5 <= self.ph <= 8.5 else "Perlu pemeriksaan"
sensor = SensorAir("Rawa Bangkau", 7)
print(sensor.lokasi)
print("Status air:", sensor.status_air())
'''
ECOSYSTEM = '''class Ekosistem:
    def __init__(self, nama, lokasi):
        self.nama = nama
        self.lokasi = lokasi
    def status(self):
        return "Status belum ditentukan"
class Rawa(Ekosistem):
    def status(self):
        return "Genangan rawa dipantau"
rawa = Rawa("Rawa Bangkau", "Kalimantan Selatan")
print(rawa.status())
'''


def payload(source=SENSOR, key='coding-latihan-1'):
    return {'exercise_key': key, 'files': {'main.py': source}}


def wrap_program(solution, marker):
    classes, program = solution.split(marker, 1)
    return classes + 'def main():\n' + '\n'.join('    ' + line for line in (marker + program).splitlines()) + '\nmain()'


class GraderTest(unittest.TestCase):
    def passed(self, report):
        return report['status'] == 'graded' and bool(report['checks']) and all(item['passed'] for item in report['checks'] if item['required'])

    def test_solutions_and_super_constructor(self):
        self.assertTrue(self.passed(sandbox.grade(payload())))
        self.assertTrue(self.passed(sandbox.grade(payload(ECOSYSTEM, 'coding-latihan-2'))))
        alternative = ECOSYSTEM.replace('class Rawa(Ekosistem):', 'class Rawa(Ekosistem):\n    def __init__(self, nama, lokasi):\n        super().__init__(nama, lokasi)')
        self.assertTrue(self.passed(sandbox.grade(payload(alternative, 'coding-latihan-2'))))
        for key, solution, marker in [('coding-latihan-1', SENSOR, 'sensor = '),
                                      ('coding-latihan-2', ECOSYSTEM, 'rawa = ')]:
            report = sandbox.grade(payload(wrap_program(solution, marker), key))
            self.assertTrue(self.passed(report))
            self.assertTrue(report['checks'][-1]['passed'], 'Object inside main() must receive full presentation credit')

    def test_wrong_boundaries_values_and_attributes(self):
        for source in [SENSOR.replace('6.5 <= self.ph <= 8.5', '6.5 < self.ph < 8.5'),
                       SENSOR.replace('self.ph = ph', 'self.ph = 7'),
                       SENSOR.replace('Perlu pemeriksaan', 'Tidak normal'),
                       'class SensorAir:\n    pass']:
            with self.subTest(source=source[:50]):
                self.assertFalse(self.passed(sandbox.grade(payload(source))))

    def test_inheritance_and_overriding_required(self):
        for source in [ECOSYSTEM.replace('Rawa(Ekosistem)', 'Rawa'),
                       ECOSYSTEM.replace('Genangan rawa dipantau', 'Salah'),
                       ECOSYSTEM.replace('self.nama = nama', 'self.nama = "Tetap"')]:
            self.assertFalse(self.passed(sandbox.grade(payload(source, 'coding-latihan-2'))))

    def test_optional_output_and_formatting_variations(self):
        for key, solution, marker in [('coding-latihan-1', SENSOR, 'sensor = '),
                                      ('coding-latihan-2', ECOSYSTEM, 'rawa = ')]:
            for source in [solution[:solution.index(marker)],
                           solution[:solution.index('print(')] + 'print("output berbeda")',
                           solution.replace('Normal', '  nOrMaL  ').replace('Perlu pemeriksaan', ' PERLU   PEMERIKSAAN ')
                           .replace('Status air:', ' STATUS  AIR : ').replace('Rawa Bangkau', 'RAWA   BANGKAU')
                           .replace('Status belum ditentukan', ' STATUS   BELUM DITENTUKAN ')
                           .replace('Genangan rawa dipantau', ' GENANGAN   RAWA DIPANTAU ')]:
                report = sandbox.grade(payload(source, key))
                with self.subTest(key=key, source=source[-80:]):
                    self.assertTrue(self.passed(report))
                    self.assertEqual(7, len(report['checks']))
                    self.assertEqual(90, sum(c['weight'] for c in report['checks'] if c['required']))
                    self.assertTrue(all(c['feedback'] for c in report['checks']))
                    score = sum(c['weight'] for c in report['checks'] if c['passed'])
                    self.assertIn(score, [90, 100])
            no_output = sandbox.grade(payload(solution[:solution.index(marker)], key))
            self.assertFalse(no_output['checks'][-1]['passed'])
            self.assertFalse(no_output['checks'][-1]['required'])

    def test_updates_shared_attributes_print_only_and_missing_constructor_fail(self):
        cached = SENSOR.replace('self.ph = ph', 'self.ph = ph\n        self.nama = "Normal" if 6.5 <= ph <= 8.5 else "Perlu pemeriksaan"')
        cached = cached.replace('return "Normal" if 6.5 <= self.ph <= 8.5 else "Perlu pemeriksaan"', 'return self.nama')
        variants = [(cached, 'updates'),
                    (SENSOR.replace('return "Normal" if 6.5 <= self.ph <= 8.5 else "Perlu pemeriksaan"',
                                    'print("Normal" if 6.5 <= self.ph <= 8.5 else "Perlu pemeriksaan")'), 'normal'),
                    (SENSOR.replace('self.ph = ph', 'SensorAir.ph = ph'), 'attributes'),
                    ('class SensorAir:\n    lokasi = "Rawa Bangkau"\n    ph = 7', 'constructor')]
        for source, failed_id in variants:
            report = sandbox.grade(payload(source))
            self.assertFalse(self.passed(report))
            self.assertFalse(next(c for c in report['checks'] if c['id'] == failed_id)['passed'])
        inherited_status = ECOSYSTEM.replace('class Rawa(Ekosistem):\n    def status(self):\n        return "Genangan rawa dipantau"',
                                              'class Rawa(Ekosistem):\n    pass')
        self.assertFalse(self.passed(sandbox.grade(payload(inherited_status, 'coding-latihan-2'))))

    def test_local_and_official_rubrics_agree(self):
        root = Path(__file__).resolve().parents[2]
        php = subprocess.run(['php', '-d', 'display_errors=stderr', '-r',
                              "echo json_encode(require 'resources/coding/exercises.php');"],
                             cwd=root, capture_output=True, text=True, check=True)
        catalog = json.loads(php.stdout)
        for slug, solution, marker in [('latihan-1', SENSOR, 'sensor = '), ('latihan-2', ECOSYSTEM, 'rawa = ')]:
            sources = [solution, solution[:solution.index(marker)],
                       solution.replace('Normal', ' NORMAL ').replace('Genangan rawa dipantau', ' GENANGAN   RAWA DIPANTAU '),
                       solution.replace('self.lokasi = lokasi', 'self.lokasi = "Tetap"'),
                       solution.replace('6.5 <= self.ph <= 8.5', '6.5 < self.ph < 8.5'),
                       solution[:solution.index(marker)].replace('Rawa(Ekosistem)', 'Rawa'),
                       solution.replace('return "Normal"', 'return "Salah"'),
                       'results = [{"passed": True}]; score = 100; passed = True']
            for source in sources:
                namespace = {'__name__': '__main__'}
                output = io.StringIO()
                with contextlib.redirect_stdout(output):
                    exec(source, namespace)
                namespace['__oopy_output__'] = output.getvalue()
                exec(catalog[slug]['checker'], namespace)
                official = sandbox.grade(payload(source, 'coding-' + slug))
                with self.subTest(slug=slug, source=source[-40:]):
                    self.assertEqual('graded', official['status'])
                    self.assertEqual([(c['id'], c['passed'], c['required'], c['weight']) for c in official['checks']],
                                     [(c['id'], c['passed'], c['required'], c['weight']) for c in namespace['results']])

    def test_tampering_and_system_access_rejected_before_execution(self):
        for source in ['import os', 'open("/etc/passwd")', 'globals()',
                       'print((1).__class__)', 'print("{0.__class__}".format(1))',
                       'print = lambda *a: None', 'f = (x for x in []); print(f.gi_frame)',
                       'def x():\n    global print\n    print = 1']:
            self.assertFalse(self.passed(sandbox.grade(payload(source))))

    def test_syntax_runtime_error_and_forged_results_cannot_pass(self):
        self.assertFalse(self.passed(sandbox.grade(payload('if True print(1)'))))
        self.assertEqual('error', sandbox.grade(payload('raise ValueError("test")'))['status'])
        self.assertFalse(self.passed(sandbox.grade(payload('results = [{"passed": True}]; passed = True; score = 100'))))

    def test_gateway_payload_validation(self):
        self.assertTrue(server.valid_payload(payload()))
        for request in [{}, payload('', 'unknown'), {'exercise_key': 'coding-latihan-1', 'files': {'../main.py': 'pass'}},
                        payload('a' * 30001), payload() | {'passed': True}]:
            self.assertFalse(server.valid_payload(request))

    def test_unicode_transport_and_request_byte_limit(self):
        request = payload(SENSOR + '\n#' + chr(0xe9) * 18000)
        encoded = json.dumps(request).encode('utf-8')
        self.assertTrue(server.valid_payload(request))
        self.assertGreater(len(encoded), 65000)
        self.assertEqual(request, sandbox.read_request(io.BytesIO(encoded)))
        with self.assertRaises(ValueError):
            sandbox.read_request(io.BytesIO(b' ' * 200001))

    def test_malformed_container_response_fails_closed(self):
        for response in ['[]', 'null', '{}', '{invalid']:
            with self.subTest(response=response), patch.object(
                    server.subprocess, 'run', return_value=subprocess.CompletedProcess([], 0, response, '')):
                with self.assertRaises((ValueError, RuntimeError)):
                    server.run_sandbox(payload())

    def test_docker_limits_timeout_cleanup_and_unavailable_fail_closed(self):
        calls = []
        def timeout_run(command, **kwargs):
            calls.append(command)
            if command[1] == 'run':
                raise subprocess.TimeoutExpired(command, 8)
            return subprocess.CompletedProcess(command, 0)
        with patch.object(server.subprocess, 'run', timeout_run):
            report = server.run_sandbox(payload())
        self.assertEqual('error', report['status'])
        for flag in ['--network=none', '--read-only', '--cap-drop=ALL', '--memory=128m', '--cpus=0.5', '--pids-limit=16', '--security-opt=no-new-privileges']:
            self.assertIn(flag, calls[0])
        self.assertEqual(['docker', 'rm', '-f'], calls[1][:3])
        with patch.object(server.subprocess, 'run', return_value=subprocess.CompletedProcess([], 125, '', 'unavailable')):
            with self.assertRaises(RuntimeError):
                server.run_sandbox(payload())

    @unittest.skipUnless(os.environ.get('OOPY_TEST_DOCKER') == '1', 'Docker integration explicitly opt-in')
    def test_real_container_pass_error_timeout_and_system_access(self):
        self.assertTrue(self.passed(server.run_sandbox(payload())))
        self.assertTrue(self.passed(server.run_sandbox(payload(ECOSYSTEM, 'coding-latihan-2'))))
        self.assertFalse(self.passed(server.run_sandbox(payload('import os'))))
        self.assertEqual('error', server.run_sandbox(payload('while True: pass'))['status'])
        self.assertEqual('error', server.run_sandbox(payload('raise ValueError("test")'))['status'])

    @unittest.skipUnless(os.environ.get('OOPY_TEST_DOCKER') == '1', 'Docker integration explicitly opt-in')
    def test_real_container_variants_unicode_wrong_syntax_and_output_limit(self):
        alternative = SENSOR.replace('return "Normal" if 6.5 <= self.ph <= 8.5 else "Perlu pemeriksaan"',
                                     'return "Perlu pemeriksaan" if self.ph < 6.5 or self.ph > 8.5 else "Normal"')
        inherited = ECOSYSTEM.replace('class Rawa(Ekosistem):',
                                     'class Rawa(Ekosistem):\n    def __init__(self, nama, lokasi):\n        super().__init__(nama, lokasi)')
        for request in [payload(alternative), payload(inherited, 'coding-latihan-2'),
                        payload(wrap_program(SENSOR, 'sensor = ')),
                        payload(wrap_program(ECOSYSTEM, 'rawa = '), 'coding-latihan-2'),
                        payload(SENSOR + '\n#' + chr(0xe9) * 18000)]:
            report = server.run_sandbox(request)
            self.assertTrue(self.passed(report))
            self.assertTrue(all(c['passed'] for c in report['checks']))
        for request in [payload(SENSOR.replace('6.5 <= self.ph <= 8.5', '6.5 < self.ph < 8.5')),
                        payload(ECOSYSTEM.replace('Rawa(Ekosistem)', 'Rawa'), 'coding-latihan-2'),
                        payload('if True print(1)')]:
            self.assertFalse(self.passed(server.run_sandbox(request)))
        report = server.run_sandbox(payload('print("x" * 200000)'))
        self.assertFalse(self.passed(report))
        self.assertLessEqual(len(report['output']), 12000)
        started = time.monotonic()
        self.assertEqual('error', server.run_sandbox(payload('while True: print("x" * 1000)'))['status'])
        self.assertLess(time.monotonic() - started, 12)
        remaining = subprocess.run(['docker', 'ps', '-a', '--filter', 'name=oopy-grade-', '--format', '{{.Names}}'],
                                   text=True, capture_output=True, check=True)
        self.assertEqual('', remaining.stdout.strip())


class GatewayHttpTest(unittest.TestCase):
    def setUp(self):
        self.httpd = server.ThreadingHTTPServer(('127.0.0.1', 0), server.Handler)
        self.thread = threading.Thread(target=self.httpd.serve_forever, daemon=True)
        self.thread.start()
        self.addCleanup(self.httpd.server_close)
        self.addCleanup(self.httpd.shutdown)
        self.secret = 'http-test-token-' + 't' * 48
        self.token_patch = patch.object(server, 'TOKEN', self.secret)
        self.token_patch.start()
        self.addCleanup(self.token_patch.stop)
        self.log_patch = patch.object(server.Handler, 'log_message')
        self.log_patch.start()
        self.addCleanup(self.log_patch.stop)

    def post(self, body=None, token=None, path='/grade', headers=None):
        connection = http.client.HTTPConnection('127.0.0.1', self.httpd.server_port, timeout=3)
        self.addCleanup(connection.close)
        request_headers = {'Content-Type': 'application/json', **(headers or {})}
        if token is not None:
            request_headers['Authorization'] = 'Bearer ' + token
        connection.request('POST', path, json.dumps(payload()) if body is None else body, request_headers)
        response = connection.getresponse()
        return response.status, json.loads(response.read())

    def test_http_authentication_rejects_missing_and_wrong_token(self):
        with patch.object(server, 'run_sandbox') as run:
            self.assertEqual(401, self.post()[0])
            self.assertEqual(401, self.post(token='wrong')[0])
            run.assert_not_called()

    def test_http_payload_size_and_path_validation(self):
        with patch.object(server, 'run_sandbox') as run:
            self.assertEqual(404, self.post(token=self.secret, path='/unknown')[0])
            self.assertEqual(422, self.post(body='{invalid', token=self.secret)[0])
            self.assertEqual(422, self.post(body=json.dumps(payload('x' * 30001)), token=self.secret)[0])
            self.assertEqual(413, self.post(body='{}', token=self.secret, headers={'Content-Length': '200001'})[0])
            run.assert_not_called()

    def test_http_authenticated_success_and_gateway_failure(self):
        expected = {'version': 2, 'exercise_key': 'coding-latihan-1', 'status': 'graded',
                    'checks': [{'label': 'Fixture', 'passed': True, 'feedback': ''}], 'output': ''}
        with patch.object(server, 'run_sandbox', return_value=expected) as run:
            self.assertEqual((200, expected), self.post(token=self.secret))
            run.assert_called_once_with(payload())
        with patch.object(server, 'run_sandbox', side_effect=OSError('Docker unavailable')):
            self.assertEqual(503, self.post(token=self.secret)[0])
        with patch.object(server, 'SLOTS') as slots, patch.object(server, 'run_sandbox') as run:
            slots.acquire.return_value = False
            self.assertEqual(503, self.post(token=self.secret)[0])
            run.assert_not_called()
            slots.release.assert_not_called()


if __name__ == '__main__':
    unittest.main()
