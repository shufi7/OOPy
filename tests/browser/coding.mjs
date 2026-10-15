// Use a disposable test database/server for --auth (it registers a test account).
import { chromium } from 'playwright';
import assert from 'node:assert/strict';
import { codingSolutions } from './coding-fixtures.mjs';
import { registerAccount } from './quiz-helpers.mjs';

const base = process.env.OOPY_BASE_URL || 'http://127.0.0.1:8017';
const browser = await chromium.launch({ channel: process.env.OOPY_BROWSER || 'msedge', headless: true });
const page = await browser.newPage();
const errors = [];
page.on('pageerror', error => errors.push(error.message));
let acceptReset = false;
page.on('dialog', dialog => dialog.type() === 'beforeunload' || acceptReset ? dialog.accept() : dialog.dismiss());
const role = name => page.locator(`[data-live-code] [data-role="${name}"]`);
const ready = () => role('run-code').waitFor({ state: 'visible' }).then(() => page.waitForFunction(() => !document.querySelector('[data-role="run-code"]').disabled, null, { timeout: 120000 }));
const edit = (slug, source) => page.evaluate(({ slug, source }) => {
    window.monaco.editor.getModel(window.monaco.Uri.parse(`file:///workspaces/coding-${slug}/main.py`)).setValue(source);
}, { slug, source });
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
        const source = () => page.evaluate(() => window.monaco.editor.getModels()[0].getValue());
        assert.equal(await source(), '');
        assert.equal(await role('config').evaluate(node => JSON.parse(node.textContent).files['main.py']), '');
        await execute('run-code');
        assert.match(await role('code-output').textContent(), /Editor masih kosong/);
        await execute('check-code');
        assert.match(await role('code-output').textContent(), /Editor masih kosong/);
        assert.equal(await role('check-results').isVisible(), false);
        await edit(slug, '   \n\t');
        await execute('run-code');
        assert.match(await role('code-output').textContent(), /Editor masih kosong/);
        await edit(slug, slug === 'latihan-1' ? 'class SensorAir:\n    pass' : 'class Ekosistem:\n    pass');
        await execute('check-code');
        assert.ok(await role('check-list').locator('.is-failed').count() > 0);
        await edit(slug, 'print(1 / 0)');
        await execute('run-code');
        assert.match(await role('code-output').textContent(), /ZeroDivisionError/);
        assert.equal(await source(), 'print(1 / 0)');
        await edit(slug, solution);
        await execute('run-code');
        assert.match(await role('code-output').textContent(), slug === 'latihan-1' ? /Rawa Bangkau\nStatus air: Normal/ : /Genangan rawa dipantau/);
        assert.equal(await role('check-results').isVisible(), false, 'Run does not grade');
        assert.equal(await source(), solution, 'Run preserves learner source');
        await execute('check-code');
        assert.equal(await role('check-list').locator('.is-failed').count(), 0);
        assert.match(await role('check-summary').textContent(), /Masuk untuk menyimpan/);
        assert.equal(await role('practice-percentage').textContent(), '0%', 'Guest local success is not official completion');
        await role('reset-code').click();
        assert.equal(await page.evaluate(() => window.monaco.editor.getModels()[0].getValue()), solution, 'Cancel reset preserves source');
        acceptReset = true;
        await role('reset-code').click();
        acceptReset = false;
        assert.equal(await source(), '');
        await execute('run-code');
        assert.match(await role('code-output').textContent(), /Editor masih kosong/);
        assert.equal(await source(), '', 'Run after reset must not repopulate the editor');
        for (const width of [320, 390, 768, 1440]) {
            await page.setViewportSize({ width, height: 1000 });
            assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1), `${slug}: overflow ${width}`);
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
        for (const status of [401, 419, 429, 500]) {
            await page.route('**/coding/latihan-1/submit', route => route.fulfill({ status, json: {} }));
            await execute('check-code');
            assert.match(await role('check-summary').textContent(), { 401: /Masuk kembali/, 419: /Sesi keamanan/, 429: /Terlalu banyak/, 500: /Server gagal/ }[status]);
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
    }
    assert.deepEqual(errors, []);
} finally {
    await browser.close();
}
