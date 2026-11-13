"""Behavioral rubric shared by Pyodide practice checks and the Docker grader."""
import builtins
import sys


def normalized(value):
    return ' '.join(value.casefold().split()) if isinstance(value, str) else None


def uses_parent_initializer(child, parent, cases):
    initializer = vars(child).get('__init__')
    parent_initializer = vars(parent).get('__init__')
    if not callable(initializer) or not callable(parent_initializer):
        return False
    scope = initializer.__globals__
    missing = object()
    previous = scope.get('super', missing)
    calls = []

    class SuperProbe:
        def __getattribute__(self, name):
            target = object.__getattribute__(self, 'target')
            method = getattr(target, name)
            if name != '__init__':
                return method

            def initialize(*args, **kwargs):
                result = method(*args, **kwargs)
                if getattr(method, '__func__', None) is parent_initializer:
                    calls.append((target.__self__, getattr(target.__self__, 'nama', None),
                                  getattr(target.__self__, 'lokasi', None)))
                return result
            return initialize

    def observed_super(*args):
        if not args:
            frame = sys._getframe(1)
            args = (frame.f_locals['__class__'], frame.f_locals[frame.f_code.co_varnames[0]])
        proxy = object.__new__(SuperProbe)
        object.__setattr__(proxy, 'target', builtins.super(*args))
        return proxy

    try:
        scope['super'] = observed_super
        for name, location, water in cases:
            calls.clear()
            instance = child(name, location, water)
            if not any(obj is instance and nama == name and lokasi == location
                       for obj, nama, lokasi in calls):
                return False
        return True
    finally:
        if previous is missing:
            scope.pop('super', None)
        else:
            scope['super'] = previous


def run_checks(namespace, output, key, extra_sensor=(), extra_ecosystem=(), modules=None,
               extra_water=(), execution=()):
    results = []

    def check(identifier, label, callback, feedback, required=True):
        detail = ''
        try:
            passed = bool(callback())
        except BaseException as error:
            passed = False
            detail = ' Pemeriksaan ini menghasilkan ' + type(error).__name__ + '.'
        results.append({'id': identifier, 'label': label, 'passed': passed,
                        'required': required, 'weight': 15 if required else 10,
                        'feedback': 'Sudah sesuai.' if passed else feedback + detail})

    def attributes(cls, pairs, names):
        objects = [cls(*pair) for pair in pairs]
        return all(isinstance(item, cls) and all(
            name in vars(item) and getattr(item, name) == value
            for name, value in zip(names, pair)) for item, pair in zip(objects, pairs))

    def sensor_status(cls, values, expected):
        return all(normalized(cls('Uji pH', ph).status_air()) == normalized(expected) for ph in values)

    def updated_sensor(cls):
        sensor, other = cls('Sensor A', 7), cls('Sensor B', 8)
        for ph, expected in [(9, 'Perlu pemeriksaan'), (6.5, 'Normal'),
                             (6, 'Perlu pemeriksaan'), (8.5, 'Normal')]:
            sensor.ph = ph
            if normalized(sensor.status_air()) != normalized(expected) or other.ph != 8:
                return False
        sensor.lokasi = 'Lokasi baru'
        return sensor.lokasi == 'Lokasi baru' and other.lokasi == 'Sensor B'

    def printed_output(expected):
        text = normalized(output).replace(' :', ':').replace(': ', ':')
        return all(fragment in text for fragment in expected)

    if key == 'coding-latihan-1':
        cls = namespace.get('SensorAir')
        check('constructor', 'Constructor SensorAir',
              lambda: isinstance(cls, type) and callable(vars(cls).get('__init__')) and isinstance(cls('Uji', 7), cls),
              'Buat class SensorAir dengan __init__(self, lokasi, ph) yang dapat dipanggil.')
        check('attributes', 'Atribut setiap object',
              lambda: attributes(cls, [('Rawa Bangkau', 6.3), ('Amuntai', 8.7), *extra_sensor], ('lokasi', 'ph')),
              'Simpan parameter pada self.lokasi dan self.ph; setiap object harus memiliki data sendiri.')
        check('boundaries', 'Batas pH 6.5 dan 8.5', lambda: sensor_status(cls, [6.5, 8.5], 'Normal'),
              'pH 6.5 dan 8.5 termasuk Normal. Gunakan batas inklusif.')
        check('normal', 'pH dalam rentang normal',
              lambda: sensor_status(cls, [7, 7.25, 8, *[ph for _, ph in extra_sensor if 6.5 <= ph <= 8.5]], 'Normal'),
              'Kembalikan string Normal untuk seluruh rentang 6.5 sampai 8.5, bukan hanya pH 7.')
        check('abnormal', 'pH di luar rentang',
              lambda: sensor_status(cls, [0, 6.499, 8.501, 14, *[ph for _, ph in extra_sensor if ph < 6.5 or ph > 8.5]], 'Perlu pemeriksaan'),
              'Kembalikan string Perlu pemeriksaan jika pH di bawah 6.5 atau di atas 8.5.')
        check('updates', 'Perubahan atribut object', lambda: updated_sensor(cls),
              'Baca self.ph terbaru setiap kali status_air() dipanggil; perubahan satu object tidak boleh mengubah object lain.')
        check('presentation', 'Penyajian output program',
              lambda: printed_output(['rawa bangkau', 'status air:normal']),
              'Tambahan: buat object dan cetak lokasi Rawa Bangkau serta Status air: Normal. Kapitalisasi dan spasi boleh berbeda.', False)
    elif key == 'coding-latihan-2-multifile-v1':
        modules = modules or {}
        parent_module, child_module = modules.get('ekosistem'), modules.get('rawa')
        parent = getattr(parent_module, 'Ekosistem', None)
        child = getattr(child_module, 'Rawa', None)
        pairs = [('Rawa Bangkau', 'Hulu Sungai Selatan'), ('Danau', 'Amuntai'), *extra_ecosystem]

        def modular():
            if not isinstance(parent, type) or not isinstance(child, type):
                return False
            imported_child = any(value is child or getattr(value, 'Rawa', None) is child for value in namespace.values())
            imported_child = imported_child or any(isinstance(obj, child) for _, obj, _, _ in execution)
            imported_parent = any(value is parent or getattr(value, 'Ekosistem', None) is parent
                                  for value in vars(child_module).values()) if child_module is not None else False
            methods_in_module = all(vars(cls).get(name) is None or getattr(vars(cls)[name], '__module__', None) == module
                                    for cls, module in ((parent, 'ekosistem'), (child, 'rawa'))
                                    for name in ('__init__', 'status'))
            return (parent.__module__ == 'ekosistem' and child.__module__ == 'rawa'
                    and imported_parent and imported_child and methods_in_module)

        def child_constructor():
            values = [('A', 'Lokasi A', 79.9), ('B', 'Lokasi B', 85.5), ('Batas', 'Lokasi batas', 80),
                      *[(name, location, water) for name, location in extra_ecosystem for water in (80.25, *extra_water)]]
            return attributes(child, values, ('nama', 'lokasi', 'tinggi_air_cm')) and uses_parent_initializer(child, parent, values)

        def dynamic_status():
            if not callable(vars(child).get('status')) or child.status is parent.status:
                return False
            a, b = child('A', 'X', 85), child('B', 'Y', 12)
            for value in [79.9, 80, 80.0, 85, 0, 12.5, 180, *extra_water]:
                a.tinggi_air_cm = value
                if normalized(a.status()) != ('siaga' if value >= 80 else 'normal'):
                    return False
                if b.tinggi_air_cm != 12 or normalized(b.status()) != 'normal':
                    return False
            a.nama, a.lokasi = 'Diubah', 'Baru'
            return b.nama == 'B' and b.lokasi == 'Y'

        def presentation():
            example = ('Rawa Bangkau', 'Hulu Sungai Selatan', 85)
            created = [obj for method, obj, result, values in execution
                       if method == '__init__' and isinstance(obj, child) and values == example]
            called = any(method == 'status' and any(obj is item for item in created)
                         and normalized(result) == 'siaga' and values == example
                         for method, obj, result, values in execution)
            return called and printed_output(['rawa bangkau - hulu sungai selatan', 'status air:siaga'])

        check('modules', 'Struktur modul dan import', modular,
              'Definisikan Ekosistem di ekosistem.py dan Rawa di rawa.py; impor induk pada rawa.py dan Rawa pada main.py.')
        check('constructor', 'Constructor dan atribut Ekosistem',
              lambda: callable(vars(parent).get('__init__')) and attributes(parent, pairs, ('nama', 'lokasi')),
              'Constructor Ekosistem harus menyimpan nama dan lokasi yang diterima pada setiap instance.')
        check('parent_status', 'Method status induk',
              lambda: all(normalized(parent(*pair).status()) == 'belum dipantau' for pair in pairs),
              'Ekosistem.status() harus mengembalikan string Belum dipantau.')
        check('inheritance', 'Pewarisan Rawa dari Ekosistem',
              lambda: isinstance(parent, type) and isinstance(child, type) and child is not parent and issubclass(child, parent),
              'Rawa harus benar-benar mewarisi Ekosistem dari modul ekosistem.')
        check('child_constructor', 'Constructor Rawa dan super()', child_constructor,
              'Definisikan constructor Rawa; gunakan super().__init__(nama, lokasi) dan simpan tinggi_air_cm pada instance.')
        check('overriding', 'Overriding dan perubahan tinggi air', dynamic_status,
              'Override status(): Siaga jika tinggi_air_cm >= 80, selain itu Normal. Baca atribut terbaru dan pisahkan data antarobjek.')
        check('presentation', 'Contoh objek dan output main.py', presentation,
              'Tambahan: buat contoh Rawa Bangkau di main.py, panggil status() melalui objek, lalu tampilkan kedua baris output.', False)
    elif key == 'coding-latihan-2':
        parent, child = namespace.get('Ekosistem'), namespace.get('Rawa')
        pairs = [('Rawa Bangkau', 'Kalimantan Selatan'), ('Danau', 'Amuntai'), *extra_ecosystem]
        check('constructor', 'Constructor Ekosistem',
              lambda: isinstance(parent, type) and callable(vars(parent).get('__init__')) and isinstance(parent('Uji', 'Kalsel'), parent),
              'Buat class Ekosistem dengan __init__(self, nama, lokasi) yang dapat dipanggil.')
        check('attributes', 'Atribut class induk', lambda: attributes(parent, pairs, ('nama', 'lokasi')),
              'Simpan nama dan lokasi dari parameter pada atribut masing-masing object induk.')
        check('parent_status', 'Method status induk',
              lambda: all(normalized(parent(*pair).status()) == 'status belum ditentukan' for pair in pairs),
              'Ekosistem.status() harus mengembalikan string Status belum ditentukan menggunakan return.')
        check('inheritance', 'Pewarisan Rawa dari Ekosistem',
              lambda: isinstance(parent, type) and isinstance(child, type) and child is not parent and issubclass(child, parent),
              'Jadikan Rawa subclass Ekosistem, misalnya class Rawa(Ekosistem).')
        check('child_attributes', 'Atribut subclass', lambda: attributes(child, pairs, ('nama', 'lokasi')),
              'Rawa harus menerima dan menyimpan nama serta lokasi. Warisi constructor atau teruskan parameter ke super().__init__().')
        check('overriding', 'Method overriding pada Rawa',
              lambda: 'status' in vars(child) and child.status is not parent.status and all(
                  normalized(child(*pair).status()) == 'genangan rawa dipantau' for pair in pairs),
              'Definisikan status() sendiri pada Rawa dan kembalikan string Genangan rawa dipantau.')
        check('presentation', 'Penyajian output program',
              lambda: printed_output(['genangan rawa dipantau']),
              'Tambahan: buat object Rawa dan cetak hasil status(). Kapitalisasi dan spasi boleh berbeda.', False)
    else:
        raise ValueError('Latihan tidak dikenal.')
    return results
