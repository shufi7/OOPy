"""Official checks. Execute this file ONLY inside the disposable Docker sandbox.

No Laravel credentials, Docker socket, host directories, or network are available.
The limited language contract additionally keeps learner code away from checker
objects. It is NOT a substitute for the container boundary.
"""
import ast
import builtins
import contextlib
import io
import json
from pathlib import Path
import runpy
import secrets
import sys

run_checks = runpy.run_path(str(Path(__file__).with_name('checks.py')))['run_checks']


class LimitedOutput(io.StringIO):
    def write(self, text):
        remaining = 12000 - self.tell()
        if remaining > 0:
            super().write(text[:remaining])
        return len(text)


SAFE_BUILTINS = {name: getattr(builtins, name) for name in (
    'abs', 'all', 'any', 'bool', 'dict', 'enumerate', 'float', 'int',
    'isinstance', 'issubclass', 'len', 'list', 'max', 'min', 'object',
    'print', 'range', 'round', 'set', 'str', 'sum', 'super', 'tuple',
    'zip', 'ValueError', 'TypeError', 'Exception', '__build_class__',
)}
ALLOWED_ATTRIBUTES = {
    'lokasi', 'ph', 'nama', 'status_air', 'status', '__init__',
    'append', 'extend', 'count', 'index', 'get', 'items', 'keys', 'values',
    'lower', 'upper', 'strip', 'startswith', 'endswith', 'join', 'split',
}


def validate_source(source):
    tree = ast.parse(source, filename='main.py')
    if sum(1 for _ in ast.walk(tree)) > 6000:
        raise ValueError('Kode terlalu kompleks untuk latihan ini.')
    for node in ast.walk(tree):
        if isinstance(node, (ast.Import, ast.ImportFrom, ast.Global, ast.Nonlocal,
                             ast.AsyncFunctionDef, ast.Await, ast.AsyncFor,
                             ast.With, ast.AsyncWith)):
            raise ValueError('Gunakan Python dasar tanpa import, akses sistem, atau async.')
        if isinstance(node, ast.Attribute) and node.attr not in ALLOWED_ATTRIBUTES:
            raise ValueError('Atribut di luar kontrak latihan: ' + node.attr[:80])
        if isinstance(node, ast.Name):
            if node.id.startswith('_') and not (node.id == '__name__' and isinstance(node.ctx, ast.Load)):
                raise ValueError('Akses internal interpreter tidak diizinkan.')
            if isinstance(node.ctx, (ast.Store, ast.Del)) and node.id in SAFE_BUILTINS:
                raise ValueError('Nama bawaan Python tidak boleh diubah.')
        if isinstance(node, (ast.FunctionDef, ast.ClassDef)):
            if (node.name.startswith('_') and node.name != '__init__') or node.name in SAFE_BUILTINS or node.decorator_list:
                raise ValueError('Gunakan class dan method biasa tanpa decorator atau nama internal.')
            if isinstance(node, ast.ClassDef) and node.keywords:
                raise ValueError('Metaclass tidak didukung dalam latihan ini.')
        if isinstance(node, ast.arg) and (node.arg.startswith('_') or node.arg in SAFE_BUILTINS):
            raise ValueError('Nama parameter tersebut tidak didukung.')
        if isinstance(node, ast.ExceptHandler) and node.name and (node.name.startswith('_') or node.name in SAFE_BUILTINS):
            raise ValueError('Nama exception tersebut tidak didukung.')
        if isinstance(node, (ast.MatchAs, ast.MatchStar)) and node.name and (node.name.startswith('_') or node.name in SAFE_BUILTINS):
            raise ValueError('Nama pattern tersebut tidak didukung.')
    return tree


def grade(payload):
    key = payload['exercise_key']
    source = payload['files']['main.py']
    output = LimitedOutput()
    try:
        tree = validate_source(source)
    except (SyntaxError, ValueError, RecursionError) as error:
        return execution_error(key, '', type(error).__name__ + ': ' + str(error)[:300])
    namespace = {'__builtins__': dict(SAFE_BUILTINS), '__name__': '__main__'}
    try:
        with contextlib.redirect_stdout(output), contextlib.redirect_stderr(output):
            # This process is launched only within a constrained, networkless container.
            exec(compile(tree, 'main.py', 'exec'), namespace)
            program_output = output.getvalue()
            checks = run_checks(namespace, program_output, key,
                                extra_sensor=[('Sensor ' + secrets.token_hex(4), ph) for ph in (
                                    6.5 + secrets.randbelow(2000) / 1000,
                                    5 + secrets.randbelow(1000) / 1000,
                                    9 + secrets.randbelow(1000) / 1000)],
                                extra_ecosystem=[('Ekosistem ' + secrets.token_hex(4), 'Amuntai')])
    except BaseException as error:
        return execution_error(key, output.getvalue(), type(error).__name__)
    return {'version': 2, 'exercise_key': key, 'status': 'graded', 'checks': checks, 'output': program_output}


def execution_error(key, output, detail):
    return {'version': 2, 'exercise_key': key, 'status': 'error',
            'output': output + '\nProgram Python error: ' + detail,
            'checks': [{'id': 'execution', 'label': 'Eksekusi program', 'required': True, 'weight': 0,
                        'passed': False, 'feedback': 'Program belum dapat dinilai: ' + detail}]}


def read_request(stream):
    # Escaped Unicode can exceed the source code's UTF-8 byte budget.
    data = stream.read(200001)
    if len(data) > 200000:
        raise ValueError('Payload too large')
    return json.loads(data)


if __name__ == '__main__':
    request = read_request(sys.stdin.buffer)
    report = grade(request)
    sys.stdout.write(json.dumps(report, ensure_ascii=True))
