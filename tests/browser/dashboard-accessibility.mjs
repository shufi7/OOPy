import { chromium } from 'playwright';
import assert from 'node:assert/strict';
import { cacheAssets, registerAccount } from './quiz-helpers.mjs';

const base = process.env.OOPY_BASE_URL || 'http://127.0.0.1:8021';
const browser = await chromium.launch({ channel: process.env.OOPY_BROWSER || 'msedge', headless: true });
const context = await browser.newContext({ viewport: { width: 1440, height: 1000 },
    ...(process.env.OOPY_DASHBOARD_STORAGE_STATE ? { storageState: process.env.OOPY_DASHBOARD_STORAGE_STATE } : {}) });
await cacheAssets(context);
const page = await context.newPage();
const errors = [];
page.on('pageerror', error => errors.push(error.message));
page.on('console', message => { if (message.type() === 'error') errors.push(message.text()); });
const widths = [320, 360, 390, 768, 1024, 1366, 1440, 1600];
const menu = page.getByRole('button', { name: 'Buka menu navigasi', exact: true });
const mobileNavigation = page.locator('.site-navbar');
const sidebar = page.locator('#oopyDashboardSidebar');
const disclosure = page.locator('.oopy-dashboard-additional');
const summary = disclosure.locator('summary');
const contrast = async () => {
    const pairs = await page.locator('.oopy-dashboard-page').evaluate(body => [...body.querySelectorAll('*')]
        .filter(node => node.checkVisibility() && !node.closest('.visually-hidden') && !node.closest('script')
            && [...node.childNodes].some(child => child.nodeType === Node.TEXT_NODE && child.textContent.trim()))
        .map(node => {
            const style = getComputedStyle(node);
            let surface = node;
            while (getComputedStyle(surface).backgroundColor === 'rgba(0, 0, 0, 0)' && surface.parentElement) surface = surface.parentElement;
            return { text: node.textContent.trim(), color: style.color, background: getComputedStyle(surface).backgroundColor };
        }));
    const luminance = color => color.match(/[\d.]+/g).slice(0, 3).map(Number)
        .map(value => value / 255).map(c => c <= .04045 ? c / 12.92 : ((c + .055) / 1.055) ** 2.4)
        .reduce((sum, c, index) => sum + c * [.2126, .7152, .0722][index], 0);
    const ratios = pairs.map(pair => {
        const values = [luminance(pair.color), luminance(pair.background)].sort((a, b) => b - a);
        const ratio = (values[0] + .05) / (values[1] + .05);
        assert.ok(ratio >= 4.5, `${pair.text}: contrast ${ratio.toFixed(2)}`);
        return ratio;
    });
    return Math.min(...ratios).toFixed(2);
};

try {
    if (!process.env.OOPY_DASHBOARD_STORAGE_STATE) await registerAccount(page, base);
    const response = await page.goto(`${base}/dashboard`, { waitUntil: 'networkidle' });
    assert.equal(response.status(), 200);
    assert.equal(response.headers()['cache-control'], 'no-store, private');
    await page.evaluate(() => document.fonts.ready);
    assert.equal(await page.locator('h1').count(), 1);
    assert.equal(await page.getByRole('table').count(), 1);
    assert.equal(await page.locator('[data-dashboard="continue"]').count(), 1);
    await summary.focus();
    await page.keyboard.press('Enter');
    assert.equal(await disclosure.getAttribute('open'), '');
    assert.notEqual(await summary.evaluate(node => getComputedStyle(node).outlineStyle), 'none');
    console.log(`PASS text contrast: minimum ${await contrast()}:1, including expanded information and sidebar`);
    await summary.press('Space');
    assert.equal(await disclosure.getAttribute('open'), null);
    assert.equal(await page.locator('.oopy-dashboard-menu-toggle,.oopy-dashboard-toolbar').count(), 0);
    assert.equal(await sidebar.evaluate(node => node.inert), false);
    await sidebar.getByRole('link', { name: 'Informasi', exact: true }).focus();
    await page.keyboard.press('Enter');
    assert.equal(await disclosure.getAttribute('open'), '');
    assert.equal(await sidebar.getByRole('link', { name: 'Informasi', exact: true }).getAttribute('aria-current'), 'location');
    assert.equal(await summary.evaluate(node => node === document.activeElement), true);
    await sidebar.getByRole('link', { name: 'Profil Saya', exact: true }).click();
    await page.waitForFunction(() => {
        const top = document.getElementById('profil').getBoundingClientRect().top;
        const header = document.querySelector('.oopy-dashboard-topbar').getBoundingClientRect().bottom;
        return top >= header && top <= header + 48;
    });
    const profilePosition = await page.locator('#profil').evaluate(node => node.getBoundingClientRect().top);
    assert.ok(profilePosition >= await page.locator('.oopy-dashboard-topbar').evaluate(node => node.getBoundingClientRect().bottom));
    assert.equal(await sidebar.getByRole('link', { name: 'Profil Saya', exact: true }).getAttribute('aria-current'), 'location');
    assert.equal(await page.locator('#profile-title').evaluate(node => node === document.activeElement), true);
    await page.evaluate(() => window.scrollTo({ top: 0, behavior: 'instant' }));
    await sidebar.getByRole('link', { name: 'Profil Saya', exact: true }).click();
    await page.waitForFunction(() => {
        const top = document.getElementById('profil').getBoundingClientRect().top;
        const header = document.querySelector('.oopy-dashboard-topbar').getBoundingClientRect().bottom;
        return top >= header && top <= header + 48;
    });
    await page.goBack();
    await page.waitForURL(`${base}/dashboard#informasi`);
    assert.equal(await sidebar.getByRole('link', { name: 'Informasi', exact: true }).getAttribute('aria-current'), 'location');
    await page.goForward();
    await page.waitForURL(`${base}/dashboard#profil`);
    assert.equal(await page.locator('#profile-title').evaluate(node => node === document.activeElement), true);
    console.log('PASS no standalone dashboard button, desktop sidebar, profile anchor, information link, disclosure Enter/Space and visible focus');

    await page.locator('[data-dashboard-logout] button').click();
    assert.equal(await page.getByRole('dialog').isVisible(), true);
    await page.setViewportSize({ width: 390, height: 1000 });
    await page.waitForFunction(() => document.getElementById('oopyDashboardSidebar').inert);
    await page.keyboard.press('Escape');
    assert.equal(await page.getByRole('dialog').isVisible(), false);
    await page.waitForFunction(() => document.activeElement === document.querySelector('.site-navbar .navbar-toggler'));
    assert.equal(await menu.evaluate(node => node === document.activeElement), true);
    await menu.click();
    await page.locator('#navbarOOPy.collapsing').waitFor({ state: 'hidden' });
    assert.equal(await page.locator('main').evaluate(node => node.inert), false);
    assert.equal(await sidebar.evaluate(node => node.inert), true);
    await disclosure.evaluate(node => node.open = false);
    await mobileNavigation.getByRole('link', { name: 'Informasi', exact: true }).click();
    await page.locator('#navbarOOPy').waitFor({ state: 'hidden' });
    assert.equal(await disclosure.getAttribute('open'), '');
    assert.equal(await menu.getAttribute('aria-expanded'), 'false');
    assert.equal(await page.locator('main').evaluate(node => node.inert), false);
    assert.equal(await summary.evaluate(node => node === document.activeElement), true);
    assert.equal(await sidebar.getByRole('link', { name: 'Informasi', exact: true, includeHidden: true }).getAttribute('aria-expanded'), 'true');
    await summary.press('Enter');
    await page.waitForFunction(() => document.querySelector('a[href="#informasi"]').getAttribute('aria-expanded') === 'false');
    await menu.click();
    await page.locator('#navbarOOPy.collapsing').waitFor({ state: 'hidden' });
    await mobileNavigation.getByRole('link', { name: 'Informasi', exact: true }).click();
    await page.locator('#navbarOOPy').waitFor({ state: 'hidden' });
    assert.equal(await disclosure.getAttribute('open'), '');
    await page.reload({ waitUntil: 'networkidle' });
    assert.equal(await disclosure.getAttribute('open'), '');
    assert.equal(await summary.evaluate(node => node === document.activeElement), true);
    await menu.click();
    await page.locator('#navbarOOPy.collapsing').waitFor({ state: 'hidden' });
    await page.locator('#oopyAccountMenu').click();
    const logout = mobileNavigation.getByRole('button', { name: 'Logout', exact: true });
    await logout.click();
    const dialog = page.getByRole('dialog');
    assert.equal(await dialog.isVisible(), true);
    await dialog.getByRole('button', { name: 'Batal', exact: true }).click();
    assert.equal(await dialog.isVisible(), false);
    assert.equal(await logout.evaluate(node => node === document.activeElement), true);
    await logout.click();
    await page.keyboard.press('Escape');
    assert.equal(await dialog.isVisible(), false);
    await page.keyboard.press('Escape');
    await menu.click();
    await page.locator('#navbarOOPy').waitFor({ state: 'hidden' });
    console.log('PASS mobile navbar profile/information, section focus, repeated clicks, Back/Forward, fragment reload, disclosure state and logout cancellation');

    await page.emulateMedia({ reducedMotion: 'reduce' });
    assert.equal(await page.locator('.oopy-dashboard-disclosure').evaluate(node => getComputedStyle(node).transitionDuration), '0s');
    for (const width of widths) {
        await page.setViewportSize({ width, height: 1000 });
        await page.evaluate(() => document.documentElement.style.fontSize = '200%');
        const dimensions = await page.evaluate(() => ({ width: innerWidth, scroll: document.documentElement.scrollWidth,
            controls: [...document.querySelectorAll('.oopy-dashboard a,.oopy-dashboard-topbar a,.oopy-dashboard-topbar button')]
                .filter(node => node.checkVisibility()).map(node => {
                    const rect = node.getBoundingClientRect();
                    return { text: node.textContent.trim(), left: rect.left, right: rect.right, height: rect.height };
                }) }));
        assert.ok(dimensions.scroll <= width + 1, `200% text at ${width}: ${JSON.stringify(dimensions)}`);
        assert.ok(dimensions.controls.every(node => node.left >= 0 && node.right <= width + 1 && node.height >= 44), JSON.stringify(dimensions));
        await page.evaluate(() => document.documentElement.style.fontSize = '');
    }
    for (const [link, path] of [
        [page.locator('.site-navbar .navbar-brand'), '/'],
        [page.locator('.site-navbar').getByRole('link', { name: 'Beranda', exact: true }), '/'],
        [page.locator('.oopy-dashboard-menu a[href$="/materi"]'), '/materi'],
    ]) {
        await link.click();
        await page.waitForURL(`${base}${path}`);
        await page.goto(`${base}/dashboard`, { waitUntil: 'networkidle' });
    }
    const next = page.locator('[data-dashboard="continue"]');
    const destination = await next.getAttribute('href');
    await next.focus();
    await page.keyboard.press('Enter');
    await page.waitForURL(destination);
    console.log('PASS logo and Beranda open home, Belajar opens the catalog, and recommendation opens its real destination with Enter');
    assert.deepEqual(errors, []);
    console.log('PASS 200% text resize at eight widths, reduced motion, semantic table, private no-store response and no console errors');
} finally {
    await browser.close();
}
