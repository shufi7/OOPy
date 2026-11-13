// DB fixtures test dashboard rendering; a real unavailable Submit verifies no false completion.
import { chromium } from 'playwright';
import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { mkdir, readFile, writeFile } from 'node:fs/promises';
import { join } from 'node:path';
import { registerAccount, loginAccount, logoutAccount, answerQuiz } from './quiz-helpers.mjs';
import { codingKeys, codingSolutions } from './coding-fixtures.mjs';

assert.equal(process.env.DB_CONNECTION, 'sqlite');
assert.match(process.env.DB_DATABASE || '', /coding-dashboard-[a-f0-9]{32}\.sqlite$/);
const base = process.env.OOPY_BASE_URL || 'http://127.0.0.1:8039';
const directory = process.env.OOPY_SCREENSHOT_DIR;
const results = { layouts: [], colors: [], interactions: [], errors: [] };
if (directory) await mkdir(directory, { recursive: true });
const browser = await chromium.launch({ channel: process.env.OOPY_BROWSER || 'msedge', headless: true });
const context = await browser.newContext({ viewport: { width: 1440, height: 1000 }, reducedMotion: 'reduce' });
if (process.env.OOPY_ASSET_MANIFEST) {
    const assets = JSON.parse(await readFile(process.env.OOPY_ASSET_MANIFEST, 'utf8'));
    await context.route('**/*', route => {
        const url = route.request().url();
        if (!assets[url]) return route.continue();
        const path = new URL(url).pathname;
        const contentType = url.includes('fonts.googleapis.com') || path.endsWith('.css') ? 'text/css'
            : path.endsWith('.js') ? 'application/javascript' : path.endsWith('.wasm') ? 'application/wasm'
                : path.endsWith('.woff2') ? 'font/woff2' : path.endsWith('.ttf') ? 'font/ttf' : 'application/octet-stream';
        return route.fulfill({ path: assets[url], contentType, headers: { 'access-control-allow-origin': '*' } });
    });
}
const page = await context.newPage();
page.on('pageerror', error => results.errors.push(error.message));
page.on('dialog', dialog => dialog.accept());
const coding = () => page.locator('.oopy-dashboard-coding');
const history = () => page.locator('.oopy-dashboard-coding-history');
const row = number => page.locator(`[data-dashboard-coding="${codingKeys[`latihan-${number}`]}"]`);
const visit = async () => {
    assert.equal((await page.goto(`${base}/dashboard`, { waitUntil: 'load' })).status(), 200);
    await page.evaluate(() => document.fonts.ready);
};
const expectProgress = async percent => {
    assert.equal(await coding().getByRole('progressbar').getAttribute('aria-valuenow'), String(percent));
    assert.equal(await coding().getByRole('progressbar').getAttribute('aria-valuetext'), `${percent / 50} dari 2 latihan selesai`);
    assert.equal(await coding().locator('.oopy-dashboard-progress-track > span').evaluate(el => el.style.width), `${percent}%`);
};
const fixture = (scenario, account) => JSON.parse(execFileSync('php', ['-d', 'display_errors=stderr',
    'tests/browser/dashboard-coding-fixture.php', scenario, account.email], { encoding: 'utf8', env: process.env }));
const luminance = value => {
    const channels = value.match(/[\d.]+/g).slice(0, 3).map(Number).map(v => {
        v /= 255;
        return v <= .04045 ? v / 12.92 : ((v + .055) / 1.055) ** 2.4;
    });
    return .2126 * channels[0] + .7152 * channels[1] + .0722 * channels[2];
};
const auditColors = async state => {
    const pairs = await page.locator('.oopy-dashboard-coding, .oopy-dashboard-coding-history').evaluateAll(sections => sections.flatMap(section =>
        [...section.querySelectorAll('*')].filter(el => el.checkVisibility() && !el.closest('.visually-hidden')
            && [...el.childNodes].some(node => node.nodeType === 3 && node.textContent.trim())).map(el => {
            const style = getComputedStyle(el);
            let background = style.backgroundColor;
            for (let node = el.parentElement; background === 'rgba(0, 0, 0, 0)' && node; node = node.parentElement) background = getComputedStyle(node).backgroundColor;
            return { text: el.textContent.trim(), color: style.color, background, size: parseFloat(style.fontSize), weight: parseInt(style.fontWeight) };
        })));
    for (const pair of pairs) {
        const ratio = (Math.max(luminance(pair.color), luminance(pair.background)) + .05) / (Math.min(luminance(pair.color), luminance(pair.background)) + .05);
        const minimum = pair.size >= 24 || (pair.size >= 18.667 && pair.weight >= 700) ? 3 : 4.5;
        assert.ok(ratio >= minimum, `${pair.text}: contrast ${ratio}`);
        results.colors.push({ state, ...pair, ratio, minimum });
    }
};
const layout = async state => {
    for (const width of [320, 360, 390, 768, 1024, 1440]) {
        await page.setViewportSize({ width, height: 1000 });
        await page.evaluate(() => new Promise(resolve => requestAnimationFrame(resolve)));
        const data = await page.evaluate(() => ({ width: innerWidth, scroll: document.documentElement.scrollWidth,
            controls: [...document.querySelectorAll('.oopy-dashboard-coding a,.oopy-dashboard-coding-history a')].map(el => {
                const r = el.getBoundingClientRect();
                return { text: el.textContent.trim(), left: r.left, right: r.right, width: r.width, height: r.height };
            }),
        }));
        assert.ok(data.scroll <= width + 1, JSON.stringify(data));
        for (const control of data.controls) assert.ok(control.left >= 0 && control.right <= width + 1 && control.height >= 44 && control.width >= 44, JSON.stringify(control));
        results.layouts.push({ state, ...data });
        if (directory && [390, 1440].includes(width)) await page.screenshot({ path: join(directory, `${state}-${width}.png`), fullPage: true });
    }
    await auditColors(state);
    console.log(`PASS Coding dashboard ${state}: six widths, targets and text contrast`);
};
const openExercise = async (number, action) => {
    const link = row(number).getByRole('link');
    assert.match(await link.innerText(), new RegExp(action));
    await link.focus();
    await page.keyboard.press('Tab');
    await page.keyboard.press('Shift+Tab');
    assert.equal(await link.evaluate(el => el === document.activeElement), true);
    assert.notEqual(await link.evaluate(el => getComputedStyle(el).outlineStyle), 'none');
    await page.keyboard.press('Enter');
    await page.waitForURL(`${base}/coding/latihan-${number}`);
    assert.equal(await page.locator('[data-live-code]').count(), 1);
    results.interactions.push(`${action}, Latihan ${number}: keyboard focus and Enter open existing route`);
};

try {
    await page.goto(`${base}/dashboard`);
    assert.equal(new URL(page.url()).pathname, '/login');
    const account = await registerAccount(page, base);
    await visit();
    await expectProgress(0);
    assert.equal(await row(1).locator('.oopy-dashboard-status').innerText(), 'Belum Dimulai');
    assert.equal(await row(1).locator('.oopy-dashboard-grade-value').innerText(), '—');
    assert.match(await history().innerText(), /Belum ada riwayat latihan coding/);
    await layout('empty');
    await history().getByRole('link', { name: 'Buka Latihan Coding' }).click();
    await page.waitForURL(`${base}/coding`);
    results.interactions.push('Empty state opens existing Coding catalog');
    await visit();
    await openExercise(2, 'Mulai Latihan');
    await visit();
    await openExercise(1, 'Mulai Latihan');
    await page.waitForFunction(() => !document.querySelector('[data-role="run-code"]').disabled, null, { timeout: 120000 });
    await page.evaluate(source => window.monaco.editor.getModels()[0].setValue(source), codingSolutions['latihan-1']);
    await page.locator('[data-role="run-code"]').click();
    await page.waitForFunction(() => !document.querySelector('[data-role="run-code"]').disabled);
    assert.match(await page.locator('[data-role="code-output"]').innerText(), /Rawa Bangkau\nStatus air: Normal/);
    const submission = page.waitForResponse(response => response.url().endsWith('/coding/latihan-1/submit'));
    await page.locator('[data-role="check-code"]').click();
    assert.equal((await submission).status(), 503);
    await page.waitForFunction(() => !document.querySelector('[data-role="run-code"]').disabled);
    assert.match(await page.locator('[data-role="check-summary"]').innerText(), /Verifikasi resmi/);
    await visit();
    await expectProgress(0);
    assert.match(await history().innerText(), /Belum ada riwayat latihan coding/);
    results.interactions.push('Real Pyodide Run succeeds; unavailable official grader returns 503; refreshed dashboard remains empty at 0%');

    fixture('failed', account);
    await visit();
    await expectProgress(0);
    assert.equal(await row(1).locator('.oopy-dashboard-status').innerText(), 'Belum Lulus');
    assert.equal(await row(1).locator('.oopy-dashboard-grade-value').innerText(), '0');
    await auditColors('failed');
    await openExercise(1, 'Lanjutkan Latihan');
    await visit();
    const { user_id: owner } = fixture('first-passed', account);
    await visit();
    await expectProgress(50);
    assert.equal(await row(1).locator('.oopy-dashboard-status').innerText(), 'Selesai');
    assert.equal(await row(1).locator('.oopy-dashboard-grade-value').innerText(), '90');
    await layout('half');
    await openExercise(1, 'Kerjakan Kembali');
    await visit();
    fixture('both-passed', account);
    await visit();
    await expectProgress(100);
    assert.equal(await row(2).locator('.oopy-dashboard-grade-value').innerText(), '100');
    assert.equal(await history().locator('li').count(), 5);
    assert.deepEqual(await history().locator('li p strong').allTextContents(), ['0', '10', '100', '70', '90']);
    assert.match(await history().locator('li').first().innerText(), /5 Nov 2026, 08:06 WITA.*Skor:\s*0.*Error/s);
    assert.equal((await page.content()).includes('private-browser-fixture'), false);
    await layout('complete');
    await openExercise(2, 'Kerjakan Kembali');
    await visit();
    await history().getByRole('link').first().click();
    await page.waitForURL(`${base}/coding/latihan-2`);
    results.interactions.push('Newest history link opens Latihan 2; latest five, WITA time, Error and source privacy verified');

    await page.goto(`${base}/materi/dasar-pemrograman-oop`, { waitUntil: 'domcontentloaded' });
    await page.locator('[data-quiz="form"]').waitFor({ state: 'visible' });
    await answerQuiz(page, 'dasar-pemrograman-oop', [0, 1, 2, 3]);
    await visit();
    await expectProgress(100);
    assert.equal(await page.getByRole('progressbar', { name: 'Progres enam BAB inti' }).getAttribute('aria-valuenow'), '17');
    assert.equal(await page.locator('[data-dashboard="average"]').innerText(), '80');
    await page.evaluate(() => { document.documentElement.style.fontSize = '200%'; });
    results.textResize = await page.evaluate(() => ({
        width: innerWidth,
        scroll: document.documentElement.scrollWidth,
        codingSections: [...document.querySelectorAll('.oopy-dashboard-coding,.oopy-dashboard-coding-history')]
            .map(el => ({ width: el.clientWidth, scroll: el.scrollWidth })),
        overflowing: [...document.querySelectorAll('.oopy-dashboard *')].filter(el => el.getBoundingClientRect().right > innerWidth + 1)
            .map(el => ({ tag: el.tagName, className: el.className, right: el.getBoundingClientRect().right })),
    }));
    assert.ok(results.textResize.codingSections.every(section => section.scroll <= section.width + 1), JSON.stringify(results.textResize));
    await page.evaluate(() => { document.documentElement.style.fontSize = ''; });
    await logoutAccount(page, base);
    await loginAccount(page, base, account);
    await visit();
    await expectProgress(100);
    results.interactions.push('Actual chapter quiz remains 17% and score 80 independently; new Coding sections reflow at 200% root text; logout/login preserves Coding results');
    await logoutAccount(page, base);
    await registerAccount(page, base);
    await page.goto(`${base}/dashboard?user_id=${owner}`);
    await expectProgress(0);
    assert.match(await history().innerText(), /Belum ada riwayat latihan coding/);
    assert.equal(await page.locator('[data-dashboard="average"]').innerText(), '—');
    const noJs = await browser.newContext({ javaScriptEnabled: false, storageState: await context.storageState() });
    const staticPage = await noJs.newPage();
    assert.equal((await staticPage.goto(`${base}/dashboard`)).status(), 200);
    assert.equal(await staticPage.locator('[data-dashboard-coding]').count(), 2);
    assert.match(await staticPage.locator('.oopy-dashboard-coding-history').innerText(), /Belum ada riwayat latihan coding/);
    await noJs.close();
    results.interactions.push('Account B cannot select Account A via query string; two exercise rows render without JavaScript');
    assert.deepEqual(results.errors, []);
} catch (error) {
    results.failure = error.stack;
    throw error;
} finally {
    if (directory) await writeFile(join(directory, 'results.json'), JSON.stringify(results, null, 2));
    await browser.close();
}
