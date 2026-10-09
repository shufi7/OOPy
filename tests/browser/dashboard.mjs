import { chromium } from 'playwright';
import assert from 'node:assert/strict';
import { randomUUID, randomBytes } from 'node:crypto';
import { chapters } from './chapters.mjs';
import { cacheAssets, registerAccount, loginAccount, logoutAccount, answerQuiz } from './quiz-helpers.mjs';

const base = process.env.OOPY_BASE_URL || 'http://127.0.0.1:8021';
const browser = await chromium.launch({ channel: process.env.OOPY_BROWSER || 'msedge', headless: true });
const context = await browser.newContext({ viewport: { width: 1440, height: 1000 } });
await cacheAssets(context);
const page = await context.newPage();
const errors = [];
page.on('pageerror', error => errors.push(error.message));
page.on('console', message => { if (message.type() === 'error' && new URL(page.url()).pathname === '/dashboard') errors.push(message.text()); });
const dashboard = async () => {
    const response = await page.goto(`${base}/dashboard`, { waitUntil: 'networkidle' });
    assert.equal(response.status(), 200);
    await page.evaluate(() => document.fonts.ready);
};
const stat = name => page.locator(`[data-dashboard="${name}"]`);
const assertStats = async (done, average, attempts, percent) => {
    assert.equal((await stat('completed').innerText()).replace(/\s+/g, ' ').trim(), `${done} / 6`);
    assert.equal(await stat('average').textContent(), String(average));
    assert.equal(await stat('attempts').textContent(), String(attempts));
    assert.equal(await page.getByRole('progressbar').getAttribute('aria-valuenow'), String(percent));
};
const layout = async state => {
    for (const width of [320, 390, 768, 1024, 1440]) {
        await page.setViewportSize({ width, height: 1000 });
        const result = await page.evaluate(() => ({
            width: innerWidth,
            scroll: document.documentElement.scrollWidth,
            columns: getComputedStyle(document.querySelector('.oopy-dashboard-overview')).gridTemplateColumns.split(' ').length,
            font: getComputedStyle(document.querySelector('.oopy-dashboard')).fontFamily,
        }));
        assert.ok(result.scroll <= result.width, JSON.stringify({ state, ...result }));
        assert.equal(result.columns, width < 1200 ? 1 : 2);
        assert.match(result.font, /Plus Jakarta Sans/);
        assert.equal(await page.locator('[data-dashboard-chapter]').count(), 6);
        for (const button of await page.locator('.oopy-dashboard a:visible').all()) {
            assert.ok(await button.evaluate(el => {
                const rect = el.getBoundingClientRect();
                return rect.right <= innerWidth && rect.left >= 0 && rect.height >= 44;
            }), `CTA "${await button.innerText()}" clipping or touch size at ${width}px`);
        }
        if (process.env.OOPY_SCREENSHOT_DIR && [390, 1440].includes(width)) {
            await page.screenshot({ path: `${process.env.OOPY_SCREENSHOT_DIR}/dashboard-${state}-${width}.png`, fullPage: true });
        }
        console.log(`PASS dashboard ${state} at ${width}px: grid, font, CTAs and no overflow`);
    }
};
const grade = async (slug, correct) => {
    await page.goto(`${base}/materi/${slug}`, { waitUntil: 'domcontentloaded' });
    await page.locator('[data-quiz="form"]').waitFor({ state: 'visible' });
    await answerQuiz(page, slug, Array.from({ length: correct }, (_, index) => index));
};

try {
    await page.goto(`${base}/dashboard`);
    assert.equal(new URL(page.url()).pathname, '/login');
    const account = await registerAccount(page, base);
    await dashboard();
    await assertStats(0, '—', 0, 0);
    assert.equal(await page.locator('.oopy-dashboard-empty').count(), 1);
    assert.equal(await stat('continue').getAttribute('href'), `${base}/materi/${chapters[0].slug}`);
    assert.equal((await page.locator('#oopyDashboardSidebar a[aria-current="page"]').textContent()).trim(), 'Dashboard');
    await layout('empty');
    await page.setViewportSize({ width: 390, height: 1000 });
    const menu = page.locator('.oopy-dashboard-menu-toggle');
    await page.waitForFunction(() => document.querySelector('.oopy-dashboard-menu-toggle').getAttribute('aria-expanded') === 'false');
    assert.equal(await menu.getAttribute('aria-expanded'), 'false');
    await menu.click();
    assert.equal(await menu.getAttribute('aria-expanded'), 'true');
    assert.equal(await page.locator('#oopyDashboardSidebar').evaluate(el => el.inert), false);
    await page.keyboard.press('Escape');
    assert.equal(await menu.getAttribute('aria-expanded'), 'false');
    assert.equal(await page.locator('#oopyDashboardSidebar').evaluate(el => el.inert), true);
    await page.setViewportSize({ width: 1440, height: 1000 });
    await page.waitForFunction(() => document.querySelector('.oopy-dashboard-menu-toggle').getAttribute('aria-expanded') === 'true');
    assert.equal(await menu.getAttribute('aria-expanded'), 'true');
    await menu.click();
    assert.equal(await page.locator('#oopyDashboardSidebar').evaluate(el => el.inert), true);
    await menu.click();
    for (const link of await page.locator('.oopy-dashboard a').all()) {
        const response = await page.request.get(await link.getAttribute('href'));
        assert.equal(response.status(), 200, `Broken dashboard link ${await link.getAttribute('href')}`);
    }
    await grade(chapters[0].slug, 3);
    await dashboard();
    await assertStats(0, 60, 1, 0);
    assert.match(await page.locator(`[data-dashboard-chapter="${chapters[0].slug}"]`).innerText(), /Sedang Dipelajari/);
    await grade(chapters[0].slug, 4);
    await grade(chapters[1].slug, 5);
    await dashboard();
    await assertStats(2, 90, 3, 33);
    assert.equal(await stat('continue').getAttribute('href'), `${base}/materi/${chapters[2].slug}`);
    assert.equal(await page.locator('.oopy-dashboard-activity-list li').count(), 3);
    await layout('progress');
    await grade(chapters[0].slug, 0);
    await dashboard();
    await assertStats(2, 90, 4, 33);
    await page.locator('.oopy-dashboard-additional > summary').click();
    assert.match(await page.locator('.oopy-dashboard-activity-list li').first().innerText(), /Nilai:\s*0.*Belum Lulus/s);
    await page.locator('.oopy-dashboard-additional > summary').click();
    for (const chapter of chapters.slice(2)) await grade(chapter.slug, 4);
    await dashboard();
    await assertStats(6, '83,3', 8, 100);
    assert.equal(await stat('continue').getAttribute('href'), `${base}/materi/evaluasi-akhir`);
    assert.equal(await page.locator('.oopy-dashboard-activity-list li').count(), 5);
    await layout('complete');
    await logoutAccount(page, base);
    assert.equal(await page.locator('.site-navbar a[href$="/dashboard"]').count(), 0);
    await loginAccount(page, base, account);
    await dashboard();
    await assertStats(6, '83,3', 8, 100);
    await logoutAccount(page, base);

    const name = '<img src=x onerror=alert(1)>'.padEnd(255, 'Nama');
    const email = `dashboard-${randomUUID()}@example.test`;
    const password = randomBytes(18).toString('hex');
    await page.goto(`${base}/register`);
    await page.locator('#name').fill(name);
    await page.locator('#email').fill(email);
    await page.locator('#password').fill(password);
    await page.locator('#password_confirmation').fill(password);
    await Promise.all([page.waitForURL(`${base}/materi`), page.locator('.oopy-auth-form button[type="submit"]').click()]);
    await dashboard();
    await assertStats(0, '—', 0, 0);
    assert.match(await page.locator('.oopy-dashboard-greeting').textContent(), /<img src=x onerror=alert\(1\)>/);
    assert.equal(await page.locator('.oopy-dashboard img').count(), 0);
    await layout('long-name');
    const noJs = await browser.newContext({ javaScriptEnabled: false, storageState: await context.storageState() });
    await cacheAssets(noJs);
    const readOnly = await noJs.newPage();
    await readOnly.goto(`${base}/dashboard`);
    assert.equal(await readOnly.locator('[data-dashboard-chapter]').count(), 6);
    assert.equal(await readOnly.locator('[data-dashboard="continue"]').getAttribute('href'), `${base}/materi/${chapters[0].slug}`);
    await noJs.close();
    assert.deepEqual(errors, []);
    console.log('PASS dashboard real quiz data, best scores, monotonic completion, latest five, all links, logout/relogin, account isolation, escaped long names and no-JS reading');
} finally {
    await browser.close();
}
