export const starterFiles = Object.freeze({
    'ekosistem.py': `class Ekosistem:
    def __init__(self, nama, lokasi):
        self.nama = nama
        self.lokasi = lokasi

    def info(self):
        return f"{self.nama} - {self.lokasi}"

    def status(self):
        return "Status ekosistem belum ditentukan"
`,
    'sungai.py': `from ekosistem import Ekosistem


class Sungai(Ekosistem):
    def __init__(self, nama, lokasi, panjang_km):
        super().__init__(nama, lokasi)
        self.panjang_km = panjang_km

    def status(self):
        return "Arus sungai dipantau"
`,
    'rawa.py': `from ekosistem import Ekosistem


class Rawa(Ekosistem):
    def __init__(self, nama, lokasi, luas_hektar):
        super().__init__(nama, lokasi)
        self.luas_hektar = luas_hektar

    def status(self):
        return "Genangan rawa dipantau"
`,
    'main.py': `from sungai import Sungai
from rawa import Rawa


sungai = Sungai(
    "Sungai Barito",
    "Banjarmasin",
    900
)

rawa = Rawa(
    "Rawa Bangkau",
    "Hulu Sungai Selatan",
    120
)

ekosistem = [sungai, rawa]

for objek in ekosistem:
    print(objek.info())
    print("Status:", objek.status())
    print()
`,
});
