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
const nav = page.locator('.site-navbar');
const mainMenu = nav.getByRole('button', { name: 'Buka menu navigasi', exact: true });
const settle = () => page.evaluate(() => new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(resolve))));

try {
    if (!process.env.OOPY_DASHBOARD_STORAGE_STATE) await registerAccount(page, base);
    let signature;
    for (const [path, active] of [['/', 'Beranda'], ['/materi', 'Materi'], ['/coding', 'Coding'], ['/dashboard', 'Dashboard']]) {
        assert.equal((await page.goto(`${base}${path}`, { waitUntil: 'networkidle' })).status(), 200);
        await page.evaluate(() => document.fonts.ready);
        assert.equal(await nav.count(), 1);
        const current = await nav.evaluate(node => {
            const style = getComputedStyle(node);
            const logo = node.querySelector('.navbar-logo');
            return { background: style.backgroundColor, border: style.borderRadius, shadow: style.boxShadow,
                logo: [logo.src, logo.width, logo.height],
                links: [...node.querySelectorAll('a:not([data-dashboard-section])')].map(link => [link.textContent.trim(), link.href]),
                font: getComputedStyle(node.querySelector('.nav-link')).fontSize };
        });
        if (signature) assert.deepEqual(current, signature, `${path}: same shared navbar appearance and destinations`);
        else signature = current;
        assert.equal((await nav.locator('a[aria-current="page"]').innerText()).trim(), active);
    }
    console.log('PASS Beranda, Materi, Coding and Dashboard share one identical navbar with correct destinations and current page');
    assert.equal(await page.locator('.oopy-dashboard-menu-toggle,.oopy-dashboard-toolbar,.oopy-dashboard-backdrop').count(), 0);

    for (const width of [320, 360, 390, 768, 1024, 1366, 1440, 1600]) {
        await page.setViewportSize({ width, height: 1000 });
        await settle();
        assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1), `Overflow ${width}`);
        if (width < 992) {
            await mainMenu.click();
            await page.locator('#navbarOOPy.show').waitFor();
            await page.locator('#navbarOOPy.collapsing').waitFor({ state: 'hidden' });
            assert.equal(await page.locator('#oopyDashboardSidebar').isVisible(), false);
            assert.equal(await page.locator('main').evaluate(node => node.inert), false);
            for (const hash of ['#profil', '#informasi']) {
                await nav.locator(`a[href="${hash}"]`).click();
                await page.locator('#navbarOOPy').waitFor({ state: 'hidden' });
                assert.equal(new URL(page.url()).hash, hash);
                if (hash === '#informasi') assert.equal(await page.locator('#informasi').getAttribute('open'), '');
                await mainMenu.click();
                await page.locator('#navbarOOPy.collapsing').waitFor({ state: 'hidden' });
            }
        }
        const account = nav.locator('#oopyAccountMenu');
        if (await account.getAttribute('aria-expanded') !== 'true') await account.click();
        const logout = nav.getByRole('button', { name: 'Logout', exact: true });
        await logout.click();
        assert.equal(await page.getByRole('dialog').isVisible(), true);
        await page.getByRole('button', { name: 'Batal', exact: true }).click();
        await nav.locator('.oopy-account-menu.show').waitFor();
        await page.keyboard.press('Escape');
        await nav.locator('.oopy-account-menu').waitFor({ state: 'hidden' });
        if (width < 992) {
            await mainMenu.click();
            await page.locator('#navbarOOPy').waitFor({ state: 'hidden' });
        }
        await settle();
        const position = await page.evaluate(() => ({
            header: document.querySelector('.oopy-dashboard-topbar').getBoundingClientRect().bottom,
            sidebar: document.querySelector('#oopyDashboardSidebar').getBoundingClientRect().top,
        }));
        if (width >= 992) assert.ok(Math.abs(position.header - position.sidebar) <= 1, JSON.stringify({ width, ...position }));
    }
    assert.deepEqual(errors, []);
    console.log('PASS eight widths: no standalone dashboard button, mobile profile/information links, navbar collapse, account dropdown/logout cancellation and automatic header offset');
} finally {
    await browser.close();
}
