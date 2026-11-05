// Run against a fresh admin-sandbox.php fixture, never the application database.
import { chromium } from 'playwright';
import assert from 'node:assert/strict';
import { mkdir, readFile, writeFile } from 'node:fs/promises';
import { join } from 'node:path';

assert.match(process.env.OOPY_ADMIN_TEST_DATABASE || '', /^admin-coding-[a-f0-9]{32}\.sqlite$/);
const base = process.env.OOPY_BASE_URL || 'http://127.0.0.1:8041';
const directory = process.env.OOPY_SCREENSHOT_DIR;
const results = { layouts: [], colors: [], interactions: [], errors: [] };
if (directory) await mkdir(directory, { recursive: true });
const browser = await chromium.launch({ channel: process.env.OOPY_BROWSER || 'msedge', headless: true });
const context = await browser.newContext({ viewport: { width: 1440, height: 1000 }, reducedMotion: 'reduce', acceptDownloads: true });
if (process.env.OOPY_ASSET_MANIFEST) {
    const assets = JSON.parse(await readFile(process.env.OOPY_ASSET_MANIFEST, 'utf8'));
    await context.route('**/*', route => {
        const url = route.request().url();
        if (!assets[url]) return route.continue();
        const path = new URL(url).pathname;
        const contentType = url.includes('fonts.googleapis.com') || path.endsWith('.css') ? 'text/css'
            : path.endsWith('.js') ? 'application/javascript' : path.endsWith('.woff2') ? 'font/woff2'
                : path.endsWith('.ttf') ? 'font/ttf' : 'application/octet-stream';
        return route.fulfill({ path: assets[url], contentType, headers: { 'access-control-allow-origin': '*' } });
    });
}
const page = await context.newPage();
page.on('pageerror', error => results.errors.push(error.message));
const visit = async path => {
    const response = await page.goto(`${base}${path}`, { waitUntil: 'load' });
    assert.equal(response.status(), 200, path);
    if (path.startsWith('/admin')) assert.match(response.headers()['cache-control'], /no-store/);
    await page.evaluate(() => document.fonts.ready);
    assert.equal((await page.content()).includes('private-admin-coding-fixture'), false);
};
const login = async email => {
    await visit('/login');
    await page.locator('#email').fill(email);
    await page.locator('#password').fill('password123');
    await Promise.all([page.waitForURL(url => url.pathname !== '/login'), page.locator('button[type="submit"]').click()]);
};
const record = text => { results.interactions.push(text); console.log(`PASS ${text}`); };
const luminance = color => {
    const channels = color.match(/[\d.]+/g).slice(0, 3).map(Number).map(v => {
        v /= 255;
        return v <= .04045 ? v / 12.92 : ((v + .055) / 1.055) ** 2.4;
    });
    return .2126 * channels[0] + .7152 * channels[1] + .0722 * channels[2];
};
const colors = async () => {
    for (const selector of ['.admin-coding-status', '.admin-metrics dt', '.admin-metrics dd', '.admin-coding-history td', '.admin-coding-history th', '.admin-filters label']) {
        for (const element of await page.locator(selector).all()) {
            const pair = await element.evaluate(el => {
                const style = getComputedStyle(el);
                let background = style.backgroundColor;
                for (let node = el.parentElement; background === 'rgba(0, 0, 0, 0)' && node; node = node.parentElement) background = getComputedStyle(node).backgroundColor;
                return { text: el.textContent.trim(), color: style.color, background, size: parseFloat(style.fontSize), weight: parseInt(style.fontWeight) };
            });
            const ratio = (Math.max(luminance(pair.color), luminance(pair.background)) + .05) / (Math.min(luminance(pair.color), luminance(pair.background)) + .05);
            const minimum = pair.size >= 24 || (pair.size >= 18.667 && pair.weight >= 700) ? 3 : 4.5;
            assert.ok(ratio >= minimum, `${selector}: ${pair.text} has contrast ${ratio}`);
            results.colors.push({ selector, ...pair, ratio, minimum });
        }
    }
};
const layout = async name => {
    for (const width of [360, 390, 768, 1024, 1440]) {
        await page.setViewportSize({ width, height: 1000 });
        await page.evaluate(() => new Promise(resolve => requestAnimationFrame(resolve)));
        const data = await page.evaluate(() => ({
            width: innerWidth, scroll: document.documentElement.scrollWidth,
            tables: [...document.querySelectorAll('.table-responsive')].map(el => ({ width: el.clientWidth, scroll: el.scrollWidth })),
            unlabeled: [...document.querySelectorAll('input:not([type=hidden]),select')].filter(el => !el.labels?.length && !el.getAttribute('aria-label')).map(el => el.name),
        }));
        assert.ok(data.scroll <= width + 1, `${name}: ${JSON.stringify(data)}`);
        assert.deepEqual(data.unlabeled, []);
        results.layouts.push({ name, ...data });
        if (directory && [390, 1440].includes(width)) await page.screenshot({ path: join(directory, `${name}-${width}.png`), fullPage: true });
    }
    await colors();
    console.log(`PASS ${name}: five viewports and local table overflow`);
};
const applyFilters = async () => {
    await Promise.all([page.waitForNavigation({ waitUntil: 'load' }), page.getByRole('button', { name: 'Terapkan', exact: true }).click()]);
};

try {
    await login('admin.browser@example.test');
    await visit('/admin');
    for (const [key, expected] of Object.entries({ attempted_users: 2, exercise_1_completed: 1, exercise_2_completed: 0, both_completed: 0, none_passed: 1 })) {
        assert.equal(await page.locator(`[data-coding-stat="${key}"]`).innerText(), String(expected));
    }
    assert.equal(await page.locator('[data-coding-submission]').count(), 10);
    assert.match(await page.locator('[data-coding-submission]').first().innerText(), /=SUM\(1;2\).*0.*error.*08:24 WITA/s);
    await layout('dashboard');
    await page.locator('[data-coding-submission]').first().getByRole('link').click();
    await page.waitForURL(`${base}/admin/users/3`);
    assert.match(await page.locator('[data-coding-exercise="coding-latihan-1"]').innerText(), /0.*Belum Lulus/s);
    record('Unique student statistics and latest ten, including valid zero/Error; activity opens the correct student');

    await visit('/admin/users');
    await layout('users');
    await page.getByRole('link', { name: 'Pelajar Uji', exact: true }).click();
    await page.waitForURL(`${base}/admin/users/2`);
    assert.equal(await page.getByRole('progressbar', { name: 'Progres Latihan Coding' }).getAttribute('aria-valuenow'), '50');
    assert.match(await page.locator('[data-coding-exercise="coding-latihan-1"]').innerText(), /90.*Selesai.*22/s);
    assert.match(await page.locator('[data-coding-exercise="coding-latihan-2"]').innerText(), /70.*Belum Lulus.*1/s);
    assert.equal(await page.locator('[data-coding-submission]').count(), 10);
    assert.equal(await page.locator('[data-coding-submission]').first().getAttribute('data-coding-submission'), '23');
    await layout('student');
    await visit('/admin/users/2?coding_page=2&history_page=1&final_page=1');
    assert.equal(await page.locator('[data-coding-submission]').count(), 10);
    assert.equal(await page.locator('[data-coding-submission]').first().getAttribute('data-coding-submission'), '13');
    const thirdPage = page.locator('a.page-link[href*="coding_page=3"]:visible').first();
    assert.match(await thirdPage.getAttribute('href'), /history_page=1/);
    await thirdPage.click();
    assert.equal(await page.locator('[data-coding-submission]').count(), 3);
    assert.equal(await page.locator('[data-coding-submission]').first().getAttribute('data-coding-submission'), '3');
    record('Student detail preserves best score and completion after failed retries; Coding pagination is independent');

    await visit('/admin/scores');
    assert.equal(await page.locator('[data-score-user]').count(), 3);
    await layout('scores');
    await page.locator('#coding_exercise').focus();
    await page.keyboard.press('Tab');
    assert.equal(await page.locator('#coding_status').evaluate(el => el === document.activeElement), true);
    assert.notEqual(await page.locator('#coding_status').evaluate(el => getComputedStyle(el).outlineStyle), 'none');
    await page.locator('#coding_exercise').selectOption('coding-latihan-1');
    await page.locator('#coding_status').selectOption('completed');
    await page.locator('#sort').selectOption('coding_best');
    await applyFilters();
    assert.equal(await page.locator('[data-score-user]').count(), 1);
    assert.equal(await page.locator('[data-score-user]').getAttribute('data-score-user'), '2');
    assert.match(await page.locator('[data-score-user]').innerText(), /90.*Selesai.*70.*Belum Lulus.*50%/s);
    await page.locator('#chapter').selectOption('1');
    await page.locator('#status').selectOption('passed');
    await applyFilters();
    assert.equal(await page.locator('[data-score-user]').count(), 1);
    await page.locator('#coding_status').selectOption('in_progress');
    await applyFilters();
    assert.equal(await page.locator('[data-score-user]').count(), 0);
    assert.match(await page.locator('main').innerText(), /Tidak ada pengguna yang sesuai filter/);
    await page.locator('#status').selectOption('');
    await page.locator('#q').fill('zero.browser@example.test');
    await applyFilters();
    assert.equal(await page.locator('[data-score-user]').getAttribute('data-score-user'), '3');
    const downloadLink = page.getByRole('link', { name: 'Export CSV', exact: true });
    assert.match(await downloadLink.getAttribute('href'), /coding_status=in_progress/);
    const [download] = await Promise.all([page.waitForEvent('download'), downloadLink.click()]);
    const chunks = [];
    for await (const chunk of await download.createReadStream()) chunks.push(chunk);
    const csv = Buffer.concat(chunks);
    assert.deepEqual([...csv.subarray(0, 3)], [239, 187, 191]);
    const text = csv.toString('utf8');
    assert.ok(text.includes('Coding Latihan 1 — Nilai Terbaik'));
    assert.ok(text.includes('"\'=SUM(1;2)"'));
    assert.ok(text.includes('zero.browser@example.test'));
    assert.equal(text.includes('student.browser@example.test'), false);
    assert.match(text, /;0;"Belum Lulus";—;"Belum Dikerjakan";0;0/);
    record('Coding and quiz filters combine with AND; search and downloaded CSV preserve filters, BOM, zero and formula protection');
    await page.getByRole('link', { name: 'Reset', exact: true }).click();
    assert.equal(await page.locator('[data-score-user]').count(), 3);
    assert.equal(await page.locator('#coding_status').inputValue(), '');
    await page.setViewportSize({ width: 390, height: 1000 });
    const region = page.getByRole('region', { name: 'Rekap nilai BAB, Evaluasi Akhir, dan Coding', exact: true });
    await region.focus();
    for (let i = 0; i < 5; i++) await page.keyboard.press('ArrowRight');
    await page.waitForTimeout(200);
    assert.ok(await region.evaluate(el => el.scrollLeft > 0));
    record('Filter reset and keyboard scrolling reach the wide score table without page overflow');

    await visit('/admin/users/4');
    assert.equal(await page.getByRole('progressbar', { name: 'Progres Latihan Coding' }).getAttribute('aria-valuenow'), '0');
    assert.equal(await page.getByText('Belum Dikerjakan', { exact: true }).count(), 2);
    assert.match(await page.locator('main').innerText(), /Pengguna ini belum mengerjakan latihan Coding/);
    await layout('empty-student');
    await Promise.all([page.waitForURL(`${base}/`), page.locator('.admin-sidebar-footer button').click()]);
    await login('student.browser@example.test');
    await visit('/dashboard');
    assert.equal(await page.getByRole('progressbar', { name: 'Progres Latihan Coding' }).getAttribute('aria-valuenow'), '50');
    assert.match(await page.locator('[data-dashboard-coding="coding-latihan-1"]').innerText(), /Selesai.*90/s);
    assert.match(await page.locator('[data-dashboard-coding="coding-latihan-2"]').innerText(), /Belum Lulus.*70/s);
    for (const path of ['/admin', '/admin/users/2', '/admin/scores', '/admin/scores/export']) assert.equal((await page.request.get(`${base}${path}`)).status(), 403);
    await context.clearCookies();
    assert.equal((await page.request.get(`${base}/admin`, { maxRedirects: 0 })).status(), 302);
    record('Empty state, student/Admin consistency, ordinary-user 403 and guest redirect verified');
    assert.deepEqual(results.errors, []);
    results.status = 'PASS';
} catch (error) {
    results.failure = error.stack;
    throw error;
} finally {
    if (directory) await writeFile(join(directory, 'results.json'), JSON.stringify(results, null, 2));
    await browser.close();
}
