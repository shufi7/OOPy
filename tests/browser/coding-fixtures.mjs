import { readFileSync } from 'node:fs';

export const codingKeys = { 'latihan-1': 'coding-latihan-1', 'latihan-2': 'coding-latihan-2-multifile-v1' };

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
    'latihan-2': JSON.parse(readFileSync(new URL('../Fixtures/coding-multifile.json', import.meta.url), 'utf8')),
};
