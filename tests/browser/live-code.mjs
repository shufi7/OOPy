// Run with Playwright installed, a local Laravel server, and an installed browser.
import { chromium } from 'playwright';
import assert from 'node:assert/strict';

const base = process.env.OOPY_BASE_URL || 'http://127.0.0.1:8017';
const browser = await chromium.launch({ channel: process.env.OOPY_BROWSER || 'msedge', headless: true });
const page = await browser.newPage();
const errors = [];
page.on('pageerror', (error) => errors.push(error.message));
page.on('dialog', (dialog) => dialog.accept());
const role = (id, name) => page.locator(`#${id} [data-role="${name}"]`);
const output = (id) => role(id, 'code-output').textContent();
const waitReady = () => page.waitForFunction(() => [...document.querySelectorAll('[data-role="run-code"]')].every((b) => !b.disabled), null, { timeout: 110000 });
const edit = (id, name, code) => page.evaluate(({ id, name, code }) => {
    window.monaco.editor.getModel(window.monaco.Uri.parse(`file:///workspaces/${id}/${name}`)).setValue(code);
}, { id, name, code });
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
} finally {
    await browser.close();
}
