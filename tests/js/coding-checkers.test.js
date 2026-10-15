import { test } from 'node:test';
import assert from 'node:assert/strict';
import { spawnSync } from 'node:child_process';
import { codingSolutions } from '../browser/coding-fixtures.mjs';

for (const [slug, solution] of Object.entries(codingSolutions)) {
    test(`Coding ${slug}: blank catalog, incorrect solution, and different valid implementations`, () => {
        const php = spawnSync('php', ['-d', 'display_errors=stderr', '-r', `$all = require 'resources/coding/exercises.php'; echo json_encode($all['${slug}']);`], { encoding: 'utf8' });
        assert.equal(php.status, 0, php.stderr);
        const config = JSON.parse(php.stdout);
        assert.deepEqual(config.files, { 'main.py': '' });
        const alternative = slug === 'latihan-1'
            ? solution.replace('6.5 <= self.ph <= 8.5', 'not (self.ph < 6.5 or self.ph > 8.5)')
            : solution.replace('class Rawa(Ekosistem):', 'class Rawa(Ekosistem):\n    def __init__(self, nama, lokasi):\n        super().__init__(nama, lokasi)');
        const incorrect = solution.replace(slug === 'latihan-1' ? 'return "Normal"' : 'return "Genangan rawa dipantau"', 'return "Salah"');
        const python = spawnSync('python', ['tests/js/checker-harness.py'], {
            input: JSON.stringify({ checker: config.checker, sources: [config.files, incorrect, solution, alternative] }), encoding: 'utf8',
        });
        assert.equal(python.status, 0, python.stderr);
        const [blank, wrong, ...solved] = JSON.parse(python.stdout);
        for (const report of [blank, wrong]) {
            assert.equal(report.error, undefined);
            assert.ok(report.results.some(check => !check.passed));
        }
        for (const report of solved) {
            assert.equal(report.error, undefined, report.error);
            assert.ok(report.results.every(check => check.passed));
        }
    });
}
