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
import secrets
import sys


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
    checks = []

    def check(label, callback, feedback):
        try:
            passed = bool(callback())
        except BaseException:
            passed = False
        checks.append({'label': label, 'passed': passed, 'feedback': '' if passed else feedback})

    try:
        tree = validate_source(source)
    except (SyntaxError, ValueError, RecursionError) as error:
        return {'version': 1, 'exercise_key': key, 'status': 'graded', 'output': '', 'checks': [
            {'label': 'Format program', 'passed': False, 'feedback': str(error)[:300]},
        ]}
    namespace = {'__builtins__': dict(SAFE_BUILTINS), '__name__': '__main__'}
    try:
        with contextlib.redirect_stdout(output), contextlib.redirect_stderr(output):
            # This process is launched only within a constrained, networkless container.
            exec(compile(tree, 'main.py', 'exec'), namespace)
            if key == 'coding-latihan-1':
                cls = namespace.get('SensorAir')
                check('Class dan constructor SensorAir', lambda: isinstance(cls, type) and '__init__' in vars(cls),
                      'Definisikan class SensorAir beserta constructor-nya.')
                locations = ['Rawa Bangkau', 'Danau Panggang', 'Amuntai ' + secrets.token_hex(3)]
                check('Atribut dan isolasi object', lambda: all(
                    cls(location, ph).lokasi == location and cls(location, ph).ph == ph
                    for location, ph in zip(locations, [6.3, 7.2, 8.7])),
                    'Simpan parameter lokasi dan ph pada setiap object, bukan nilai tetap.')
                check('Nilai batas pH', lambda: all(cls('Batas', ph).status_air() == 'Normal' for ph in [6.5, 8.5]),
                      'Kedua batas 6.5 dan 8.5 harus termasuk rentang Normal.')
                normal = [7.0, 6.5 + secrets.randbelow(2000) / 1000]
                check('Nilai dalam rentang', lambda: all(cls('Normal', ph).status_air() == 'Normal' for ph in normal),
                      'Gunakan rentang pH, bukan satu nilai tertentu.')
                abnormal = [0, 6.499, 8.501, 14, 5 + secrets.randbelow(1000) / 1000, 9 + secrets.randbelow(1000) / 1000]
                check('Nilai di luar rentang', lambda: all(cls('Periksa', ph).status_air() == 'Perlu pemeriksaan' for ph in abnormal),
                      'Kembalikan teks Perlu pemeriksaan untuk pH di luar rentang.')
                check('Perubahan atribut object', lambda: updated_sensor(cls),
                      'status_air() perlu membaca ph terbaru dari object.')
                check('Object dan output program', lambda: has_object_call(tree, 'SensorAir') and 'Rawa Bangkau' in output.getvalue() and 'Status air: Normal' in output.getvalue(),
                      'Buat object, lalu tampilkan lokasi Rawa Bangkau dan Status air: Normal.')
            elif key == 'coding-latihan-2':
                parent, child = namespace.get('Ekosistem'), namespace.get('Rawa')
                check('Constructor Ekosistem', lambda: isinstance(parent, type) and '__init__' in vars(parent),
                      'Definisikan class induk dan constructor nama, lokasi.')
                check('Atribut induk', lambda: attributes_match(parent),
                      'Constructor induk harus menyimpan kedua parameter pada object.')
                check('Status induk', lambda: parent('Dasar', 'Kalsel').status() == 'Status belum ditentukan',
                      'Periksa nilai kembalian status() pada Ekosistem.')
                check('Pewarisan', lambda: isinstance(child, type) and child is not parent and issubclass(child, parent),
                      'Rawa perlu mewarisi Ekosistem.')
                check('Atribut subclass', lambda: attributes_match(child),
                      'Pastikan nama dan lokasi diteruskan ke constructor induk.')
                check('Overriding status', lambda: 'status' in vars(child) and child.status is not parent.status and child('Rawa Bangkau', 'Kalsel').status() == 'Genangan rawa dipantau',
                      'Override status() pada Rawa dengan teks yang diminta.')
                check('Object dan output program', lambda: has_object_call(tree, 'Rawa') and 'Genangan rawa dipantau' in output.getvalue(),
                      'Buat object Rawa dan tampilkan hasil status().')
            else:
                raise ValueError('Latihan tidak dikenal.')
    except BaseException as error:
        return {'version': 1, 'exercise_key': key, 'status': 'error', 'checks': [],
                'output': output.getvalue() + '\nProgram Python error: ' + type(error).__name__}
    return {'version': 1, 'exercise_key': key, 'status': 'graded', 'checks': checks, 'output': output.getvalue()}


def updated_sensor(cls):
    sensor = cls('Rawa Bangkau', 7)
    sensor.ph = 9
    return sensor.status_air() == 'Perlu pemeriksaan'


def attributes_match(cls):
    for name, location in [('Rawa Bangkau', 'Kalimantan Selatan'), ('Ekosistem ' + secrets.token_hex(3), 'Amuntai')]:
        item = cls(name, location)
        if item.nama != name or item.lokasi != location:
            return False
    return True


def has_object_call(tree, name):
    return any(isinstance(node, ast.Call) and isinstance(node.func, ast.Name) and node.func.id == name for node in ast.walk(tree))


if __name__ == '__main__':
    request = json.loads(sys.stdin.buffer.read(65000))
    report = grade(request)
    sys.stdout.write(json.dumps(report, ensure_ascii=True))
