export const codingSolutions = {
    'latihan-1': `class SensorAir:
    def __init__(self, lokasi, ph):
        self.lokasi = lokasi
        self.ph = ph

    def status_air(self):
        if 6.5 <= self.ph <= 8.5:
            return "Normal"
        return "Perlu pemeriksaan"

sensor = SensorAir("Rawa Bangkau", 7.0)
print(sensor.lokasi)
print("Status air:", sensor.status_air())`,
    'latihan-2': `class Ekosistem:
    def __init__(self, nama, lokasi):
        self.nama = nama
        self.lokasi = lokasi

    def status(self):
        return "Status belum ditentukan"

class Rawa(Ekosistem):
    def status(self):
        return "Genangan rawa dipantau"

rawa = Rawa("Rawa Bangkau", "Kalimantan Selatan")
print(rawa.status())`,
};
