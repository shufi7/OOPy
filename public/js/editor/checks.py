"""Practice feedback based on Python objects, independent of source formatting.

Executed only inside the browser's Pyodide worker. This is practice feedback,
not a trusted assessment or a persisted grade.
"""
import importlib
import inspect
import json


def check_project():
    results = []
    classes = {}

    def check(label, operation, hint):
        try:
            if not operation():
                raise AssertionError(hint)
            results.append({"label": label, "passed": True, "feedback": ""})
        except BaseException as error:
            results.append({
                "label": label,
                "passed": False,
                "feedback": f"{hint} ({type(error).__name__}: {error})",
            })

    def load_class(module_name, class_name):
        candidate = getattr(importlib.import_module(module_name), class_name)
        if not inspect.isclass(candidate):
            raise TypeError(f"{class_name} harus berupa class.")
        classes[class_name] = candidate
        return True

    for module_name, class_name in (
        ("ekosistem", "Ekosistem"), ("sungai", "Sungai"), ("rawa", "Rawa")
    ):
        check(
            f"Class {class_name} berhasil di-import dari {module_name}.py",
            lambda m=module_name, c=class_name: load_class(m, c),
            f"Periksa import dan deklarasi class {class_name} di {module_name}.py.",
        )

    check(
        "Class Sungai mewarisi Ekosistem",
        lambda: issubclass(classes["Sungai"], classes["Ekosistem"]),
        "Periksa deklarasi class Sungai(Ekosistem).",
    )
    check(
        "Class Rawa mewarisi Ekosistem",
        lambda: issubclass(classes["Rawa"], classes["Ekosistem"]),
        "Periksa deklarasi class Rawa(Ekosistem).",
    )

    def sungai():
        return classes["Sungai"]("Sungai Barito", "Banjarmasin", 900)

    def rawa():
        return classes["Rawa"]("Rawa Bangkau", "Hulu Sungai Selatan", 120)

    check(
        "Atribut nama dari kelas induk terisi dengan benar",
        lambda: sungai().nama == "Sungai Barito",
        "Panggil super().__init__(nama, lokasi) pada konstruktor Sungai.",
    )
    check(
        "Atribut lokasi dari kelas induk terisi dengan benar",
        lambda: sungai().lokasi == "Banjarmasin",
        "Pastikan konstruktor Ekosistem menyimpan self.lokasi.",
    )
    check(
        "Atribut panjang_km pada Sungai terisi dengan benar",
        lambda: sungai().panjang_km == 900,
        "Simpan panjang_km pada self.panjang_km di konstruktor Sungai.",
    )
    check(
        "Method Sungai.status() menghasilkan status yang tepat",
        lambda: sungai().status() == "Arus sungai dipantau",
        'Method status() pada Sungai harus mengembalikan "Arus sungai dipantau".',
    )
    check(
        "Method Rawa.status() menghasilkan status yang tepat",
        lambda: rawa().status() == "Genangan rawa dipantau",
        'Method status() pada Rawa harus mengembalikan "Genangan rawa dipantau".',
    )
    check(
        "Polimorfisme berjalan melalui pemanggilan status() yang sama",
        lambda: [obj.status() for obj in (sungai(), rawa())] == [
            "Arus sungai dipantau", "Genangan rawa dipantau"
        ],
        "Pastikan kedua kelas mengimplementasikan status() sesuai perilakunya.",
    )
    return json.dumps(results)


check_project()
