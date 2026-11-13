"""Only fixed, trusted test fixtures execute in these unit tests. No submitted code.
Docker integration tests are opt-in: OOPY_TEST_DOCKER=1 python -m unittest ...
"""
import os
import io
import contextlib
import json
import http.client
from pathlib import Path
import subprocess
import sys
import threading
import time
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


def wrap_program(solution, marker):
    classes, program = solution.split(marker, 1)
    return classes + 'def main():\n' + '\n'.join('    ' + line for line in (marker + program).splitlines()) + '\nmain()'


class GraderTest(unittest.TestCase):
    def passed(self, report):
        return report['status'] == 'graded' and bool(report['checks']) and all(item['passed'] for item in report['checks'] if item['required'])

    def test_solutions_and_super_constructor(self):
        self.assertTrue(self.passed(sandbox.grade(payload())))
        self.assertTrue(self.passed(sandbox.grade(payload(ECOSYSTEM, 'coding-latihan-2'))))
        alternative = ECOSYSTEM.replace('class Rawa(Ekosistem):', 'class Rawa(Ekosistem):\n    def __init__(self, nama, lokasi):\n        super().__init__(nama, lokasi)')
        self.assertTrue(self.passed(sandbox.grade(payload(alternative, 'coding-latihan-2'))))
        for key, solution, marker in [('coding-latihan-1', SENSOR, 'sensor = '),
                                      ('coding-latihan-2', ECOSYSTEM, 'rawa = ')]:
            report = sandbox.grade(payload(wrap_program(solution, marker), key))
            self.assertTrue(self.passed(report))
            self.assertTrue(report['checks'][-1]['passed'], 'Object inside main() must receive full presentation credit')

    def test_wrong_boundaries_values_and_attributes(self):
        for source in [SENSOR.replace('6.5 <= self.ph <= 8.5', '6.5 < self.ph < 8.5'),
                       SENSOR.replace('self.ph = ph', 'self.ph = 7'),
                       SENSOR.replace('Perlu pemeriksaan', 'Tidak normal'),
                       'class SensorAir:\n    pass']:
            with self.subTest(source=source[:50]):
                self.assertFalse(self.passed(sandbox.grade(payload(source))))

    def test_inheritance_and_overriding_required(self):
        for source in [ECOSYSTEM.replace('Rawa(Ekosistem)', 'Rawa'),
                       ECOSYSTEM.replace('Genangan rawa dipantau', 'Salah'),
                       ECOSYSTEM.replace('self.nama = nama', 'self.nama = "Tetap"')]:
            self.assertFalse(self.passed(sandbox.grade(payload(source, 'coding-latihan-2'))))

    def test_optional_output_and_formatting_variations(self):
        for key, solution, marker in [('coding-latihan-1', SENSOR, 'sensor = '),
                                      ('coding-latihan-2', ECOSYSTEM, 'rawa = ')]:
            for source in [solution[:solution.index(marker)],
                           solution[:solution.index('print(')] + 'print("output berbeda")',
                           solution.replace('Normal', '  nOrMaL  ').replace('Perlu pemeriksaan', ' PERLU   PEMERIKSAAN ')
                           .replace('Status air:', ' STATUS  AIR : ').replace('Rawa Bangkau', 'RAWA   BANGKAU')
                           .replace('Status belum ditentukan', ' STATUS   BELUM DITENTUKAN ')
                           .replace('Genangan rawa dipantau', ' GENANGAN   RAWA DIPANTAU ')]:
                report = sandbox.grade(payload(source, key))
                with self.subTest(key=key, source=source[-80:]):
                    self.assertTrue(self.passed(report))
                    self.assertEqual(7, len(report['checks']))
                    self.assertEqual(90, sum(c['weight'] for c in report['checks'] if c['required']))
                    self.assertTrue(all(c['feedback'] for c in report['checks']))
                    score = sum(c['weight'] for c in report['checks'] if c['passed'])
                    self.assertIn(score, [90, 100])
            no_output = sandbox.grade(payload(solution[:solution.index(marker)], key))
            self.assertFalse(no_output['checks'][-1]['passed'])
            self.assertFalse(no_output['checks'][-1]['required'])

    def test_updates_shared_attributes_print_only_and_missing_constructor_fail(self):
        cached = SENSOR.replace('self.ph = ph', 'self.ph = ph\n        self.nama = "Normal" if 6.5 <= ph <= 8.5 else "Perlu pemeriksaan"')
        cached = cached.replace('return "Normal" if 6.5 <= self.ph <= 8.5 else "Perlu pemeriksaan"', 'return self.nama')
        variants = [(cached, 'updates'),
                    (SENSOR.replace('return "Normal" if 6.5 <= self.ph <= 8.5 else "Perlu pemeriksaan"',
                                    'print("Normal" if 6.5 <= self.ph <= 8.5 else "Perlu pemeriksaan")'), 'normal'),
                    (SENSOR.replace('self.ph = ph', 'SensorAir.ph = ph'), 'attributes'),
                    ('class SensorAir:\n    lokasi = "Rawa Bangkau"\n    ph = 7', 'constructor')]
        for source, failed_id in variants:
            report = sandbox.grade(payload(source))
            self.assertFalse(self.passed(report))
            self.assertFalse(next(c for c in report['checks'] if c['id'] == failed_id)['passed'])
        inherited_status = ECOSYSTEM.replace('class Rawa(Ekosistem):\n    def status(self):\n        return "Genangan rawa dipantau"',
                                              'class Rawa(Ekosistem):\n    pass')
        self.assertFalse(self.passed(sandbox.grade(payload(inherited_status, 'coding-latihan-2'))))

    def test_local_and_official_rubrics_agree(self):
        root = Path(__file__).resolve().parents[2]
        php = subprocess.run(['php', '-d', 'display_errors=stderr', '-r',
                              "echo json_encode(require 'resources/coding/exercises.php');"],
                             cwd=root, capture_output=True, text=True, check=True)
        catalog = json.loads(php.stdout)
        for slug, solution, marker in [('latihan-1', SENSOR, 'sensor = '), ('latihan-2', ECOSYSTEM, 'rawa = ')]:
            sources = [solution, solution[:solution.index(marker)],
                       solution.replace('Normal', ' NORMAL ').replace('Genangan rawa dipantau', ' GENANGAN   RAWA DIPANTAU '),
                       solution.replace('self.lokasi = lokasi', 'self.lokasi = "Tetap"'),
                       solution.replace('6.5 <= self.ph <= 8.5', '6.5 < self.ph < 8.5'),
                       solution[:solution.index(marker)].replace('Rawa(Ekosistem)', 'Rawa'),
                       solution.replace('return "Normal"', 'return "Salah"'),
                       'results = [{"passed": True}]; score = 100; passed = True']
            for source in sources:
                namespace = {'__name__': '__main__'}
                output = io.StringIO()
                with contextlib.redirect_stdout(output):
                    exec(source, namespace)
                namespace['__oopy_output__'] = output.getvalue()
                namespace['__oopy_modules__'] = {}
                namespace['__oopy_execution__'] = []
                if slug == 'latihan-2':
                    namespace['results'] = sandbox.run_checks(namespace, output.getvalue(), 'coding-latihan-2')
                else:
                    exec(catalog[slug]['checker'], namespace)
                official = sandbox.grade(payload(source, 'coding-' + slug))
                with self.subTest(slug=slug, source=source[-40:]):
                    self.assertEqual('graded', official['status'])
                    self.assertEqual([(c['id'], c['passed'], c['required'], c['weight']) for c in official['checks']],
                                     [(c['id'], c['passed'], c['required'], c['weight']) for c in namespace['results']])

    def test_tampering_and_system_access_rejected_before_execution(self):
        for source in ['import os', 'open("/etc/passwd")', 'globals()',
                       'print((1).__class__)', 'print("{0.__class__}".format(1))',
                       'print = lambda *a: None', 'f = (x for x in []); print(f.gi_frame)',
                       'def x():\n    global print\n    print = 1',
                       'match 1:\n    case object(__class__=klass):\n        pass',
                       'match {}:\n    case {**super}:\n        pass']:
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

    def test_unicode_transport_and_request_byte_limit(self):
        request = payload(SENSOR + '\n#' + chr(0xe9) * 18000)
        encoded = json.dumps(request).encode('utf-8')
        self.assertTrue(server.valid_payload(request))
        self.assertGreater(len(encoded), 65000)
        self.assertEqual(request, sandbox.read_request(io.BytesIO(encoded)))
        with self.assertRaises(ValueError):
            sandbox.read_request(io.BytesIO(b' ' * 200001))

    def test_malformed_container_response_fails_closed(self):
        for response in ['[]', 'null', '{}', '{invalid']:
            with self.subTest(response=response), patch.object(
                    server.subprocess, 'run', return_value=subprocess.CompletedProcess([], 0, response, '')):
                with self.assertRaises((ValueError, RuntimeError)):
                    server.run_sandbox(payload())

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

    @unittest.skipUnless(os.environ.get('OOPY_TEST_DOCKER') == '1', 'Docker integration explicitly opt-in')
    def test_real_container_variants_unicode_wrong_syntax_and_output_limit(self):
        alternative = SENSOR.replace('return "Normal" if 6.5 <= self.ph <= 8.5 else "Perlu pemeriksaan"',
                                     'return "Perlu pemeriksaan" if self.ph < 6.5 or self.ph > 8.5 else "Normal"')
        inherited = ECOSYSTEM.replace('class Rawa(Ekosistem):',
                                     'class Rawa(Ekosistem):\n    def __init__(self, nama, lokasi):\n        super().__init__(nama, lokasi)')
        for request in [payload(alternative), payload(inherited, 'coding-latihan-2'),
                        payload(wrap_program(SENSOR, 'sensor = ')),
                        payload(wrap_program(ECOSYSTEM, 'rawa = '), 'coding-latihan-2'),
                        payload(SENSOR + '\n#' + chr(0xe9) * 18000)]:
            report = server.run_sandbox(request)
            self.assertTrue(self.passed(report))
            self.assertTrue(all(c['passed'] for c in report['checks']))
        for request in [payload(SENSOR.replace('6.5 <= self.ph <= 8.5', '6.5 < self.ph < 8.5')),
                        payload(ECOSYSTEM.replace('Rawa(Ekosistem)', 'Rawa'), 'coding-latihan-2'),
                        payload('if True print(1)')]:
            self.assertFalse(self.passed(server.run_sandbox(request)))
        report = server.run_sandbox(payload('print("x" * 200000)'))
        self.assertFalse(self.passed(report))
        self.assertLessEqual(len(report['output']), 12000)
        started = time.monotonic()
        self.assertEqual('error', server.run_sandbox(payload('while True: print("x" * 1000)'))['status'])
        self.assertLess(time.monotonic() - started, 12)
        remaining = subprocess.run(['docker', 'ps', '-a', '--filter', 'name=oopy-grade-', '--format', '{{.Names}}'],
                                   text=True, capture_output=True, check=True)
        self.assertEqual('', remaining.stdout.strip())


MULTIFILE = json.loads((Path(__file__).parents[2] / 'tests/Fixtures/coding-multifile.json').read_text())
MULTIFILE_KEY = 'coding-latihan-2-multifile-v1'


class MultiFileTest(unittest.TestCase):
    def report(self, files=None):
        return sandbox.grade({'exercise_key': MULTIFILE_KEY, 'files': MULTIFILE if files is None else files})

    def test_super_initializer_is_required_for_each_constructor_case(self):
        conditional = MULTIFILE['rawa.py'].replace(
            'super().__init__(nama, lokasi)',
            'if tinggi_air_cm == 81.25:\n            super().__init__(nama, lokasi)\n'
            '        else:\n            self.nama = nama\n            self.lokasi = lokasi')
        report = self.report(MULTIFILE | {'rawa.py': conditional})
        self.assertEqual('graded', report['status'])
        self.assertTrue(report['checks'][1]['passed'])
        self.assertFalse(next(c for c in report['checks'] if c['id'] == 'child_constructor')['passed'])
        self.assertEqual(85, sum(c['weight'] for c in report['checks'] if c['passed']))

    def test_super_must_call_ekosistem_initializer_rather_than_object(self):
        bypass = MULTIFILE['rawa.py'].replace('super().__init__(nama, lokasi)',
            'self.nama = nama\n        self.lokasi = lokasi\n        super(Ekosistem, self).__init__()')
        report = self.report(MULTIFILE | {'rawa.py': bypass})
        self.assertEqual('graded', report['status'])
        self.assertFalse(next(c for c in report['checks'] if c['id'] == 'child_constructor')['passed'])
        self.assertEqual(85, sum(c['weight'] for c in report['checks'] if c['passed']))

    def test_additional_valid_imports_constructor_styles_and_fresh_modules(self):
        variants = [
            MULTIFILE | {'rawa.py': MULTIFILE['rawa.py'].replace(
                'from ekosistem import Ekosistem', 'from ekosistem import Ekosistem as Induk')
                .replace('Rawa(Ekosistem)', 'Rawa(Induk)')},
            MULTIFILE | {'main.py': MULTIFILE['main.py'].replace(
                'from rawa import Rawa', 'from rawa import Rawa as Habitat').replace('rawa = Rawa(', 'rawa = Habitat(')},
            MULTIFILE | {'rawa.py': MULTIFILE['rawa.py'].replace(
                'super().__init__(nama, lokasi)', 'super().__init__(lokasi=lokasi, nama=nama)')},
            MULTIFILE | {'ekosistem.py': MULTIFILE['ekosistem.py'].replace(
                'self.nama = nama\n        self.lokasi = lokasi', 'self.nama, self.lokasi = nama, lokasi')},
            MULTIFILE | {'main.py': 'def main():\n' + '\n'.join(
                '    ' + line for line in MULTIFILE['main.py'].splitlines()) + '\nif __name__ == "__main__":\n    main()'},
        ]
        for files in variants:
            with self.subTest(files=files):
                self.assertTrue(all(c['passed'] for c in self.report(files)['checks']))
        wrong = MULTIFILE | {'rawa.py': MULTIFILE['rawa.py'].replace('return "Siaga"', 'return "Salah"')}
        self.assertFalse(self.report(wrong)['checks'][5]['passed'])
        self.assertTrue(all(c['passed'] for c in self.report()['checks']), 'Previous modules must not be reused')

    def test_cached_status_print_only_and_wrong_classes_do_not_pass(self):
        cached = MULTIFILE['rawa.py'].replace(
            'self.tinggi_air_cm = tinggi_air_cm',
            'self.tinggi_air_cm = tinggi_air_cm\n        self.ph = "Siaga" if tinggi_air_cm >= 80 else "Normal"')
        cached = cached[:cached.index('    def status(self):')] + '    def status(self):\n        return self.ph\n'
        variants = [
            (MULTIFILE | {'rawa.py': cached}, 'overriding'),
            (MULTIFILE | {'main.py': 'print("Rawa Bangkau - Hulu Sungai Selatan\\nStatus air: Siaga")'}, 'modules'),
            (MULTIFILE | {'ekosistem.py': MULTIFILE['ekosistem.py'].replace('Ekosistem', 'Habitat'),
                          'rawa.py': MULTIFILE['rawa.py'].replace('from ekosistem import Ekosistem',
                              'import ekosistem').replace('Rawa(Ekosistem)', 'Rawa(object)').replace(
                                  'super().__init__(nama, lokasi)', 'self.nama = nama\n        self.lokasi = lokasi')}, 'inheritance'),
        ]
        for files, failed in variants:
            with self.subTest(failed=failed):
                report = self.report(files)
                self.assertEqual('graded', report['status'])
                self.assertFalse(next(c for c in report['checks'] if c['id'] == failed)['passed'])
                self.assertFalse(all(c['passed'] for c in report['checks'] if c['required']))

    def test_security_surface_is_rejected_without_running_payloads(self):
        for name in ('open', 'eval', 'exec', 'compile', 'globals', 'locals', 'getattr', 'setattr', '__import__'):
            self.assertNotIn(name, sandbox.SAFE_BUILTINS)
        for source in ('import os', 'import subprocess', 'from rawa import Rawa\nRawa.__dict__',
                       'from rawa import Rawa\nRawa.__init__.__globals__',
                       '__builtins__["open"]("fixture")', 'object.__subclasses__()'):
            with self.subTest(source=source), self.assertRaises(ValueError):
                sandbox.validate_source(source, 'main.py', MULTIFILE_KEY)

    def test_reference_aliases_explicit_super_and_wrapped_main(self):
        variants = [MULTIFILE,
                    MULTIFILE | {'rawa.py': MULTIFILE['rawa.py'].replace('super()', 'super(Rawa, self)')},
                    MULTIFILE | {'rawa.py': MULTIFILE['rawa.py'].replace('from ekosistem import Ekosistem', 'import ekosistem as eco').replace('Rawa(Ekosistem)', 'Rawa(eco.Ekosistem)')},
                    MULTIFILE | {'main.py': MULTIFILE['main.py'].replace('from rawa import Rawa', 'import rawa as modul').replace('rawa = Rawa(', 'rawa = modul.Rawa(')},
                    MULTIFILE | {'main.py': 'def main():\n' + '\n'.join('    ' + line for line in MULTIFILE['main.py'].splitlines()) + '\nmain()'}]
        for files in variants:
            with self.subTest(files=files):
                report = self.report(files)
                self.assertEqual('graded', report['status'], report)
                self.assertEqual(['modules', 'constructor', 'parent_status', 'inheritance', 'child_constructor', 'overriding', 'presentation'],
                                 [c['id'] for c in report['checks']])
                self.assertTrue(all(c['passed'] for c in report['checks']), report)
                self.assertEqual(100, sum(c['weight'] for c in report['checks'] if c['passed']))

    def test_behavior_boundaries_mutation_and_actual_super_call(self):
        variants = [
            ('rawa.py', 'self.tinggi_air_cm >= 80', 'self.tinggi_air_cm > 80', 'overriding'),
            ('rawa.py', 'self.tinggi_air_cm >= 80', 'True', 'overriding'),
            ('rawa.py', 'super().__init__(nama, lokasi)', 'Ekosistem.__init__(self, nama, lokasi)', 'child_constructor'),
            ('rawa.py', 'super().__init__(nama, lokasi)', 'self.nama = nama\n        self.lokasi = lokasi\n        if False:\n            super().__init__(nama, lokasi)', 'child_constructor'),
            ('ekosistem.py', 'self.nama = nama', 'self.nama = "Tetap"', 'constructor'),
            ('ekosistem.py', 'Belum dipantau', 'Status lama', 'parent_status'),
        ]
        for name, old, new, failed in variants:
            with self.subTest(failed=failed, new=new):
                report = self.report(MULTIFILE | {name: MULTIFILE[name].replace(old, new)})
                self.assertEqual('graded', report['status'], report)
                self.assertFalse(next(c for c in report['checks'] if c['id'] == failed)['passed'])
        no_override = MULTIFILE['rawa.py'].split('    def status(self):')[0]
        self.assertFalse(next(c for c in self.report(MULTIFILE | {'rawa.py': no_override})['checks'] if c['id'] == 'overriding')['passed'])
        unrelated = MULTIFILE['rawa.py'].replace('class Rawa(Ekosistem):', 'class Rawa:').replace('super().__init__(nama, lokasi)', 'self.nama = nama\n        self.lokasi = lokasi')
        report = self.report(MULTIFILE | {'rawa.py': unrelated})
        self.assertFalse(next(c for c in report['checks'] if c['id'] == 'inheritance')['passed'])

    def test_modular_contract_output_bonus_and_forged_results(self):
        combined = MULTIFILE['ekosistem.py'] + MULTIFILE['rawa.py'].replace('from ekosistem import Ekosistem', '') + MULTIFILE['main.py'].replace('from rawa import Rawa', '')
        report = self.report({'ekosistem.py': 'pass', 'rawa.py': 'pass', 'main.py': combined})
        self.assertFalse(report['checks'][0]['passed'])
        report = self.report(MULTIFILE | {'main.py': 'from rawa import Rawa'})
        self.assertTrue(all(c['passed'] for c in report['checks'] if c['required']))
        self.assertFalse(report['checks'][-1]['passed'])
        self.assertEqual(90, sum(c['weight'] for c in report['checks'] if c['passed']))
        fake = 'from rawa import Rawa\nprint("Rawa Bangkau - Hulu Sungai Selatan\\nStatus air: Siaga")\nresults = [{"passed": True}]; score=100; passed=True; run_checks=1; type=1; vars=1'
        report = self.report(MULTIFILE | {'main.py': fake})
        self.assertEqual('graded', report['status'])
        self.assertFalse(report['checks'][-1]['passed'])
        self.assertEqual(7, len(report['checks']))

    def test_forbidden_imports_in_every_file_rejected_before_execution(self):
        for name, code in [
            ('ekosistem.py', 'import os'), ('rawa.py', 'import sys'), ('main.py', 'import subprocess'),
            ('main.py', 'from rawa import *'), ('main.py', 'from .rawa import Rawa'),
            ('main.py', 'from rawa import Rawa as print'), ('main.py', 'from rawa import __dict__'),
            ('main.py', 'import ekosistem.extra'), ('ekosistem.py', 'from rawa import Rawa'),
            ('main.py', 'from rawa import Rawa as __builtins__'),
        ]:
            with self.subTest(name=name, code=code):
                report = self.report(MULTIFILE | {name: code + '\n' + MULTIFILE[name]})
                self.assertEqual('error', report['status'])
                self.assertIn('import', report['output'])
                self.assertNotIn('Status air: Siaga', report['output'])
        self.assertEqual('error', sandbox.grade(payload('import ekosistem'))['status'])
        for source in ['from rawa import Rawa\nprint(Rawa.__mro__)', 'from rawa import Rawa\nopen("/etc/passwd")']:
            self.assertEqual('error', self.report(MULTIFILE | {'main.py': source})['status'])

    def test_files_shape_size_and_error_location(self):
        for files in [dict(list(MULTIFILE.items())[1:]), MULTIFILE | {'../x.py': 'pass'}, MULTIFILE | {'/main.py': 'pass'},
                      MULTIFILE | {'rawa.py': []}, MULTIFILE | {'rawa.py': ' \n\t'},
                      MULTIFILE | {'rawa.py': '#' + 'x' * 30000},
                      {name: '#' + 'x' * 11000 for name in MULTIFILE}]:
            request = {'exercise_key': MULTIFILE_KEY, 'files': files}
            self.assertFalse(server.valid_payload(request))
            self.assertEqual('error', sandbox.grade(request)['status'])
        report = self.report(MULTIFILE | {'rawa.py': 'class Broken(:'})
        self.assertIn('rawa.py', report['output'])
        report = self.report(MULTIFILE | {'rawa.py': 'raise TypeError("fixture")'})
        self.assertIn('rawa.py', report['output'])

    def test_local_official_parity_for_trusted_multifile_fixtures(self):
        root = Path(__file__).parents[2]
        config = json.loads(subprocess.check_output(['php', '-r', "echo json_encode((require 'resources/coding/exercises.php')['latihan-2']);"], cwd=root))
        variants = [MULTIFILE, MULTIFILE | {'rawa.py': MULTIFILE['rawa.py'].replace('>= 80', '> 80')},
                    MULTIFILE | {'main.py': 'from rawa import Rawa'},
                    MULTIFILE | {'rawa.py': MULTIFILE['rawa.py'].replace('super().__init__(nama, lokasi)',
                        'if tinggi_air_cm == 81.25:\n            super().__init__(nama, lokasi)\n'
                        '        else:\n            self.nama = nama\n            self.lokasi = lokasi')},
                    MULTIFILE | {'rawa.py': MULTIFILE['rawa.py'].replace('super().__init__(nama, lokasi)',
                        'self.nama = nama\n        self.lokasi = lokasi\n        super(Ekosistem, self).__init__()')},
                    MULTIFILE | {'rawa.py': MULTIFILE['rawa.py'].replace('super().__init__(nama, lokasi)', 'Ekosistem.__init__(self, nama, lokasi)')}]
        reports = json.loads(subprocess.check_output([sys.executable, 'tests/js/checker-harness.py'],
                            input=json.dumps({'checker': config['checker'], 'sources': variants}).encode(), cwd=root))
        for files, local in zip(variants, reports):
            self.assertNotIn('error', local, local)
            self.assertTrue(local['super_restored'])
            official = self.report(files)
            self.assertEqual([(c['id'], c['passed'], c['weight'], c['required']) for c in official['checks']],
                             [(c['id'], c['passed'], c['weight'], c['required']) for c in local['results']])

    @unittest.skipUnless(os.environ.get('OOPY_TEST_DOCKER') == '1', 'Docker integration explicitly opt-in')
    def test_real_multifile_container(self):
        report = server.run_sandbox({'exercise_key': MULTIFILE_KEY, 'files': MULTIFILE})
        self.assertEqual('graded', report['status'])
        self.assertTrue(all(c['passed'] for c in report['checks']))
        self.assertEqual('error', server.run_sandbox({'exercise_key': MULTIFILE_KEY, 'files': MULTIFILE | {'rawa.py': 'import os'}})['status'])

    @unittest.skipUnless(os.environ.get('OOPY_TEST_DOCKER') == '1', 'Docker integration explicitly opt-in')
    def test_real_multifile_partial_resource_limits_and_recovery(self):
        for replacement in [
            'if tinggi_air_cm == 81.25:\n            super().__init__(nama, lokasi)\n'
            '        else:\n            self.nama = nama\n            self.lokasi = lokasi',
            'self.nama = nama\n        self.lokasi = lokasi\n        super(Ekosistem, self).__init__()',
        ]:
            files = MULTIFILE | {'rawa.py': MULTIFILE['rawa.py'].replace('super().__init__(nama, lokasi)', replacement)}
            report = server.run_sandbox({'exercise_key': MULTIFILE_KEY, 'files': files})
            self.assertEqual('graded', report['status'])
            self.assertEqual(85, sum(c['weight'] for c in report['checks'] if c['passed']))
        for main in ('while True: pass', 'data = [0] * 100000000', 'print("x" * 1000)\nwhile True: pass'):
            with self.subTest(main=main):
                started = time.monotonic()
                report = server.run_sandbox({'exercise_key': MULTIFILE_KEY, 'files': MULTIFILE | {'main.py': main}})
                self.assertEqual('error', report['status'])
                self.assertLess(time.monotonic() - started, 12)
        report = server.run_sandbox({'exercise_key': MULTIFILE_KEY, 'files': MULTIFILE})
        self.assertTrue(all(c['passed'] for c in report['checks']))


class GatewayHttpTest(unittest.TestCase):
    def setUp(self):
        self.httpd = server.ThreadingHTTPServer(('127.0.0.1', 0), server.Handler)
        self.thread = threading.Thread(target=self.httpd.serve_forever, daemon=True)
        self.thread.start()
        self.addCleanup(self.httpd.server_close)
        self.addCleanup(self.httpd.shutdown)
        self.secret = 'http-test-token-' + 't' * 48
        self.token_patch = patch.object(server, 'TOKEN', self.secret)
        self.token_patch.start()
        self.addCleanup(self.token_patch.stop)
        self.log_patch = patch.object(server.Handler, 'log_message')
        self.log_patch.start()
        self.addCleanup(self.log_patch.stop)

    def post(self, body=None, token=None, path='/grade', headers=None):
        connection = http.client.HTTPConnection('127.0.0.1', self.httpd.server_port, timeout=3)
        self.addCleanup(connection.close)
        request_headers = {'Content-Type': 'application/json', **(headers or {})}
        if token is not None:
            request_headers['Authorization'] = 'Bearer ' + token
        connection.request('POST', path, json.dumps(payload()) if body is None else body, request_headers)
        response = connection.getresponse()
        return response.status, json.loads(response.read())

    def test_http_authentication_rejects_missing_and_wrong_token(self):
        with patch.object(server, 'run_sandbox') as run:
            self.assertEqual(401, self.post()[0])
            self.assertEqual(401, self.post(token='wrong')[0])
            run.assert_not_called()

    def test_http_payload_size_and_path_validation(self):
        with patch.object(server, 'run_sandbox') as run:
            self.assertEqual(404, self.post(token=self.secret, path='/unknown')[0])
            self.assertEqual(422, self.post(body='{invalid', token=self.secret)[0])
            self.assertEqual(422, self.post(body=json.dumps(payload('x' * 30001)), token=self.secret)[0])
            self.assertEqual(413, self.post(body='{}', token=self.secret, headers={'Content-Length': '200001'})[0])
            run.assert_not_called()

    def test_http_authenticated_success_and_gateway_failure(self):
        expected = {'version': 2, 'exercise_key': 'coding-latihan-1', 'status': 'graded',
                    'checks': [{'label': 'Fixture', 'passed': True, 'feedback': ''}], 'output': ''}
        with patch.object(server, 'run_sandbox', return_value=expected) as run:
            self.assertEqual((200, expected), self.post(token=self.secret))
            run.assert_called_once_with(payload())
        with patch.object(server, 'run_sandbox', side_effect=OSError('Docker unavailable')):
            self.assertEqual(503, self.post(token=self.secret)[0])
        with patch.object(server, 'SLOTS') as slots, patch.object(server, 'run_sandbox') as run:
            slots.acquire.return_value = False
            self.assertEqual(503, self.post(token=self.secret)[0])
            run.assert_not_called()
            slots.release.assert_not_called()

    def test_http_multifile_invalid_requests_do_not_break_next_submission(self):
        valid = {'exercise_key': MULTIFILE_KEY, 'files': MULTIFILE}
        cases = [valid | {'score': 100}, valid | {'passed': True}, valid | {'results': []},
                 valid | {'files': list(MULTIFILE.values())},
                 valid | {'files': MULTIFILE | {'../rawa.py': 'pass'}},
                 valid | {'files': MULTIFILE | {'rawa.py': ['pass']}},
                 valid | {'files': MULTIFILE | {'rawa.py': 'x' * 30001}},
                 valid | {'files': MULTIFILE | {'rawa.py': '\ud800'}}]
        with patch.object(server, 'run_sandbox') as run:
            for request in cases:
                with self.subTest(request=request):
                    self.assertEqual(422, self.post(body=json.dumps(request), token=self.secret)[0])
            run.assert_not_called()
            expected = server.error_report(MULTIFILE_KEY, 'Controlled sandbox fixture')
            run.return_value = expected
            self.assertEqual((200, expected), self.post(body=json.dumps(valid), token=self.secret))
            run.assert_called_once_with(valid)


if __name__ == '__main__':
    unittest.main()
