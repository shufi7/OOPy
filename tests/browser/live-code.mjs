// Run with Playwright installed, a local Laravel server, and an installed browser.
import { chromium } from 'playwright';
import assert from 'node:assert/strict';
import { exercises } from './chapters.mjs';

const base = process.env.OOPY_BASE_URL || 'http://127.0.0.1:8017';
const browser = await chromium.launch({ channel: process.env.OOPY_BROWSER || 'msedge', headless: true });
const page = await browser.newPage();
const errors = [];
page.on('pageerror', (error) => { errors.push(error.message); console.error(error.stack); });
page.on('dialog', (dialog) => dialog.accept());
const role = (id, name) => page.locator(`#${id} [data-role="${name}"]`);
const output = (id) => role(id, 'code-output').textContent();
const waitReady = () => page.waitForFunction(() => [...document.querySelectorAll('[data-role="run-code"]')].every((b) => !b.disabled), null, { timeout: 110000 });
const edit = (id, name, code) => page.evaluate(({ id, name, code }) => {
    window.monaco.editor.getModel(window.monaco.Uri.parse(`file:///workspaces/${id}/${name}`)).setValue(code);
}, { id, name, code });
const editProject = async (id, source) => {
    for (const [file, code] of Object.entries(typeof source === 'string' ? { 'main.py': source } : source)) await edit(id, file, code);
};
const run = async (id, action = 'run-code') => {
    await role(id, action).click();
    await page.waitForFunction((id) => !document.querySelector(`#${id} [data-role="run-code"]`).disabled, id, { timeout: 110000 });
};
const job = (payload) => page.evaluate(async (payload) => {
    const { runtimeManager } = await import('/js/live-code/runtime-manager.js');
    return new Promise((resolve) => {
        const messages = [];
        runtimeManager.submit('browser-test', payload, (message) => {
            messages.push(message);
            if (['done', 'error', 'cancelled'].includes(message.type)) resolve(messages);
        });
    });
}, payload);
const a = 'demo-dasar', b = 'demo-objek', c = 'demo-pewarisan';

try {
    await page.addInitScript(() => {
        const NativeWorker = window.Worker;
        window.pythonWorkerCount = 0;
        window.Worker = class extends NativeWorker {
            constructor(url, options) {
                super(url, options);
                if (String(url).includes('/python-worker.js')) window.pythonWorkerCount++;
            }
        };
    });
    if (!process.argv.includes('--chapters-only')) {
    await page.goto(`${base}/editor`, { waitUntil: 'domcontentloaded' });
    await waitReady();
    assert.equal(await page.locator('[data-live-code]').count(), 3);
    assert.equal(await page.evaluate(() => window.pythonWorkerCount), 1);
    assert.equal(await page.locator('script[src*="vs/loader.js"]').count(), 1);
    assert.equal(await page.evaluate(() => window.monaco.editor.getModels().length), 7);
    await run(a);
    assert.match(await output(a), /Rawa Bangkau/);
    await run(b);
    const bOutput = await output(b);
    await edit(a, 'main.py', 'nama = "Berubah"\nprint(nama)');
    assert.equal(await role(a, 'practice-percentage').textContent(), '0%');
    await run(c, 'check-code');
    assert.equal(await role(c, 'check-list').locator('li').count(), 11);
    assert.equal(await role(c, 'practice-percentage').textContent(), '100%');
    await role(a, 'reset-code').click();
    assert.equal(await output(b), bOutput);
    assert.equal(await role(c, 'practice-percentage').textContent(), '100%');
    for (const id of [a, b]) {
        await run(id, 'check-code');
        assert.equal(await role(id, 'practice-percentage').textContent(), '100%');
    }
    console.log('PASS: A Run, B Run, A edit, C Submit, A Reset; independent models, output and scores');

    await edit(a, 'main.py', 'nama = "salah"');
    await run(a, 'check-code');
    assert.equal(await role(a, 'practice-percentage').textContent(), '0%');
    assert.match(await role(a, 'check-list').textContent(), /Rawa Bangkau/);
    for (const [code, expected] of [['if True print(1)', /SyntaxError/], ['import missing_oopy_module', /ModuleNotFoundError/], ['input("Nama: ")', /input\(\).*belum didukung/]]) {
        await edit(a, 'main.py', code);
        await run(a);
        assert.match(await output(a), expected);
    }
    await edit(a, 'main.py', 'print("x" * 150000)');
    await run(a);
    assert.match(await output(a), /Output dibatasi/);
    assert.ok((await output(a)).length < 101000);
    const partial = await job({ type: 'run', files: { 'main.py': 'print("partial-line", end="")' }, entryFile: 'main.py', checker: '' });
    assert.ok(partial.some((message) => message.type === 'output' && message.chunks.some((chunk) => chunk.text.includes('partial-line'))));
    console.log('PASS: assertion feedback, syntax/import errors, unsupported input, output limit');

    const custom = await job({ type: 'check', files: { 'start.py': 'from pkg.helper import value\nassert value == 42', 'pkg/__init__.py': '', 'pkg/helper.py': 'value = 42', 'a.py': '', 'b.py': '', 'c.py': '' }, entryFile: 'start.py', checker: 'assert value == 42' });
    assert.equal(custom.at(-1).type, 'done');
    const isolated = await job({ type: 'run', files: { 'start.py': 'import pkg.helper' }, entryFile: 'start.py', checker: '' });
    assert.match(isolated.at(-1).message, /ModuleNotFoundError/);
    const missing = await job({ type: 'run', files: { 'other.py': '' }, entryFile: 'start.py', checker: '' });
    assert.match(missing.at(-1).message, /Entry file.*tidak ditemukan/);
    const checkerError = await job({ type: 'check', files: { 'main.py': 'value = 1' }, entryFile: 'main.py', checker: 'raise ValueError("checker rusak")' });
    assert.match(checkerError.at(-1).message, /Checker Python Error/);
    const emptyResults = await job({ type: 'check', files: { 'main.py': '' }, entryFile: 'main.py', checker: 'results = []' });
    assert.equal(emptyResults.at(-1).type, 'error');
    // Same module name, different editor, then same-length immediate edit.
    await run(c);
    await run(b);
    assert.doesNotMatch(await output(b), /Error/);
    await edit(b, 'ekosistem.py', 'class Ekosistem:\n    def __init__(self, nama):\n        self.nama = "Versi AAA"');
    await run(b);
    assert.match(await output(b), /Versi AAA/);
    await edit(b, 'ekosistem.py', 'class Ekosistem:\n    def __init__(self, nama):\n        self.nama = "Versi BBB"');
    await run(b);
    assert.match(await output(b), /Versi BBB/);
    await role(b, 'reset-code').click();
    console.log('PASS: custom entry, six files, packages, module isolation, fresh imports, checker errors');

    await edit(a, 'main.py', 'while True: pass');
    await role(a, 'run-code').click();
    await role(b, 'run-code').click();
    assert.match(await role(b, 'python-status-text').textContent(), /antrean/);
    await role(b, 'stop-code').click();
    assert.match(await output(b), /Antrean dibatalkan/);
    assert.equal(await page.evaluate(() => window.pythonWorkerCount), 1);
    await role(c, 'check-code').click();
    await role(a, 'stop-code').click();
    await waitReady();
    assert.equal(await role(c, 'practice-percentage').textContent(), '100%');
    assert.equal(await page.evaluate(() => window.pythonWorkerCount), 2);
    await role(a, 'run-code').click();
    await role(b, 'run-code').click();
    await waitReady();
    assert.match(await output(a), /maksimal 10 detik/);
    assert.match(await output(b), /Rawa Bangkau/);
    assert.equal(await page.evaluate(() => window.pythonWorkerCount), 3);
    await role(a, 'reset-code').click();
    console.log('PASS: queued cancellation, active Stop, timeout and queued-job recovery');

    // Keyboard tabs and Ctrl+Enter remain local to the focused editor.
    await page.locator(`#${b} [role="tab"]`).first().focus();
    await page.keyboard.press('End');
    assert.equal(await page.locator(`#${b} [role="tab"][aria-selected="true"]`).textContent(), 'main.py');
    await page.locator(`#${a} .monaco-editor .view-lines`).click();
    await page.waitForFunction(() => window.monaco.editor.getEditors()[0].hasTextFocus());
    const beforeShortcut = await output(c);
    await page.keyboard.press('Control+Enter');
    await waitReady();
    assert.match(await output(a), /Rawa Bangkau/);
    assert.equal(await output(c), beforeShortcut);
    for (const width of [390, 768, 940, 1440]) {
        await page.setViewportSize({ width, height: 900 });
        assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1), `Overflow at ${width}px`);
    }
    assert.deepEqual(errors, []);
    console.log('PASS: keyboard navigation, shortcut, 390/768/940/1440px layout; no page errors');

    // Real CDN failure and recovery; the editor still renders when Python is offline.
    const failed = await browser.newPage();
    await failed.route('**/pyodide.js', (route) => route.abort());
    await failed.goto(`${base}/editor`, { waitUntil: 'domcontentloaded' });
    await failed.locator(`#${a} [data-role="retry-runtime"]`).waitFor({ state: 'visible' });
    await failed.unroute('**/pyodide.js');
    await failed.locator(`#${a} [data-role="retry-runtime"]`).click();
    await failed.waitForFunction(() => !document.querySelector('[data-role="run-code"]').disabled, null, { timeout: 110000 });
    await failed.close();
    const noMonaco = await browser.newPage();
    await noMonaco.route('**/vs/loader.js', (route) => route.abort());
    await noMonaco.goto(`${base}/editor`, { waitUntil: 'domcontentloaded' });
    await noMonaco.waitForFunction(() => document.querySelector('[data-role="editor-loading"]').textContent.includes('tidak dapat diunduh'));
    await noMonaco.close();
    console.log('PASS: Python CDN failure/retry and Monaco CDN failure message');
    }

    // Exercise the actual chapter configs through Monaco and the Pyodide worker.
    for (const exercise of exercises) {
        const { id, slug } = exercise;
        await page.goto(`${base}/materi/${slug}`, { waitUntil: 'domcontentloaded' });
        await waitReady();
        const config = JSON.parse(await role(id, 'config').textContent());
        assert.equal(await page.evaluate(() => window.pythonWorkerCount), 1);
        assert.equal(await page.locator('script[src*="vs/loader.js"]').count(), 1);
        assert.equal(await page.locator('script[src$="js/live-code/live-code.js"]').count(), 1);
        assert.equal(await page.evaluate(() => window.monaco.editor.getModels().length), Object.keys(config.files).length);
        assert.equal(await page.evaluate((id) => window.monaco.editor.getModel(window.monaco.Uri.parse(`file:///workspaces/${id}/main.py`)).getValue(), id), config.files['main.py']);
        const submit = async () => {
            await run(id, 'check-code');
            assert.equal(await role(id, 'check-results').isVisible(), true);
            assert.equal(await role(id, 'check-list').locator('li').count(), exercise.checks);
            const failed = await role(id, 'check-list').locator('.is-failed').count();
            const percentage = Math.round((exercise.checks - failed) / exercise.checks * 100);
            assert.equal(await role(id, 'practice-percentage').textContent(), `${percentage}%`);
            assert.equal(await role(id, 'practice-progress').getAttribute('aria-valuenow'), String(percentage));
            assert.doesNotMatch(await output(id), /Python Error|Traceback/);
            return failed;
        };
        await run(id);
        assert.doesNotMatch(await output(id), /Python Error|Traceback/);
        assert.ok(await submit() > 0, `${slug}: starter must be incomplete`);
        if (slug === 'pewarisan') {
            assert.deepEqual(Object.keys(config.files), ['ekosistem.py', 'sungai.py', 'rawa.py', 'main.py']);
            assert.equal((await page.locator(`#${id} [role="tab"][aria-selected="true"]`).textContent()).trim(), 'main.py');
            for (const [file, starter] of Object.entries(config.files)) {
                await page.locator(`#${id} [role="tab"][data-file="${file}"]`).click();
                await edit(id, file, `${starter}\n# Edit tersimpan: ${file}`);
                await page.evaluate(() => window.monaco.editor.getEditors()[0].setPosition({ lineNumber: 2, column: 1 }));
                assert.equal(await page.locator(`#${id} [data-file="${file}"] .oopy-file-dirty:not([hidden])`).count(), 2);
            }
            for (const [file, starter] of Object.entries(config.files)) {
                await page.locator(`#${id} [role="tab"][data-file="${file}"]`).click();
                assert.equal(await page.evaluate(({ id, file }) => window.monaco.editor.getModel(window.monaco.Uri.parse(`file:///workspaces/${id}/${file}`)).getValue(), { id, file }), `${starter}\n# Edit tersimpan: ${file}`);
                assert.deepEqual(await page.evaluate(() => window.monaco.editor.getEditors()[0].getPosition()), { lineNumber: 2, column: 1 });
            }
            await role(id, 'reset-code').click();
            for (const [file, starter] of Object.entries(config.files)) {
                assert.equal(await page.evaluate(({ id, file }) => window.monaco.editor.getModel(window.monaco.Uri.parse(`file:///workspaces/${id}/${file}`)).getValue(), { id, file }), starter);
            }
            assert.equal(await page.locator(`#${id} .oopy-file-dirty:not([hidden])`).count(), 0);
            for (const width of [320, 390, 768, 1024, 1440]) {
                await page.setViewportSize({ width, height: 1000 });
                await page.locator(`#${id}`).scrollIntoViewIfNeeded();
                assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1), `BAB 4 overflow at ${width}px`);
                assert.equal(await page.locator(`#${id} [role="tab"]`).count(), 4);
                await page.waitForFunction(id => {
                    const strip = document.querySelector(`#${id} .oopy-file-tabs`).getBoundingClientRect();
                    const tab = document.querySelector(`#${id} [role="tab"][aria-selected="true"]`).getBoundingClientRect();
                    return tab.left >= strip.left - 1 && tab.right <= strip.right + 1;
                }, id);
                if (process.env.OOPY_SCREENSHOT_DIR && [390, 1440].includes(width)) await page.locator(`#${id}`).screenshot({ path: `${process.env.OOPY_SCREENSHOT_DIR}/bab4-multifile-${width}.png` });
            }
            console.log('PASS BAB 4: four tabs/models, independent edits, dirty indicators, cursor restoration, all-file Reset and five responsive sizes');
        }
        for (const expected of exercise.runtimeErrors || []) {
            await editProject(id, expected.source);
            await run(id);
            assert.match(await output(id), expected.error);
            await run(id, 'check-code');
            assert.match(await output(id), expected.error);
            assert.equal(await role(id, 'practice-percentage').textContent(), '0%');
            assert.equal(await role(id, 'check-results').isVisible(), false);
        }
        for (const incorrect of exercise.incorrect) {
            await editProject(id, incorrect.source);
            assert.ok(await submit() > 0);
            assert.match((await role(id, 'check-list').locator('.is-failed').allTextContents()).join('\n'), incorrect.label);
        }
        await editProject(id, exercise.solution);
        if (slug === 'pewarisan') await page.locator(`#${id} [role="tab"][data-file="ekosistem.py"]`).click();
        await run(id);
        assert.match(await output(id), exercise.output);
        assert.equal(await submit(), 0);
        for (const source of exercise.alternatives) {
            await editProject(id, source);
            assert.equal(await submit(), 0, `${slug}: alternative solution`);
        }
        await role(id, 'reset-code').click();
        assert.equal(await role(id, 'practice-percentage').textContent(), '0%');
        assert.equal(await role(id, 'check-results').isVisible(), false);
        for (const [file, source] of Object.entries(config.files)) {
            assert.equal(await page.evaluate(({ id, file }) => window.monaco.editor.getModel(window.monaco.Uri.parse(`file:///workspaces/${id}/${file}`)).getValue(), { id, file }), source);
        }
        assert.deepEqual(errors, []);
        console.log(`PASS: ${slug}: starter/wrong rejected, solution/alternatives 100%, real Python output, feedback/progress, Reset, one worker/loader; no page errors`);
    }
} finally {
    await browser.close();
}
