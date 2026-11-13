// Use a disposable test database/server for --auth (it registers a test account).
import { chromium } from 'playwright';
import assert from 'node:assert/strict';
import { mkdir } from 'node:fs/promises';
import { codingKeys, codingSolutions } from './coding-fixtures.mjs';
import { cacheAssets, registerAccount } from './quiz-helpers.mjs';

const base = process.env.OOPY_BASE_URL || 'http://127.0.0.1:8017';
if (process.env.OOPY_SCREENSHOT_DIR) await mkdir(process.env.OOPY_SCREENSHOT_DIR, { recursive: true });
const browser = await chromium.launch({ channel: process.env.OOPY_BROWSER || 'msedge', headless: true });
const page = await browser.newPage();
await cacheAssets(page.context());
const errors = [];
page.on('pageerror', error => errors.push(error.message));
let acceptReset = false;
page.on('dialog', dialog => dialog.type() === 'beforeunload' || acceptReset ? dialog.accept() : dialog.dismiss());
const role = name => page.locator(`[data-live-code] [data-role="${name}"]`);
const ready = () => role('run-code').waitFor({ state: 'visible' }).then(() => page.waitForFunction(() => !document.querySelector('[data-role="run-code"]').disabled, null, { timeout: 120000 }));
const edit = (slug, source) => page.evaluate(({ slug, source }) => {
    for (const [name, code] of Object.entries(typeof source === 'string' ? { 'main.py': source } : source)) {
        window.monaco.editor.getModel(window.monaco.Uri.parse(`file:///workspaces/${slug}/${name}`)).setValue(code);
    }
}, { slug: codingKeys[slug], source });
const execute = async action => {
    await role(action).click();
    await ready();
};

try {
    await page.goto(`${base}/coding`, { waitUntil: 'domcontentloaded' });
    assert.equal(await page.locator('.coding-card').count(), 2);
    assert.equal(await page.locator('.navbar .nav-link.active').textContent(), 'Coding');
    let guestPosts = 0;
    page.on('request', request => { if (request.method() === 'POST' && request.url().includes('/submit')) guestPosts++; });
    for (const [slug, solution] of Object.entries(codingSolutions)) {
        await page.goto(`${base}/coding/${slug}`, { waitUntil: 'domcontentloaded' });
        await ready();
        const source = () => page.evaluate(() => {
            const config = JSON.parse(document.querySelector('[data-role=config]').textContent);
            const files = Object.fromEntries(Object.keys(config.files).map(name => [name, window.monaco.editor.getModel(window.monaco.Uri.parse(`file:///workspaces/${config.id}/${name}`)).getValue()]));
            return Object.keys(files).length === 1 ? files['main.py'] : files;
        });
        const blank = slug === 'latihan-1' ? '' : { 'ekosistem.py': '', 'rawa.py': '', 'main.py': '' };
        const blankMessage = slug === 'latihan-1' ? /Editor masih kosong/ : /File belum lengkap/;
        const project = main => slug === 'latihan-1' ? main : { ...solution, 'main.py': main };
        assert.deepEqual(await source(), blank);
        assert.equal(await page.locator('[role=tab]').count(), slug === 'latihan-1' ? 1 : 3);
        assert.equal(await page.locator('.oopy-file-button').count(), slug === 'latihan-1' ? 1 : 3);
        await execute('run-code');
        assert.match(await role('code-output').textContent(), blankMessage);
        await execute('check-code');
        assert.match(await role('code-output').textContent(), blankMessage);
        assert.equal(await role('check-results').isVisible(), false);
        await edit(slug, '   \n\t');
        await execute('run-code');
        assert.match(await role('code-output').textContent(), blankMessage);
        await edit(slug, slug === 'latihan-1' ? 'class SensorAir:\n    pass' : Object.fromEntries(Object.keys(solution).map(name => [name, 'pass'])));
        await execute('check-code');
        assert.ok(await role('check-list').locator('.is-failed').count() > 0);
        await edit(slug, project('print(1 / 0)'));
        await execute('run-code');
        assert.match(await role('code-output').textContent(), /ZeroDivisionError/);
        assert.deepEqual(await source(), project('print(1 / 0)'));
        await edit(slug, solution);
        await execute('run-code');
        assert.match(await role('code-output').textContent(), slug === 'latihan-1' ? /Rawa Bangkau\nStatus air: Normal/ : /Rawa Bangkau - Hulu Sungai Selatan\nStatus air: Siaga/);
        assert.equal(await role('check-results').isVisible(), false, 'Run does not grade');
        assert.deepEqual(await source(), solution, 'Run preserves learner source');
        if (slug === 'latihan-2') {
            const tabs = page.getByRole('tab');
            await tabs.nth(0).click();
            await page.keyboard.press('ArrowRight');
            assert.equal(await tabs.nth(1).getAttribute('aria-selected'), 'true');
            await page.keyboard.press('End');
            assert.equal(await tabs.nth(2).getAttribute('aria-selected'), 'true');
            assert.deepEqual(await source(), solution, 'Tab navigation preserves all Monaco models');
            await edit(slug, { 'rawa.py': 'from missing import Ekosistem' });
            await execute('run-code');
            assert.match(await role('code-output').innerText(), /ModuleNotFoundError/);
            assert.match(await role('code-output').innerText(), /rawa.py/);
            await edit(slug, solution);
            await edit(slug, { 'rawa.py': 'class Rawa(:' });
            await execute('run-code');
            assert.match(await role('code-output').innerText(), /SyntaxError/);
            assert.match(await role('code-output').innerText(), /rawa.py/);
            assert.deepEqual(await source(), { ...solution, 'rawa.py': 'class Rawa(:' });
            await tabs.nth(0).click();
            for (const marker of ['VERSI A', 'VERSI B']) {
                await edit(slug, { 'rawa.py': solution['rawa.py'].replace('return "Siaga"', `return "${marker}"`) });
                await execute('run-code');
                assert.match(await role('code-output').innerText(), new RegExp(marker));
                assert.equal((await role('code-output').innerText()).includes(marker === 'VERSI A' ? 'VERSI B' : 'VERSI A'), false);
                assert.equal((await source())['main.py'], solution['main.py']);
            }
            for (const replacement of [
                'if tinggi_air_cm == 81.25:\n            super().__init__(nama, lokasi)\n        else:\n            self.nama = nama\n            self.lokasi = lokasi',
                'self.nama = nama\n        self.lokasi = lokasi\n        super(Ekosistem, self).__init__()',
            ]) {
                await edit(slug, { ...solution, 'rawa.py': solution['rawa.py'].replace('super().__init__(nama, lokasi)', replacement) });
                await execute('check-code');
                assert.equal(await role('check-list').locator('.is-failed').count(), 1);
                assert.match(await role('check-score').innerText(), /Skor lokal: 85/);
                assert.equal(await role('practice-percentage').innerText(), '0%');
            }
            await edit(slug, solution);
            assert.equal(await page.locator('[role=tab] .oopy-file-dirty:not([hidden])').count(), 3);
            await edit(slug, { 'main.py': 'while True:\n    pass' });
            await role('run-code').click();
            await role('stop-code').waitFor({ state: 'visible' });
            await role('stop-code').click();
            await ready();
            assert.deepEqual(await source(), { ...solution, 'main.py': 'while True:\n    pass' }, 'Stop preserves every model');
            await edit(slug, solution);
            await page.locator('.monaco-editor textarea').focus();
            await page.keyboard.press('Control+Enter');
            await ready();
            assert.match(await role('code-output').innerText(), /Status air: Siaga/);
        }
        await execute('check-code');
        assert.equal(await role('check-list').locator('.is-failed').count(), 0);
        assert.match(await role('check-summary').textContent(), /Masuk untuk menyimpan/);
        assert.equal(await role('practice-percentage').textContent(), '0%', 'Guest local success is not official completion');
        await role('reset-code').click();
        assert.deepEqual(await source(), solution, 'Cancel reset preserves all source');
        acceptReset = true;
        await role('reset-code').click();
        acceptReset = false;
        assert.deepEqual(await source(), blank);
        await execute('run-code');
        assert.match(await role('code-output').textContent(), blankMessage);
        assert.deepEqual(await source(), blank, 'Run after reset must not repopulate editors');
        for (const width of [320, 390, 768, 1024, 1440]) {
            await page.setViewportSize({ width, height: 1000 });
            assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1), `${slug}: overflow ${width}`);
            if (process.env.OOPY_SCREENSHOT_DIR && [390, 1440].includes(width)) await page.screenshot({ path: `${process.env.OOPY_SCREENSHOT_DIR}/${slug}-${width}.png`, fullPage: true });
        }
        console.log(`PASS ${slug}: empty Monaco, blank/whitespace guard, wrong/correct Submit, real Pyodide output/error, source preserved, Reset empties, responsive sizes`);
    }
    assert.equal(guestPosts, 0);
    if (process.argv.includes('--auth')) {
        await page.setViewportSize({ width: 1440, height: 1000 });
        await registerAccount(page, base);
        await page.goto(`${base}/coding/latihan-1`, { waitUntil: 'domcontentloaded' });
        await ready();
        await edit('latihan-1', codingSolutions['latihan-1']);
        const responsePromise = page.waitForResponse(response => response.url().endsWith('/coding/latihan-1/submit'));
        await execute('check-code');
        assert.equal((await responsePromise).status(), 503, 'Test server must run without the official grader');
        assert.match(await role('check-summary').textContent(), /Verifikasi resmi/);
        assert.equal(await role('practice-percentage').textContent(), '0%');
        await page.reload({ waitUntil: 'domcontentloaded' });
        assert.equal(await page.locator('[data-coding-completion]').textContent(), 'Belum Dikerjakan');
        await ready();
        assert.equal(await page.evaluate(() => window.monaco.editor.getModels()[0].getValue()), '');
        const postsBeforeBlank = guestPosts;
        await execute('check-code');
        assert.equal(guestPosts, postsBeforeBlank, 'Authenticated empty Submit never reaches the server');
        await edit('latihan-1', codingSolutions['latihan-1']);
        for (const status of [401, 419, 422, 429, 500]) {
            await page.route('**/coding/latihan-1/submit', route => route.fulfill({ status, json: {} }));
            await execute('check-code');
            assert.match(await role('check-summary').textContent(), { 401: /Masuk kembali/, 419: /Sesi keamanan/, 422: /Lengkapi main.py/, 429: /Terlalu banyak/, 500: /Server gagal/ }[status]);
            await page.unroute('**/coding/latihan-1/submit');
        }
        await page.route('**/coding/latihan-1/submit', route => route.abort());
        await execute('check-code');
        assert.doesNotMatch(await role('check-summary').textContent(), /progres telah tersimpan/);
        await page.unroute('**/coding/latihan-1/submit');
        // UI contract only: simulate a delayed server acknowledgement and ensure no early success.
        let acknowledge;
        let requests = 0;
        const acknowledged = new Promise(resolve => { acknowledge = resolve; });
        await page.route('**/coding/latihan-1/submit', async route => {
            requests++;
            await acknowledged;
            await route.fulfill({ json: { status: 'passed', score: 100, completed: true,
                checks: [{ label: 'Pemeriksaan resmi', passed: true, feedback: '' }],
                message: 'Latihan 1 berhasil diselesaikan dan progres telah tersimpan.' } });
        });
        await role('check-code').click();
        await page.waitForFunction(() => document.querySelector('[data-role="check-code"]').textContent.includes('Verifikasi server'));
        assert.equal(await role('check-code').isDisabled(), true);
        assert.equal(await role('reset-code').isDisabled(), true);
        assert.doesNotMatch(await role('check-summary').textContent(), /progres telah tersimpan/);
        assert.equal(await page.locator('[data-coding-completion]').textContent(), 'Belum Dikerjakan');
        acknowledge();
        await ready();
        assert.equal(requests, 1);
        assert.match(await role('check-summary').textContent(), /progres telah tersimpan/);
        assert.equal(await role('practice-percentage').textContent(), '100%');
        assert.match(await page.locator('[data-coding-completion]').textContent(), /Selesai/);
        console.log('PASS authenticated UI: real unavailable grader leaves progress incomplete; session/CSRF/rate/network errors; delayed simulated acknowledgement and disabled duplicate Submit');
        await page.goto(`${base}/coding/latihan-2`, { waitUntil: 'domcontentloaded' });
        await ready();
        await edit('latihan-2', codingSolutions['latihan-2']);
        const multiResponse = page.waitForResponse(response => response.url().endsWith('/coding/latihan-2/submit'));
        await execute('check-code');
        const response = await multiResponse;
        assert.equal(response.status(), 503);
        assert.deepEqual(response.request().postDataJSON().files, codingSolutions['latihan-2']);
        assert.equal(await role('practice-percentage').textContent(), '0%');
        assert.equal(await page.locator('[data-coding-completion]').textContent(), 'Belum Dikerjakan');
        console.log('PASS authenticated multi-file: all three latest sources reach Laravel; unavailable grader saves no completion');
        await page.route('**/coding/latihan-2/submit', route => route.fulfill({ json: {
            status: 'failed', score: 85, completed: false,
            checks: [{ label: 'Constructor Rawa', passed: false, feedback: 'Gunakan initializer Ekosistem.' }],
            message: 'Percobaan ini belum lulus.',
        } }));
        await execute('check-code');
        assert.equal(await page.locator('[data-coding-completion]').innerText(), 'Belum Lulus');
        assert.equal(await role('practice-percentage').innerText(), '0%');
        await page.unroute('**/coding/latihan-2/submit');
        await page.route('**/coding/latihan-2/submit', route => route.fulfill({ status: 422, json: {} }));
        await execute('check-code');
        assert.match(await role('check-summary').textContent(), /Lengkapi ekosistem.py, rawa.py, main.py/);
        assert.match(await role('check-summary').textContent(), /Total proyek maksimal 30.000 karakter dan 60.000 byte UTF-8/);
        assert.deepEqual(await page.evaluate(() => Object.fromEntries(window.monaco.editor.getModels().map(model => [model.uri.path.split('/').at(-1), model.getValue()]))), codingSolutions['latihan-2']);
        await page.unroute('**/coding/latihan-2/submit');
        console.log('PASS validation feedback names every required file for the active exercise and preserves the project');
    }
    assert.deepEqual(errors, []);
} finally {
    await browser.close();
}
