"""Execute trusted bank fixtures only, never HTTP/student-supplied code."""
import contextlib
import io
import json
import os
from pathlib import Path
import re
import subprocess
import sys
import unittest

ROOT = Path(__file__).resolve().parents[1]
BANK = json.loads(subprocess.check_output([
    os.environ.get("OOPY_PHP", "php"), "-r",
    "echo json_encode((require 'resources/materi/evaluasi-akhir.php')['questions']);",
], cwd=ROOT, text=True))
CASES = json.loads((ROOT / "tests/Fixtures/final-exam-cases.json").read_text())


def run(source):
    namespace = {}
    output = io.StringIO()
    with contextlib.redirect_stdout(output):
        exec(compile(source, "<trusted-final-exam-fixture>", "exec"), namespace)
    return output.getvalue(), namespace


def complete(question, answer):
    return re.sub(r"_{3,}", lambda _: answer.strip(), question["code"], count=1)


class FinalExamPythonTest(unittest.TestCase):
    def test_bank_shape_and_all_reference_code_solutions(self):
        self.assertEqual(len(BANK), 20)
        self.assertEqual([q["type"] for q in BANK], ["multiple_choice"] * 10 + ["code_fill"] * 10)
        for question in BANK[10:]:
            case = CASES[question["id"]]
            self.assertEqual(len(re.findall(r"_{3,}", question["code"])), 1)
            for answer in [question["answer"], *case["valid"]]:
                with self.subTest(id=question["id"], answer=answer):
                    output, _ = run(complete(question, answer))
                    self.assertEqual(output, case["output"])

    def test_multiple_choice_code_and_single_correct_option(self):
        for question in BANK[:10]:
            self.assertEqual(len(question["options"]), 4)
            self.assertEqual(len(set(question["options"])), 4)
            self.assertIn(question["correct"], range(4))
            self.assertTrue(question["explanation"])
        self.assertEqual(run(BANK[0]["code"])[0], BANK[0]["options"][BANK[0]["correct"]] + "\n")
        successes = []
        for option in BANK[1]["options"]:
            _, ns = run(BANK[1]["code"])
            try:
                result = eval(compile(option, "<trusted-choice>", "eval"), ns)
                successes.append(result == 75 and ns["sensor"].nilai == 75)
            except TypeError:
                successes.append(False)
        self.assertEqual(successes, [False, False, False, True])
        successes = []
        for option in BANK[3]["options"]:
            try:
                _, ns = run(complete(BANK[3], option) + '\ns = Sungai("Barito", 5)')
                successes.append(ns["s"].nama == "Barito" and ns["s"].panjang == 5)
            except (TypeError, AttributeError):
                successes.append(False)
        self.assertEqual(successes, [False, False, True, False])
        self.assertEqual(run(BANK[4]["code"])[0], "7.2\n")
        with self.assertRaises(TypeError):
            run(BANK[7]["code"])
        self.assertEqual(run(BANK[8]["code"])[0], "85\n")

    def test_answers_generalize_beyond_the_displayed_example(self):
        for q in BANK[10:]:
            _, ns = run(complete(q, q["answer"]))
            key = q["id"]
            if key == "q11":
                a, b = ns["Sensor"](0), ns["Sensor"](135)
                a.nilai = 12
                self.assertEqual((a.nilai, b.nilai), (12, 135))
            elif key == "q12":
                self.assertEqual(ns["SensorPH"](6.5).ph, 6.5)
            elif key == "q13":
                s = ns["Sungai"]("Martapura", "Banjarmasin", 9)
                self.assertEqual((s.nama, s.lokasi, s.aliran), ("Martapura", "Banjarmasin", 9))
            elif key == "q15":
                self.assertTrue(issubclass(ns["SensorPH"], ns["SensorLingkungan"]))
                with self.assertRaises(TypeError):
                    ns["SensorLingkungan"]()
            elif key == "q16":
                sensor = ns["Sensor"]()
                self.assertIs(ns["Stasiun"](sensor).sensor, sensor)
            elif key == "q17":
                s = ns["Sensor"](45)
                self.assertEqual(s.prediksi(-5), 40)
                self.assertEqual(s.nilai, 45)
            elif key == "q18":
                self.assertAlmostEqual(ns["SensorPH"](6.9, -0.1).baca_data(), 6.8)
            elif key == "q19":
                s = ns["SensorPH"](0)
                s.ph = 14
                self.assertEqual(s.ph, 14)
                for value in [-1, 15]:
                    with self.assertRaises(ValueError):
                        s.ph = value
                    self.assertEqual(s.ph, 14)
            elif key == "q20":
                self.assertNotIn("sumber", ns["SensorPH"].__dict__)
                self.assertEqual(ns["SensorPH"]().sumber(), "OOPy")


if __name__ == "__main__":
    unittest.main(testRunner=unittest.TextTestRunner(stream=sys.stdout, verbosity=2))
