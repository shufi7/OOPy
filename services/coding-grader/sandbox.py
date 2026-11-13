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
import types

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


MULTIFILE_KEY = 'coding-latihan-2-multifile-v1'
EXPORTS = {'ekosistem': 'Ekosistem', 'rawa': 'Rawa'}


def validate_source(source, filename='main.py', key='coding-latihan-1'):
    tree = ast.parse(source, filename=filename)
    if sum(1 for _ in ast.walk(tree)) > 6000:
        raise ValueError('Kode terlalu kompleks untuk latihan ini.')
    for node in ast.walk(tree):
        if isinstance(node, (ast.Import, ast.ImportFrom)):
            allowed = {'main.py': {'ekosistem', 'rawa'}, 'rawa.py': {'ekosistem'}, 'ekosistem.py': set()}
            names = [node.module] if isinstance(node, ast.ImportFrom) else [alias.name for alias in node.names]
            if (key != MULTIFILE_KEY or any(name not in allowed.get(filename, set()) for name in names)
                    or isinstance(node, ast.ImportFrom) and (node.level != 0 or any(
                        alias.name != EXPORTS[node.module] for alias in node.names))):
                raise ValueError(filename + ': import hanya diperbolehkan untuk dependensi ekosistem dan rawa dalam proyek ini.')
            for alias in node.names:
                name = alias.asname or alias.name
                if name.startswith('_') or name in SAFE_BUILTINS:
                    raise ValueError(filename + ': alias import tidak diizinkan.')
        if isinstance(node, (ast.Global, ast.Nonlocal,
                             ast.AsyncFunctionDef, ast.Await, ast.AsyncFor,
                             ast.With, ast.AsyncWith)):
            raise ValueError('Gunakan Python dasar tanpa import, akses sistem, atau async.')
        attributes = ALLOWED_ATTRIBUTES | ({'tinggi_air_cm', 'Ekosistem', 'Rawa'} if key == MULTIFILE_KEY else set())
        if isinstance(node, ast.Attribute) and node.attr not in attributes:
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
        if isinstance(node, ast.MatchMapping) and node.rest and (node.rest.startswith('_') or node.rest in SAFE_BUILTINS):
            raise ValueError('Nama pattern tersebut tidak didukung.')
        if isinstance(node, ast.MatchClass) and any(name not in attributes for name in node.kwd_attrs):
            raise ValueError('Atribut pattern di luar kontrak latihan.')
    return tree


def execute_files(files, key):
    expected = {'ekosistem.py', 'rawa.py', 'main.py'} if key == MULTIFILE_KEY else {'main.py'}
    if (key not in ('coding-latihan-1', 'coding-latihan-2', MULTIFILE_KEY)
            or not isinstance(files, dict) or set(files) != expected
            or any(not isinstance(source, str) or not source.strip() or len(source) > 30000
                   or len(source.encode('utf-8')) > 60000 for source in files.values())
            or sum(len(source) for source in files.values()) > 30000
            or sum(len(source.encode('utf-8')) for source in files.values()) > 60000):
        raise ValueError('Struktur file atau batas ukuran proyek tidak valid.')
    trees = {name: validate_source(source, name, key) for name, source in files.items()}
    modules = {}
    loading = set()

    def load(name):
        if name in loading:
            raise ValueError('Import melingkar tidak didukung: ' + name)
        if name not in modules:
            module = types.ModuleType(name)
            modules[name] = module
            module.__dict__.update({'__builtins__': safe_builtins()})
            loading.add(name)
            try:
                exec(compile(trees[name + '.py'], name + '.py', 'exec'), module.__dict__)
            finally:
                loading.remove(name)
        return modules[name]

    def restricted_import(name, globals=None, locals=None, fromlist=(), level=0):
        caller = (globals or {}).get('__name__')
        allowed = {'__main__': {'ekosistem', 'rawa'}, 'rawa': {'ekosistem'}, 'ekosistem': set()}
        if (key != MULTIFILE_KEY or level or name not in allowed.get(caller, set())
                or any(export != EXPORTS[name] for export in (fromlist or ()))):
            raise ValueError('Import modul di luar proyek tidak diizinkan.')
        return load(name)

    def safe_builtins():
        result = dict(SAFE_BUILTINS)
        if key == MULTIFILE_KEY:
            result['__import__'] = restricted_import
        return result

    namespace = {'__builtins__': safe_builtins(), '__name__': '__main__'}
    observations = []

    def observe(frame, event, result):
        if (len(observations) < 1000 and event == 'return' and frame.f_code.co_name in ('__init__', 'status')
                and frame.f_back is not None and frame.f_back.f_code.co_filename == 'main.py'):
            obj = frame.f_locals.get(frame.f_code.co_varnames[0]) if frame.f_code.co_argcount else None
            if obj is not None:
                observations.append((frame.f_code.co_name, obj, result, tuple(
                    getattr(obj, name, None) for name in ('nama', 'lokasi', 'tinggi_air_cm'))))

    previous = sys.getprofile()
    try:
        if key == MULTIFILE_KEY:
            sys.setprofile(observe)
        exec(compile(trees['main.py'], 'main.py', 'exec'), namespace)
    finally:
        sys.setprofile(previous)
    return namespace, modules, observations


def grade(payload):
    key = payload['exercise_key']
    output = LimitedOutput()
    try:
        with contextlib.redirect_stdout(output), contextlib.redirect_stderr(output):
            # This process is launched only within a constrained, networkless container.
            namespace, modules, observations = execute_files(payload['files'], key)
            program_output = output.getvalue()
            checks = run_checks(namespace, program_output, key,
                                extra_sensor=[('Sensor ' + secrets.token_hex(4), ph) for ph in (
                                    6.5 + secrets.randbelow(2000) / 1000,
                                    5 + secrets.randbelow(1000) / 1000,
                                    9 + secrets.randbelow(1000) / 1000)],
                                extra_ecosystem=[('Ekosistem ' + secrets.token_hex(4), 'Amuntai')],
                                modules=modules, execution=observations,
                                extra_water=[secrets.randbelow(8000) / 100, 80 + secrets.randbelow(12000) / 100])
    except BaseException as error:
        if isinstance(error, (SyntaxError, ValueError, RecursionError)):
            detail = type(error).__name__ + ': ' + str(error)[:300]
        else:
            trace = error.__traceback__
            location = ''
            while trace:
                if trace.tb_frame.f_code.co_filename in payload['files']:
                    location = ' di ' + trace.tb_frame.f_code.co_filename + ' baris ' + str(trace.tb_lineno)
                trace = trace.tb_next
            detail = type(error).__name__ + location
        return execution_error(key, output.getvalue(), detail)
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
