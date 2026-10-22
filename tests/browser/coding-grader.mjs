// This suite requires the real gateway, Docker image, and a disposable MariaDB database.
import { chromium } from 'playwright';
import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { readFile, mkdir, writeFile } from 'node:fs/promises';
import { join } from 'node:path';
import { codingSolutions } from './coding-fixtures.mjs';
import { registerAccount, loginAccount, logoutAccount } from './quiz-helpers.mjs';

assert.match(process.env.OOPY_TEST_DATABASE || '', /^oopy_grader_test_[a-f0-9]{32}$/);
const base = process.env.OOPY_BASE_URL || 'http://127.0.0.1:8027';
const unavailableBase = process.env.OOPY_UNAVAILABLE_BASE_URL;
assert.ok(unavailableBase, 'Provide an isolated test server with unavailable grader credentials.');
const directory = process.env.OOPY_SCREENSHOT_DIR || 'docs/evidence/coding-grader-2026-10-22';
await mkdir(directory, { recursive: true });
const results = { database: process.env.OOPY_TEST_DATABASE, submissions: [], interactions: [], errors: [] };
const secret = (await readFile('.env', 'utf8')).match(/^CODING_GRADER_TOKEN=(.+)$/m)?.[1].trim();
assert.ok(secret, 'Configure the private grader token before this test.');
const assets = process.env.OOPY_ASSET_MANIFEST ? JSON.parse(await readFile(process.env.OOPY_ASSET_MANIFEST, 'utf8')) : {};
const browser = await chromium.launch({ channel: process.env.OOPY_BROWSER || 'msedge', headless: true });
const makeContext = async () => {
    const context = await browser.newContext({ viewport: { width: 1440, height: 1000 }, reducedMotion: 'reduce' });
    await context.route('**/*', route => {
        const url = route.request().url();
        if (!assets[url]) return route.continue();
        const path = new URL(url).pathname;
        const contentType = url.includes('fonts.googleapis.com') || path.endsWith('.css') ? 'text/css'
            : path.endsWith('.js') ? 'application/javascript' : path.endsWith('.wasm') ? 'application/wasm'
                : path.endsWith('.woff2') ? 'font/woff2' : path.endsWith('.ttf') ? 'font/ttf' : 'application/octet-stream';
        return route.fulfill({ path: assets[url], contentType, headers: { 'access-control-allow-origin': '*' } });
    });
    return context;
};
const context = await makeContext();
const page = await context.newPage();
page.on('pageerror', error => results.errors.push(error.message));
page.on('dialog', dialog => dialog.accept());
const part = name => page.locator(`[data-live-code] [data-role="${name}"]`);
const ready = () => page.waitForFunction(() => !document.querySelector('[data-role="run-code"]').disabled, null, { timeout: 120000 });
const edit = (slug, source) => page.evaluate(({ slug, source }) => {
    window.monaco.editor.getModel(window.monaco.Uri.parse(`file:///workspaces/coding-${slug}/main.py`)).setValue(source);
}, { slug, source });
const probe = account => JSON.parse(execFileSync('php', ['-d', 'display_errors=stderr', 'tests/browser/coding-db-probe.php', account.email], {
    encoding: 'utf8', env: process.env, stdio: ['ignore', 'pipe', 'pipe'],
}));
const record = message => { results.interactions.push(message); console.log(`PASS ${message}`); };
const screenshots = async name => {
    for (const width of [360, 390, 768, 1024, 1440]) {
        await page.setViewportSize({ width, height: 1000 });
        await part('check-results').scrollIntoViewIfNeeded();
        assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1));
        if ([390, 1440].includes(width)) await page.screenshot({ path: join(directory, `${name}-${width}.png`) });
    }
};

try {
    const account = await registerAccount(page, base);
    const owner = probe(account).user_id;
    for (const [slug, solution] of Object.entries(codingSolutions)) {
        await page.goto(`${base}/coding/${slug}`, { waitUntil: 'domcontentloaded' });
        await ready();
        assert.equal(await page.evaluate(() => window.monaco.editor.getModels()[0].getValue()), '');
        assert.equal((await page.content()).includes(secret), false, 'Private token must never reach the browser');
        assert.equal((await page.content()).includes('secrets.randbelow'), false);
        const correct = `\n${solution}\n\n`;
        await edit(slug, correct);
        await part('run-code').click();
        await ready();
        assert.match(await part('code-output').innerText(), slug === 'latihan-1' ? /Rawa Bangkau\nStatus air: Normal/ : /Genangan rawa dipantau/);
        assert.equal(await page.evaluate(() => window.monaco.editor.getModels()[0].getValue()), correct);
        assert.equal(await page.locator('[data-coding-completion]').innerText(), 'Belum Dikerjakan');

        for (const [status, source] of [['passed', correct], ['failed', slug === 'latihan-1' ? 'class SensorAir:\n    pass' : 'class Ekosistem:\n    pass'], ['error', 'print(1 / 0)']]) {
            await edit(slug, source);
            const before = probe(account).submissions.length;
            let release;
            let requests = 0;
            const barrier = new Promise(resolve => { release = resolve; });
            const endpoint = `**/coding/${slug}/submit`;
            // Delay delivery only; every successful response comes from the real server.
            await page.route(endpoint, async route => { requests++; await barrier; await route.continue(); });
            const responsePromise = page.waitForResponse(response => response.url().endsWith(`/coding/${slug}/submit`));
            await part('check-code').click();
            await page.waitForFunction(() => document.querySelector('[data-role="check-code"]').textContent.includes('Verifikasi server'));
            assert.equal(await part('check-code').isDisabled(), true);
            assert.equal(await part('reset-code').isDisabled(), true);
            await part('check-code').evaluate(el => el.dispatchEvent(new MouseEvent('click', { bubbles: true })));
            assert.equal(probe(account).submissions.length, before);
            release();
            const response = await responsePromise;
            assert.equal(response.status(), 200);
            const report = await response.json();
            await ready();
            await page.unroute(endpoint);
            assert.equal(requests, 1);
            assert.equal(report.status, status);
            assert.equal(report.completed, true, 'A later failed/error attempt must preserve completion');
            if (status === 'passed') assert.equal(report.score, 100);
            if (status === 'error') assert.equal(report.score, 0);
            const snapshot = probe(account);
            assert.equal(snapshot.submissions.length, before + 1);
            const stored = snapshot.submissions.at(-1);
            assert.equal(stored.user_id, owner);
            assert.equal(stored.exercise_key, `coding-${slug}`);
            assert.equal(stored.status, status);
            assert.equal(Number(stored.score), report.score);
            assert.equal(stored.code.entry_file, 'main.py');
            assert.equal(stored.code.files['main.py'], source);
            assert.ok(stored.submitted_at);
            if (status === 'passed') assert.match(stored.output, slug === 'latihan-1' ? /Status air: Normal/ : /Genangan rawa dipantau/);
            assert.match(await part('check-score').innerText(), /Skor resmi/);
            assert.equal(await part('practice-percentage').innerText(), '100%');
            results.submissions.push({ slug, status, score: report.score, submissionId: stored.id, userId: stored.user_id, snapshotPreserved: true });
            await screenshots(`${slug}-${status}`);
            record(`${slug}: ${status}, real Docker result saved to isolated MariaDB, exact source/output/owner/time, no duplicate Submit`);
        }
        await page.reload({ waitUntil: 'domcontentloaded' });
        await ready();
        assert.match(await page.locator('[data-coding-completion]').innerText(), /Selesai/);
        assert.equal(await page.evaluate(() => window.monaco.editor.getModels()[0].getValue()), '');
        await part('run-code').focus();
        await page.keyboard.press('Tab');
        assert.equal(await part('reset-code').evaluate(el => el === document.activeElement), true);
        assert.notEqual(await part('reset-code').evaluate(el => getComputedStyle(el).outlineStyle), 'none');
    }
    const csrf = await page.locator('meta[name="csrf-token"]').getAttribute('content');
    const throttled = await page.request.post(`${base}/coding/latihan-1/submit`, {
        headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf }, data: { files: { 'main.py': codingSolutions['latihan-1'] } },
    });
    assert.equal(throttled.status(), 429);
    assert.equal(probe(account).submissions.length, 6);
    await page.setViewportSize({ width: 1440, height: 1000 });
    await logoutAccount(page, base);
    await loginAccount(page, base, account);
    await page.goto(`${base}/coding`);
    assert.equal(await page.locator('.coding-status.is-completed').count(), 2);
    await page.screenshot({ path: join(directory, 'completed-after-login-1440.png') });
    record('Both exercises remain complete after failed/error retries, refresh, logout/login; seventh submission is rate limited');

    await logoutAccount(page, base);
    const other = await registerAccount(page, base);
    await page.goto(`${base}/coding`);
    assert.equal(await page.locator('.coding-status.is-completed').count(), 0);
    const otherCsrf = await page.locator('meta[name="csrf-token"]').getAttribute('content');
    const post = (slug, data, withCsrf = true) => page.request.post(`${base}/coding/${slug}/submit`, {
        headers: { Accept: 'application/json', ...(withCsrf ? { 'X-CSRF-TOKEN': otherCsrf } : {}) }, data,
    });
    for (const slug of Object.keys(codingSolutions)) {
        const response = await post(slug, { files: { 'main.py': 'class Incomplete:\n    pass' } });
        assert.equal(response.status(), 200);
        const report = await response.json();
        assert.equal(report.status, 'failed');
        assert.equal(report.completed, false);
    }
    assert.equal((await post('latihan-1', { files: { 'main.py': 'pass' }, user_id: owner, score: 100, passed: true, status: 'passed' })).status(), 422);
    assert.equal((await post('latihan-1', { files: { 'main.py': 'x'.repeat(30001) } })).status(), 422);
    assert.equal((await post('latihan-1', { files: { '../main.py': 'pass' } })).status(), 422);
    assert.equal((await post('latihan-1', { files: { 'main.py': 'pass' } }, false)).status(), 419);
    assert.equal((await page.request.get(`${base}/coding/submissions/${probe(account).submissions[0].id}`)).status(), 404);
    assert.equal(probe(other).submissions.length, 2);
    assert.equal(probe(account).submissions.length, 6);
    record('Other account starts incomplete; wrong answers do not pass; forged identity/score, oversized input, path traversal and missing CSRF rejected; owner history unchanged');

    for (const [slug, solution] of Object.entries(codingSolutions)) {
        await page.setViewportSize({ width: 1440, height: 1000 });
        await logoutAccount(page, base);
        const learner = await registerAccount(page, base);
        await page.goto(`${base}/coding/${slug}`, { waitUntil: 'domcontentloaded' });
        await ready();
        const formatted = solution.replaceAll('Normal', ' NORMAL ').replaceAll('Perlu pemeriksaan', ' PERLU   PEMERIKSAAN ')
            .replaceAll('Status air:', ' STATUS  AIR : ').replaceAll('Rawa Bangkau', 'RAWA   BANGKAU')
            .replaceAll('Status belum ditentukan', ' STATUS   BELUM DITENTUKAN ')
            .replaceAll('Genangan rawa dipantau', ' GENANGAN   RAWA DIPANTAU ');
        const conceptError = slug === 'latihan-1'
            ? solution.replace('6.5 <= self.ph <= 8.5', '6.5 < self.ph < 8.5')
            : solution.replaceAll('Genangan rawa dipantau', 'Salah');
        const marker = slug === 'latihan-1' ? 'sensor = ' : 'rawa = ';
        let completed = false;
        const cases = [
            ['core-error', conceptError, 'failed', slug === 'latihan-1' ? 70 : 75],
            ['syntax-error', 'if True print(1)', 'error', 0],
            ['format-variation', formatted, 'passed', 100],
            ['missing-output', solution.slice(0, solution.indexOf('print(')), 'passed', 90],
            ['classes-only', solution.slice(0, solution.indexOf(marker)), 'passed', 90],
            ['forged-results', 'results = [{"passed": True}]; score = 100; passed = True', 'failed', 0],
        ];
        for (const [name, source, status, score] of cases) {
            await edit(slug, source);
            let release;
            const barrier = new Promise(resolve => { release = resolve; });
            const endpoint = `**/coding/${slug}/submit`;
            await page.route(endpoint, async route => { await barrier; await route.continue(); });
            const responsePromise = page.waitForResponse(response => response.url().endsWith(`/coding/${slug}/submit`));
            await part('check-code').click();
            await page.waitForFunction(() => document.querySelector('[data-role="check-code"]').textContent.includes('Verifikasi server'));
            if (status !== 'error') {
                assert.equal(await part('check-score').innerText(), `Skor lokal: ${score}`);
                assert.equal(await part('check-list').locator('li').count(), 7);
                assert.equal(await part('check-list').getByText(/Inti \(wajib\)/).count(), 6);
                assert.equal(await part('check-list').getByText(/Tambahan ·/).count(), 1);
            }
            assert.equal(await part('practice-percentage').innerText(), completed ? '100%' : '0%');
            release();
            const response = await responsePromise;
            assert.equal(response.status(), 200);
            const report = await response.json();
            await ready();
            await page.unroute(endpoint);
            completed ||= status === 'passed';
            assert.equal(report.status, status);
            assert.equal(report.score, score);
            assert.equal(report.completed, completed);
            assert.equal(await part('check-score').innerText(), `Skor resmi: ${score}`);
            assert.equal(await part('practice-percentage').innerText(), completed ? '100%' : '0%');
            const stored = probe(learner).submissions.at(-1);
            assert.equal(stored.code.files['main.py'], source);
            assert.equal(stored.status, status);
            assert.equal(Number(stored.score), score);
            if (name === 'missing-output' || name === 'classes-only') {
                assert.equal(await part('check-list').locator('li.is-advice').count(), 1);
                assert.match(await part('check-summary').innerText(), /Semua konsep inti berhasil/);
            }
            await screenshots(`${slug}-${name}`);
            results.submissions.push({ slug, case: name, status, score, submissionId: stored.id, userId: stored.user_id, snapshotPreserved: true });
            record(`${slug}: ${name}, local and Docker scores ${score}, official ${status}, completion ${completed}, snapshot saved`);
        }
        assert.equal(probe(learner).submissions.length, 6);
    }

    const failureContext = await makeContext();
    const failurePage = await failureContext.newPage();
    const failureAccount = await registerAccount(failurePage, unavailableBase);
    await failurePage.goto(`${unavailableBase}/coding/latihan-1`, { waitUntil: 'domcontentloaded' });
    await failurePage.waitForFunction(() => !document.querySelector('[data-role="run-code"]').disabled, null, { timeout: 120000 });
    await failurePage.evaluate(source => window.monaco.editor.getModels()[0].setValue(source), codingSolutions['latihan-1']);
    const failedResponse = failurePage.waitForResponse(response => response.url().endsWith('/coding/latihan-1/submit'));
    await failurePage.locator('[data-role="check-code"]').click();
    assert.equal((await failedResponse).status(), 503);
    await failurePage.waitForFunction(() => !document.querySelector('[data-role="run-code"]').disabled);
    assert.match(await failurePage.locator('[data-role="check-summary"]').innerText(), /Verifikasi resmi/);
    assert.equal(await failurePage.locator('[data-role="practice-percentage"]').innerText(), '0%');
    assert.equal(probe(failureAccount).submissions.length, 0);
    await failurePage.screenshot({ path: join(directory, 'unavailable-grader-1440.png') });
    await failureContext.close();
    record('Real wrong gateway token produces Laravel 503, clear feedback, zero official submissions and no completion');
    await logoutAccount(page, base);
    await page.goto(`${base}/coding/latihan-1`);
    const guestCsrf = await page.locator('meta[name="csrf-token"]').getAttribute('content');
    assert.equal((await page.request.post(`${base}/coding/latihan-1/submit`, { headers: { Accept: 'application/json', 'X-CSRF-TOKEN': guestCsrf }, data: { files: { 'main.py': 'pass' } } })).status(), 401);
    record('Guest cannot submit official answers');
    assert.deepEqual(results.errors, []);
} catch (error) {
    results.failure = error.stack;
    throw error;
} finally {
    await writeFile(join(directory, 'browser-real-grader.json'), JSON.stringify(results, null, 2));
    await browser.close();
}
