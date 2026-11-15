// Use a disposable admin-sandbox.php database; registration writes only to that fixture.
import { chromium } from 'playwright';
import assert from 'node:assert/strict';
import { mkdir, readFile, writeFile } from 'node:fs/promises';
import { join } from 'node:path';
import { randomUUID } from 'node:crypto';

assert.match(process.env.OOPY_ADMIN_TEST_DATABASE || '', /^admin-coding-[a-f0-9]{32}\.sqlite$/);
const base = process.env.OOPY_BASE_URL;
assert.match(base || '', /^http:\/\/127\.0\.0\.1:\d+$/);
const directory = process.env.OOPY_SCREENSHOT_DIR;
if (directory) await mkdir(directory, { recursive: true });
const assets = process.env.OOPY_ASSET_MANIFEST ? JSON.parse(await readFile(process.env.OOPY_ASSET_MANIFEST, 'utf8')) : {};
const widths = [320, 360, 390, 768, 1024, 1366, 1440];
const results = { layouts: [], colors: [], interactions: [], errors: [] };
const browser = await chromium.launch({ channel: process.env.OOPY_BROWSER || 'msedge', headless: true });
const context = await browser.newContext({ viewport: { width: 1440, height: 1000 }, reducedMotion: 'no-preference' });
await context.route('**/*', route => {
    const url = route.request().url();
    if (!assets[url]) return route.continue();
    const path = new URL(url).pathname;
    const contentType = url.includes('fonts.googleapis.com') || path.endsWith('.css') ? 'text/css'
        : path.endsWith('.js') ? 'application/javascript' : path.endsWith('.woff2') ? 'font/woff2'
            : path.endsWith('.ttf') ? 'font/ttf' : 'application/octet-stream';
    return route.fulfill({ path: assets[url], contentType, headers: { 'access-control-allow-origin': '*' } });
});
const page = await context.newPage();
page.on('pageerror', error => results.errors.push(error.message));
const nav = page.locator('.site-navbar');
const menu = page.getByRole('button', { name: 'Buka menu navigasi', exact: true });
const record = text => { results.interactions.push(text); console.log(`PASS ${text}`); };
const settle = () => page.evaluate(() => new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(resolve))));
const visit = async path => {
    assert.equal((await page.goto(base + path, { waitUntil: 'load' })).status(), 200, path);
    await page.evaluate(() => document.fonts.ready);
};
const openMenu = async width => {
    if (width < 992 && await menu.getAttribute('aria-expanded') !== 'true') {
        await menu.click();
        await page.locator('#navbarOOPy.show').waitFor();
    }
};
const luminance = color => {
    const [r, g, b] = color.match(/[\d.]+/g).slice(0, 3).map(Number).map(value => {
        const c = value / 255;
        return c <= .04045 ? c / 12.92 : ((c + .055) / 1.055) ** 2.4;
    });
    return .2126 * r + .7152 * g + .0722 * b;
};
const ratio = (a, b) => (Math.max(luminance(a), luminance(b)) + .05) / (Math.min(luminance(a), luminance(b)) + .05);
const contrast = async (control, state) => {
    const pair = await control.evaluate(el => {
        el.getAnimations().forEach(animation => animation.finish());
        const style = getComputedStyle(el);
        let background = style.backgroundColor;
        for (let parent = el.parentElement; background === 'rgba(0, 0, 0, 0)' && parent; parent = parent.parentElement) background = getComputedStyle(parent).backgroundColor;
        return { color: style.color, background, outline: style.outlineColor, outlineWidth: style.outlineWidth, focus: el.matches(':focus-visible') };
    });
    const value = ratio(pair.color, pair.background);
    assert.ok(value >= 4.5, JSON.stringify({ state, pair, value }));
    results.colors.push({ state, ...pair, ratio: value });
    return pair;
};
const transformY = control => control.evaluate(el => {
    el.getAnimations().forEach(animation => animation.finish());
    const value = getComputedStyle(el).transform;
    return value === 'none' ? 0 : new DOMMatrixReadOnly(value).m42;
});
const layout = async (state, width) => {
    await settle();
    const data = await nav.evaluate(el => ({
        width: innerWidth, scroll: document.documentElement.scrollWidth,
        left: el.getBoundingClientRect().left, right: el.getBoundingClientRect().right,
        controls: [...el.querySelectorAll('.navbar-brand,.navbar-toggler,.nav-link,.btn-brand')]
            .filter(item => item.checkVisibility()).map(item => {
                const r = item.getBoundingClientRect();
                return { name: item.textContent.trim(), left: r.left, right: r.right, width: r.width, height: r.height };
            }),
    }));
    assert.ok(data.scroll <= width + 1 && data.left >= 0 && data.right <= width, `${state}: ${JSON.stringify(data)}`);
    for (const control of data.controls) {
        assert.ok(control.height >= 44 && control.width >= 44, `${state}: ${JSON.stringify(control)}`);
        assert.ok(control.left >= data.left && control.right <= data.right + 1, `${state}: control outside navbar`);
    }
    results.layouts.push({ state, ...data });
};
const screenshot = async name => {
    if (!directory) return;
    await page.evaluate(() => window.scrollTo(0, 0));
    const width = page.viewportSize().width;
    let bottom = await nav.evaluate(el => el.getBoundingClientRect().bottom);
    const dropdown = page.locator('.oopy-account-menu.show');
    if (await dropdown.isVisible()) bottom = Math.max(bottom, await dropdown.evaluate(el => el.getBoundingClientRect().bottom));
    await page.screenshot({ path: join(directory, `${name}.png`), clip: { x: 0, y: 0, width, height: Math.ceil(bottom + 16) } });
};

try {
    for (const [path, current] of [
        ['/', 'Beranda'], ['/materi', 'Materi'], ['/coding', 'Coding'], ['/login', 'Masuk'], ['/register', 'Daftar'],
        ['/materi/evaluasi-akhir', 'Materi'], ['/materi/evaluasi-akhir/ujian', 'Materi'], ['/materi/evaluasi-akhir/hasil', 'Materi'],
    ]) {
        for (const width of widths) {
            await page.setViewportSize({ width, height: 1000 });
            await visit(path);
            await layout(`${path} closed`, width);
            await openMenu(width);
            const active = nav.locator('a[aria-current="page"]');
            assert.equal(await active.count(), 1, path);
            assert.equal((await active.innerText()).trim(), current);
            assert.equal(await active.evaluate(el => getComputedStyle(el).color), 'rgb(255, 255, 255)');
            await contrast(active, `${path} ${width} current`);
            await layout(`${path} open`, width);
            if (path === '/' && [390, 1440].includes(width)) await screenshot(`guest-${width}`);
            if (width < 992) {
                await menu.focus();
                await page.keyboard.press('Enter');
                await page.locator('#navbarOOPy').waitFor({ state: 'hidden' });
                assert.equal(await menu.getAttribute('aria-expanded'), 'false');
                await page.keyboard.press('Space');
                await page.locator('#navbarOOPy.show').waitFor();
            }
        }
    }
    record('Eight guest routes each have one correct current destination across seven widths; mobile Enter/Space opens and closes Bootstrap collapse');

    await page.setViewportSize({ width: 1440, height: 1000 });
    await visit('/');
    const other = nav.getByRole('link', { name: 'Coding', exact: true });
    const otherBox = await other.boundingBox();
    for (const name of ['Materi', 'Beranda', 'Daftar']) {
        const control = nav.getByRole('link', { name, exact: true });
        await page.mouse.move(0, 0);
        await contrast(control, `${name} normal`);
        await control.hover();
        await contrast(control, `${name} hover`);
        assert.equal(await transformY(control), -1);
        assert.equal((await other.boundingBox()).x, otherBox.x, 'Hover must not shift neighbouring controls');
        await page.mouse.down();
        await contrast(control, `${name} pressed`);
        assert.equal(await transformY(control), 2);
        if (directory) await screenshot(`${name.toLowerCase()}-pressed`);
        await page.mouse.move(0, 0);
        await page.mouse.up();
        await page.keyboard.press('Tab');
        await control.focus();
        const focus = await contrast(control, `${name} focus`);
        assert.equal(focus.focus, true);
        assert.ok(parseFloat(focus.outlineWidth) >= 3 && ratio(focus.outline, 'rgb(255,255,255)') >= 3);
    }
    for (const endpoint of ['rgb(6,95,99)', 'rgb(8,127,128)', 'rgb(5,78,82)', 'rgb(7,109,113)']) assert.ok(ratio('rgb(255,255,255)', endpoint) >= 4.5);
    await nav.getByRole('link', { name: 'Materi', exact: true }).focus();
    await page.keyboard.press('Enter');
    await page.waitForURL(base + '/materi');
    assert.equal((await nav.locator('[aria-current="page"]').innerText()).trim(), 'Materi');
    record('Normal, hover, current, pressed and focus states pass contrast; transforms are -1/+2px and neighbours do not move; Enter follows the actual route');

    await page.emulateMedia({ reducedMotion: 'reduce' });
    await visit('/');
    for (const name of ['Beranda', 'Materi', 'Daftar']) {
        const control = nav.getByRole('link', { name, exact: true });
        await control.hover();
        await page.mouse.down();
        assert.equal(await transformY(control), 0);
        assert.equal(await control.evaluate(el => getComputedStyle(el).transitionDuration), '0s');
        await page.mouse.move(0, 0);
        await page.mouse.up();
    }
    assert.match(await nav.locator('.nav-link.active').evaluate(el => getComputedStyle(el).backgroundImage), /linear-gradient/);
    await visit('/register');
    assert.equal(await nav.getByRole('link', { name: 'Daftar', exact: true }).evaluate(el => getComputedStyle(el).textDecorationLine), 'underline');
    record('Reduced motion removes transforms/transitions while preserving the current page; Register keeps its CTA identity and current-page underline');

    const longName = 'Pelajar ' + 'NamaPanjang'.repeat(18);
    await page.locator('#name').fill(longName);
    await page.locator('#email').fill(`navbar-${randomUUID()}@example.test`);
    await page.locator('#password').fill('password123');
    await page.locator('#password_confirmation').fill('password123');
    await Promise.all([page.waitForURL(base + '/materi'), page.locator('form.oopy-auth-form button[type="submit"]').click()]);
    for (const width of widths) {
        await page.setViewportSize({ width, height: 1000 });
        await visit('/');
        await openMenu(width);
        await layout('long name', width);
        assert.equal(await nav.locator('.oopy-account-name').innerText(), longName);
        const account = page.locator('#oopyAccountMenu');
        await account.focus();
        await page.keyboard.press('Enter');
        const dropdown = page.locator('.oopy-account-menu.show');
        await dropdown.waitFor();
        assert.equal(await dropdown.locator('.dropdown-item-text.fw-bold').innerText(), longName);
        const bounds = await dropdown.evaluate(el => ({ left: el.getBoundingClientRect().left, right: el.getBoundingClientRect().right, scroll: el.scrollWidth, width: el.clientWidth }));
        assert.ok(bounds.left >= 0 && bounds.right <= width + 1 && bounds.scroll <= bounds.width + 1, JSON.stringify(bounds));
        assert.equal(await dropdown.getByRole('link', { name: 'Panel Admin', exact: true }).count(), 0);
        await page.keyboard.press('ArrowDown');
        assert.equal(await page.evaluate(() => document.activeElement.textContent.trim()), 'Logout');
        await page.keyboard.press('Escape');
        await dropdown.waitFor({ state: 'hidden' });
        assert.equal(await account.evaluate(el => el === document.activeElement), true);
        if ([390, 1440].includes(width)) {
            await account.click();
            await screenshot(`account-${width}`);
            await page.keyboard.press('Escape');
        }
    }
    await page.setViewportSize({ width: 1440, height: 1000 });
    await visit('/');
    await nav.getByRole('link', { name: 'Dashboard', exact: true }).click();
    await page.waitForURL(base + '/dashboard');
    assert.equal(await page.locator('#oopyDashboardSidebar a[aria-current="page"]').count(), 1);
    record('A 206-character account name fits all widths, full text remains in the keyboard dropdown, ordinary users have no Admin link; Dashboard opens its existing sidebar layout');

    await visit('/');
    if (await page.locator('#oopyAccountMenu').getAttribute('aria-expanded') !== 'true') await page.locator('#oopyAccountMenu').click();
    await page.locator('.oopy-account-menu [data-logout-confirmation] button').click();
    const dialog = page.getByRole('dialog', { name: 'Yakin ingin keluar dari OOPy?', exact: true });
    await dialog.waitFor();
    await dialog.getByRole('button', { name: 'Batal', exact: true }).click();
    await dialog.waitFor({ state: 'hidden' });
    assert.equal((await context.request.get(base + '/dashboard', { maxRedirects: 0 })).status(), 200);
    if (await page.locator('#oopyAccountMenu').getAttribute('aria-expanded') !== 'true') await page.locator('#oopyAccountMenu').click();
    await page.locator('.oopy-account-menu [data-logout-confirmation] button').click();
    await dialog.getByRole('button', { name: 'Ya, Logout', exact: true }).click();
    await page.waitForURL(base + '/');
    assert.equal((await context.request.get(base + '/dashboard', { maxRedirects: 0 })).status(), 302);
    await visit('/login');
    await page.locator('#email').fill('admin.browser@example.test');
    await page.locator('#password').fill('password123');
    await Promise.all([page.waitForURL(url => url.pathname !== '/login'), page.locator('form button[type="submit"]').click()]);
    await visit('/');
    await page.locator('#oopyAccountMenu').click();
    await page.getByRole('link', { name: 'Panel Admin', exact: true }).click();
    await page.waitForURL(base + '/admin');
    record('Logout cancellation retains authentication, confirmation ends it, and Admin dropdown opens the existing Admin panel');
    assert.deepEqual(results.errors, []);
    results.status = 'PASS';
} catch (error) {
    results.status = 'FAIL';
    results.failure = error.stack;
    throw error;
} finally {
    if (directory) await writeFile(join(directory, 'results.json'), JSON.stringify(results, null, 2));
    await browser.close();
}
