"""Only fixed, trusted test fixtures execute in these unit tests. No submitted code.
Docker integration tests are opt-in: OOPY_TEST_DOCKER=1 python -m unittest ...
"""
import os
from pathlib import Path
import subprocess
import sys
import unittest
from unittest.mock import patch

sys.path.insert(0, str(Path(__file__).parent))
import sandbox
import server

SENSOR = '''class SensorAir:
    def __init__(self, lokasi, ph):
        self.lokasi = lokasi
        self.ph = ph
    def status_air(self):
        return "Normal" if 6.5 <= self.ph <= 8.5 else "Perlu pemeriksaan"
sensor = SensorAir("Rawa Bangkau", 7)
print(sensor.lokasi)
print("Status air:", sensor.status_air())
'''
ECOSYSTEM = '''class Ekosistem:
    def __init__(self, nama, lokasi):
        self.nama = nama
        self.lokasi = lokasi
    def status(self):
        return "Status belum ditentukan"
class Rawa(Ekosistem):
    def status(self):
        return "Genangan rawa dipantau"
rawa = Rawa("Rawa Bangkau", "Kalimantan Selatan")
print(rawa.status())
'''


def payload(source=SENSOR, key='coding-latihan-1'):
    return {'exercise_key': key, 'files': {'main.py': source}}


class GraderTest(unittest.TestCase):
    def passed(self, report):
        return report['status'] == 'graded' and bool(report['checks']) and all(item['passed'] for item in report['checks'])

    def test_solutions_and_super_constructor(self):
        self.assertTrue(self.passed(sandbox.grade(payload())))
        self.assertTrue(self.passed(sandbox.grade(payload(ECOSYSTEM, 'coding-latihan-2'))))
        alternative = ECOSYSTEM.replace('class Rawa(Ekosistem):', 'class Rawa(Ekosistem):\n    def __init__(self, nama, lokasi):\n        super().__init__(nama, lokasi)')
        self.assertTrue(self.passed(sandbox.grade(payload(alternative, 'coding-latihan-2'))))

    def test_wrong_boundaries_values_attributes_and_missing_output(self):
        for source in [SENSOR.replace('6.5 <= self.ph <= 8.5', '6.5 < self.ph < 8.5'),
                       SENSOR.replace('self.ph = ph', 'self.ph = 7'),
                       SENSOR.replace('Perlu pemeriksaan', 'Tidak normal'),
                       SENSOR[:SENSOR.index('sensor = ')], 'class SensorAir:\n    pass']:
            with self.subTest(source=source[:50]):
                self.assertFalse(self.passed(sandbox.grade(payload(source))))

    def test_inheritance_and_overriding_required(self):
        for source in [ECOSYSTEM.replace('Rawa(Ekosistem)', 'Rawa'),
                       ECOSYSTEM.replace('Genangan rawa dipantau', 'Salah'),
                       ECOSYSTEM.replace('self.nama = nama', 'self.nama = "Tetap"')]:
            self.assertFalse(self.passed(sandbox.grade(payload(source, 'coding-latihan-2'))))

    def test_tampering_and_system_access_rejected_before_execution(self):
        for source in ['import os', 'open("/etc/passwd")', 'globals()',
                       'print((1).__class__)', 'print("{0.__class__}".format(1))',
                       'print = lambda *a: None', 'f = (x for x in []); print(f.gi_frame)',
                       'def x():\n    global print\n    print = 1']:
            self.assertFalse(self.passed(sandbox.grade(payload(source))))

    def test_syntax_runtime_error_and_forged_results_cannot_pass(self):
        self.assertFalse(self.passed(sandbox.grade(payload('if True print(1)'))))
        self.assertEqual('error', sandbox.grade(payload('raise ValueError("test")'))['status'])
        self.assertFalse(self.passed(sandbox.grade(payload('results = [{"passed": True}]; passed = True; score = 100'))))

    def test_gateway_payload_validation(self):
        self.assertTrue(server.valid_payload(payload()))
        for request in [{}, payload('', 'unknown'), {'exercise_key': 'coding-latihan-1', 'files': {'../main.py': 'pass'}},
                        payload('a' * 30001), payload() | {'passed': True}]:
            self.assertFalse(server.valid_payload(request))

    def test_docker_limits_timeout_cleanup_and_unavailable_fail_closed(self):
        calls = []
        def timeout_run(command, **kwargs):
            calls.append(command)
            if command[1] == 'run':
                raise subprocess.TimeoutExpired(command, 8)
            return subprocess.CompletedProcess(command, 0)
        with patch.object(server.subprocess, 'run', timeout_run):
            report = server.run_sandbox(payload())
        self.assertEqual('error', report['status'])
        for flag in ['--network=none', '--read-only', '--cap-drop=ALL', '--memory=128m', '--cpus=0.5', '--pids-limit=16', '--security-opt=no-new-privileges']:
            self.assertIn(flag, calls[0])
        self.assertEqual(['docker', 'rm', '-f'], calls[1][:3])
        with patch.object(server.subprocess, 'run', return_value=subprocess.CompletedProcess([], 125, '', 'unavailable')):
            with self.assertRaises(RuntimeError):
                server.run_sandbox(payload())

    @unittest.skipUnless(os.environ.get('OOPY_TEST_DOCKER') == '1', 'Docker integration explicitly opt-in')
    def test_real_container_pass_error_timeout_and_system_access(self):
        self.assertTrue(self.passed(server.run_sandbox(payload())))
        self.assertTrue(self.passed(server.run_sandbox(payload(ECOSYSTEM, 'coding-latihan-2'))))
        self.assertFalse(self.passed(server.run_sandbox(payload('import os'))))
        self.assertEqual('error', server.run_sandbox(payload('while True: pass'))['status'])
        self.assertEqual('error', server.run_sandbox(payload('raise ValueError("test")'))['status'])


if __name__ == '__main__':
    unittest.main()
