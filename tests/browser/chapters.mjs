// Explicit expectations: availability changes must update this contract.
export const chapters = [
    { slug: 'dasar-pemrograman-oop', previous: null, next: 'kelas-dan-objek' },
    { slug: 'kelas-dan-objek', previous: 'dasar-pemrograman-oop', next: 'enkapsulasi' },
    { slug: 'enkapsulasi', previous: 'kelas-dan-objek', next: 'pewarisan' },
    { slug: 'pewarisan', previous: 'enkapsulasi', next: 'polimorfisme' },
    { slug: 'polimorfisme', previous: 'pewarisan', next: 'kelas-abstrak' },
    { slug: 'kelas-abstrak', previous: 'polimorfisme', next: 'evaluasi-akhir' },
];

export const evaluationChapter = { slug: 'evaluasi-akhir', previous: 'kelas-abstrak', next: null };

const inheritance = `class Ekosistem:
    def __init__(self, nama, lokasi):
        self.nama = nama
        self.lokasi = lokasi
    def info(self):
        return f"{self.nama} - {self.lokasi}"

class Sungai(Ekosistem):
    def __init__(self, nama, lokasi, panjang_km):
        super().__init__(nama, lokasi)
        self.panjang_km = panjang_km
    def info(self):
        return f"{self.nama}, {self.lokasi}, {self.panjang_km} km"

class Rawa(Ekosistem):
    def __init__(self, nama, lokasi, luas_ha):
        super().__init__(nama, lokasi)
        self.luas_ha = luas_ha
    def info(self):
        return f"{self.nama}, {self.lokasi}, {self.luas_ha} ha"

print(Sungai("Sungai Barito", "Banjarmasin", 25).info())
print(Rawa("Bangkau", "Hulu Sungai Selatan", 15).info())`;

const sensors = `class SensorPH:
    def status(self):
        return "pH: 7.1"
class SensorSuhu:
    def status(self):
        return "Suhu: 29.5"
class SensorTinggiAir:
    def status(self):
        return "Tinggi air: 128"
class SensorKekeruhan:
    def status(self):
        return "Kekeruhan: 20"
sensor = [SensorPH(), SensorSuhu(), SensorTinggiAir(), SensorKekeruhan()]
for item in sensor:
    print(item.status())`;

const abstractSensors = `from abc import ABC, abstractmethod

class AlatPantau(ABC):
    @abstractmethod
    def baca(self):
        pass

class SensorTinggiAir(AlatPantau):
    def baca(self):
        return 128

class SensorSuhu(AlatPantau):
    def baca(self):
        return 29.5

sensor = [SensorTinggiAir(), SensorSuhu()]
for item in sensor:
    print(item.baca())`;

export const exercises = [
    {
        slug: 'pewarisan', id: 'bab4-pewarisan-ekosistem', checks: 8,
        solution: inheritance, output: /Sungai Barito.*25.*Bangkau.*15/s,
        alternatives: [
            inheritance.replace('super().__init__(nama, lokasi)', 'super(Sungai, self).__init__(nama=nama, lokasi=lokasi)')
                .replace('super().__init__(nama, lokasi)', 'parent = super()\n        parent.__init__(nama, lokasi)'),
        ],
        incorrect: [
            { source: inheritance.replaceAll('super().__init__(nama, lokasi)', 'self.nama = nama\n        self.lokasi = lokasi'), label: /super\(\)/ },
            { source: inheritance.replaceAll('super().__init__(nama, lokasi)', 'if False:\n            super().__init__(nama, lokasi)\n        Ekosistem.__init__(self, nama, lokasi)'), label: /super\(\)/ },
            { source: inheritance.replaceAll('super().__init__(nama, lokasi)', 'super().__init__("Salah", "Salah")\n        self.nama = nama\n        self.lokasi = lokasi'), label: /super\(\)/ },
            { source: inheritance.replace('class Sungai(Ekosistem):', 'class Sungai:').replace('super().__init__(nama, lokasi)', 'self.nama = nama\n        self.lokasi = lokasi'), label: /Pewarisan Sungai/ },
            { source: inheritance.replace('return f"{self.nama}, {self.lokasi}, {self.panjang_km} km"', 'return "Sungai Barito, Banjarmasin, 25 km"'), label: /Overriding info\(\) Sungai/ },
            { source: inheritance.replace('self.luas_ha = luas_ha', 'type(self).luas_ha = luas_ha'), label: /Data instance terpisah/ },
            { source: inheritance.replace('return f"{self.nama}, {self.lokasi}, {self.luas_ha} ha"', 'return super().info()'), label: /Overriding info\(\) Rawa/ },
        ],
    },
    {
        slug: 'polimorfisme', id: 'bab5-polimorfisme-sensor', checks: 7,
        solution: sensors, output: /pH: 7.1\s+Suhu: 29.5\s+Tinggi air: 128\s+Kekeruhan: 20/,
        alternatives: [
            sensors.replace('for item in sensor:\n    print(item.status())', 'devices = sensor\nfor index, device in enumerate(devices):\n    print(device.status())'),
            sensors.replace('for item in sensor:\n    print(item.status())', 'print([device.status() for device in sensor])'),
            sensors.replace('for item in sensor:\n    print(item.status())', 'def display(device):\n    print(device.status())\nfor device in sensor:\n    display(device)'),
            sensors.replace('for item in sensor:\n    print(item.status())', 'print(type(sensor))\nfor item in sensor:\n    print(item.status())'),
            sensors.replace('class SensorSuhu:', 'class SensorSuhu(SensorPH):')
                .replace('class SensorTinggiAir:', 'class SensorTinggiAir(SensorPH):')
                .replace('class SensorKekeruhan:', 'class SensorKekeruhan(SensorPH):'),
            sensors.replace('class SensorPH:', 'class SensorPH:\n    def __init__(self, value):\n        self.value = value')
                .replace('return "pH: 7.1"', 'return f"pH: {self.value}"')
                .replace('SensorPH(),', 'SensorPH(7.1),'),
            `class Sensor:
    def status(self):
        return self.text
class SensorPH(Sensor):
    text = "pH"
class SensorSuhu(Sensor):
    text = "Suhu"
class SensorTinggiAir(Sensor):
    text = "Tinggi"
class SensorKekeruhan(Sensor):
    text = "Keruh"
sensor = [SensorPH(), SensorSuhu(), SensorTinggiAir(), SensorKekeruhan()]
for item in sensor:
    print(item.status())`,
        ],
        incorrect: [
            { source: sensors.replace('return "Kekeruhan: 20"', 'pass'), label: /implementasi status/ },
            { source: sensors.replace('return "Kekeruhan: 20"', 'return "pH: 7.1"'), label: /implementasi status/ },
            { source: sensors.replace(', SensorKekeruhan()]', ']'), label: /Empat object/ },
            { source: sensors.replace('for item in sensor:', 'if False:\n  for item in sensor:').replace('    print(item.status())', '      print(item.status())'), label: /melalui satu loop/ },
            { source: sensors.replace('for item in sensor:', 'for item in []:'), label: /melalui satu loop/ },
            { source: sensors.replace('for item in sensor:', 'for item in sensor[:1]:'), label: /melalui satu loop/ },
            { source: sensors.replace('    print(item.status())', '    if isinstance(item, SensorPH):\n        print(item.status())\n    else:\n        print(item.status())'), label: /melalui satu loop/ },
            { source: sensors.replace('    print(item.status())', '    if type(item) is SensorPH:\n        print(item.status())\n    else:\n        print(item.status())'), label: /melalui satu loop/ },
            { source: sensors.replace('    print(item.status())', '    if item.__class__.__name__ == "SensorPH":\n        print(item.status())\n    else:\n        print(item.status())'), label: /melalui satu loop/ },
            { source: sensors.replace('    print(item.status())', '    kind = type(item)\n    if kind is SensorPH:\n        print(item.status())\n    else:\n        print(item.status())'), label: /melalui satu loop/ },
        ],
    },
    {
        slug: 'kelas-abstrak', id: 'bab6-kelas-abstrak-alat-pantau', checks: 9,
        solution: abstractSensors, output: /128\s+29\.5/,
        alternatives: [
            abstractSensors.replace('return 128', 'return 0').replace('return 29.5', 'return 21.75'),
            abstractSensors.replace('return 128', 'return "Tinggi air: 128 cm"').replace('return 29.5', 'return "Suhu: 29.5 C"'),
            abstractSensors.replaceAll('sensor', 'alat_pantau')
                .replace('for item in alat_pantau:\n    print(item.baca())', 'for index, device in enumerate(alat_pantau):\n    print(device.baca())'),
            abstractSensors.replace('for item in sensor:\n    print(item.baca())', 'print([device.baca() for device in sensor])'),
            abstractSensors.replace('for item in sensor:\n    print(item.baca())', 'def tampilkan(device):\n    print(device.baca())\nfor device in sensor:\n    tampilkan(device)'),
            abstractSensors.replace('class SensorTinggiAir(AlatPantau):', 'class SensorTinggiAir(AlatPantau):\n    def __init__(self, nilai):\n        self.nilai = nilai')
                .replace('return 128', 'return self.nilai').replace('SensorTinggiAir(),', 'SensorTinggiAir(128),'),
            abstractSensors.replace('class SensorSuhu(AlatPantau):', 'class PembacaSuhu(AlatPantau):')
                .replace('sensor = [', 'class SensorSuhu(PembacaSuhu):\n    pass\n\nsensor = ['),
        ],
        incorrect: [
            { source: abstractSensors.replace('    @abstractmethod\n', ''), label: /benar-benar abstrak/ },
            { source: abstractSensors.replace('class AlatPantau(ABC):', 'class AlatPantau:').replace('    @abstractmethod\n', ''), label: /merupakan ABC/ },
            { source: abstractSensors.replace('class SensorSuhu(AlatPantau):\n    def baca(self):\n        return 29.5', 'class SensorSuhu(AlatPantau):\n    pass')
                .replace('sensor = [SensorTinggiAir(), SensorSuhu()]', 'try:\n    sensor = [SensorTinggiAir(), SensorSuhu()]\nexcept TypeError:\n    sensor = []'), label: /subclass konkret/ },
            { source: abstractSensors.replace('class SensorSuhu(AlatPantau):', 'class SensorSuhu:'), label: /SensorSuhu mewarisi/ },
            ...['None', '"   "', 'True', 'float("nan")', 'float("inf")', '128'].map((value) => ({
                source: abstractSensors.replace('return 29.5', `return ${value}`), label: /bermakna dan berbeda/,
            })),
            { source: abstractSensors.replace('sensor = [SensorTinggiAir(), SensorSuhu()]', 'sensor = [SensorTinggiAir()]'), label: /disimpan dalam satu list/ },
            { source: abstractSensors.replace('sensor = [SensorTinggiAir(), SensorSuhu()]', 'sensor = (SensorTinggiAir(), SensorSuhu())'), label: /disimpan dalam satu list/ },
            { source: abstractSensors.replace('for item in sensor:\n    print(item.baca())', 'print(sensor[0].baca())\nprint(sensor[1].baca())'), label: /melalui loop baca/ },
            { source: abstractSensors.replace('for item in sensor:', 'for item in []:'), label: /melalui loop baca/ },
            { source: abstractSensors.replace('for item in sensor:', 'for item in sensor[:1]:'), label: /melalui loop baca/ },
            { source: abstractSensors.replace('for item in sensor:\n    print(item.baca())', 'if False:\n    for item in sensor:\n        print(item.baca())'), label: /melalui loop baca/ },
            { source: abstractSensors.replace('    print(item.baca())', '    print("baca()")'), label: /melalui loop baca/ },
        ],
        runtimeErrors: [
            { source: abstractSensors.replace('        return 29.5', '        pass\n    baca = AlatPantau.baca'), error: /TypeError.*abstract/s },
        ],
    },
];
