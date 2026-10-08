// Explicit expectations: availability changes must update this contract.
export const chapters = [
    { slug: 'dasar-pemrograman-oop', previous: null, next: 'kelas-dan-objek' },
    { slug: 'kelas-dan-objek', previous: 'dasar-pemrograman-oop', next: 'enkapsulasi' },
    { slug: 'enkapsulasi', previous: 'kelas-dan-objek', next: 'pewarisan' },
    { slug: 'pewarisan', previous: 'enkapsulasi', next: 'polimorfisme' },
    { slug: 'polimorfisme', previous: 'pewarisan', next: null },
];

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
];
