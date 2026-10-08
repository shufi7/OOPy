import { test } from 'node:test';
import assert from 'node:assert/strict';
import { spawnSync } from 'node:child_process';
import { exercises } from '../browser/chapters.mjs';

for (const exercise of exercises) {
    test(`${exercise.slug}: starter, incorrect behavior, real execution and alternative solutions`, () => {
        const php = spawnSync(process.env.OOPY_PHP || 'php', ['-d', 'display_errors=stderr', '-r',
            `$chapter = require 'resources/materi/${exercise.slug}.php'; foreach ($chapter['sections'] as $section) { foreach ($section['live_codes'] ?? [] as $config) { echo json_encode($config); } }`,
        ], { encoding: 'utf8' });
        assert.equal(php.status, 0, php.stderr);
        const config = JSON.parse(php.stdout);
        const sources = [config.files['main.py'], ...exercise.incorrect.map((entry) => entry.source), exercise.solution, ...exercise.alternatives];
        const python = spawnSync(process.env.OOPY_PYTHON || 'python', ['tests/js/checker-harness.py'], {
            input: JSON.stringify({ checker: config.checker, sources }), encoding: 'utf8',
        });
        assert.equal(python.status, 0, python.stderr);
        const results = JSON.parse(python.stdout);
        for (const checks of results) {
            assert.equal(checks.length, exercise.checks);
            for (const check of checks) {
                assert.equal(typeof check.passed, 'boolean');
                if (!check.passed) assert.ok(check.feedback.trim(), check.label);
            }
        }
        assert.ok(results[0].some((check) => !check.passed), 'Starter must fail');
        for (const [index, incorrect] of exercise.incorrect.entries()) {
            assert.ok(results[index + 1].some((check) => !check.passed && incorrect.label.test(check.label)), String(incorrect.label));
        }
        for (const checks of results.slice(exercise.incorrect.length + 1)) {
            assert.ok(checks.every((check) => check.passed), JSON.stringify(checks.filter((check) => !check.passed)));
        }
    });
}
