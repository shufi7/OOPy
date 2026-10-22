"""Behavioral rubric shared by Pyodide practice checks and the Docker grader."""


def normalized(value):
    return ' '.join(value.casefold().split()) if isinstance(value, str) else None


def run_checks(namespace, output, key, extra_sensor=(), extra_ecosystem=()):
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
