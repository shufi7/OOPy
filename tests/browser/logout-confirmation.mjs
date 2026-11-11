// Run only against the isolated admin-sandbox.php fixture.
import { chromium } from 'playwright';
import assert from 'node:assert/strict';
import { mkdir, readFile, writeFile } from 'node:fs/promises';
import { join } from 'node:path';

assert.match(process.env.OOPY_ADMIN_TEST_DATABASE || '', /^admin-coding-[a-f0-9]{32}\.sqlite$/);
const base = process.env.OOPY_BASE_URL;
assert.match(base || '', /^http:\/\/127\.0\.0\.1:\d+$/);
const directory = process.env.OOPY_SCREENSHOT_DIR;
if (directory) await mkdir(directory, { recursive: true });
const browser = await chromium.launch({ channel: process.env.OOPY_BROWSER || 'msedge', headless: true });
const results = { interactions: [], layouts: [], contrast: [], errors: [] };
const assets = process.env.OOPY_ASSET_MANIFEST ? JSON.parse(await readFile(process.env.OOPY_ASSET_MANIFEST, 'utf8')) : {};
const record = message => { results.interactions.push(message); console.log(`PASS ${message}`); };
const makeContext = async options => {
    const context = await browser.newContext({ viewport: { width: 1440, height: 1000 }, ...options });
    await context.route('**/*', route => {
        const url = route.request().url();
        if (!assets[url]) return route.continue();
        const path = new URL(url).pathname;
        const contentType = url.includes('fonts.googleapis.com') || path.endsWith('.css') ? 'text/css'
            : path.endsWith('.js') ? 'application/javascript' : path.endsWith('.ttf') ? 'font/ttf'
                : path.endsWith('.woff2') ? 'font/woff2' : 'application/octet-stream';
        return route.fulfill({ path: assets[url], contentType, headers: { 'access-control-allow-origin': '*' } });
    });
    context.on('page', page => page.on('pageerror', error => results.errors.push(error.message)));
    return context;
};
const login = async (page, admin = false) => {
    await page.goto(base + '/login');
    await page.locator('#email').fill(admin ? 'admin.browser@example.test' : 'student.browser@example.test');
    await page.locator('#password').fill('password123');
    await Promise.all([page.waitForURL(url => url.pathname !== '/login'), page.locator('button[type="submit"]').click()]);
};
const triggerFor = async (page, kind) => {
    if (kind === 'navbar') {
        if (!await page.locator('#oopyAccountMenu').isVisible()) await page.locator('.navbar-toggler').click();
        if (await page.locator('#oopyAccountMenu').getAttribute('aria-expanded') !== 'true') await page.locator('#oopyAccountMenu').click();
    } else if (kind === 'admin') {
        if (!await page.locator('.admin-logout').isVisible()) await page.locator('#admin-menu-toggle').click();
    } else if (await page.locator('.oopy-dashboard-menu-toggle').getAttribute('aria-expanded') === 'false') {
        await page.locator('.oopy-dashboard-menu-toggle').click();
    }
    return page.locator('[data-logout-confirmation] button[type="submit"]');
};
const luminance = rgb => rgb.match(/[\d.]+/g).slice(0, 3).map(Number).map(c => c / 255)
    .map(c => c <= .04045 ? c / 12.92 : ((c + .055) / 1.055) ** 2.4)
    .reduce((sum, c, i) => sum + c * [.2126, .7152, .0722][i], 0);
const contrast = async (locator, state) => {
    const pair = await locator.evaluate(el => {
        const style = getComputedStyle(el);
        let background = style.backgroundColor;
        for (let parent = el.parentElement; background === 'rgba(0, 0, 0, 0)' && parent; parent = parent.parentElement) background = getComputedStyle(parent).backgroundColor;
        return { color: style.color, background };
    });
    const values = [luminance(pair.color), luminance(pair.background)];
    const ratio = (Math.max(...values) + .05) / (Math.min(...values) + .05);
    assert.ok(ratio >= 4.5, JSON.stringify({ state, pair, ratio }));
    results.contrast.push({ state, ...pair, ratio });
};
try {
    for (const kind of ['navbar', 'dashboard', 'admin']) {
        const context = await makeContext();
        const page = await context.newPage();
        await login(page, kind === 'admin');
        let posts = 0;
        page.on('request', request => { if (request.url() === base + '/logout' && request.method() === 'POST') posts++; });
        const path = kind === 'navbar' ? '/' : kind === 'admin' ? '/admin' : '/dashboard';
        const protectedPath = kind === 'admin' ? '/admin' : '/dashboard';
        const dialog = page.getByRole('dialog', { name: 'Yakin ingin keluar dari OOPy?' });
        const cancel = dialog.getByRole('button', { name: 'Batal', exact: true });
        const confirm = dialog.getByRole('button', { name: 'Ya, Logout', exact: true });
        for (const width of [320, 390, 768, 1024, 1440]) {
            await page.setViewportSize({ width, height: 1000 });
            await page.goto(base + path);
            const trigger = await triggerFor(page, kind);
            await trigger.focus();
            await page.keyboard.press('Enter');
            await dialog.waitFor();
            assert.equal(await page.locator('#oopy-logout-dialog').count(), 1);
            assert.equal(await cancel.evaluate(el => el === document.activeElement), true);
            assert.equal(posts, 0);
            assert.equal((await context.request.get(base + protectedPath, { maxRedirects: 0 })).status(), 200);
            await page.keyboard.press('Tab');
            assert.equal(await confirm.evaluate(el => el === document.activeElement), true);
            await page.keyboard.press('Shift+Tab');
            assert.equal(await cancel.evaluate(el => el === document.activeElement), true);
            for (let i = 0; i < 6; i++) {
                await page.keyboard.press('Tab');
                assert.equal(await page.evaluate(() => document.activeElement === document.body || document.querySelector('#oopy-logout-dialog').contains(document.activeElement)), true);
            }
            await page.locator('main').evaluate(el => el.focus());
            assert.equal(await dialog.evaluate(el => el.contains(document.activeElement) || document.activeElement === document.body), true);
            const layout = await dialog.evaluate(el => {
                const r = el.getBoundingClientRect();
                return { width: innerWidth, scroll: document.documentElement.scrollWidth, left: r.left, right: r.right, content: el.scrollWidth, client: el.clientWidth };
            });
            assert.ok(layout.scroll <= width + 1 && layout.left >= 0 && layout.right <= width && layout.content <= layout.client + 1, JSON.stringify(layout));
            results.layouts.push({ kind, ...layout });
            for (const button of [cancel, confirm]) {
                const box = await button.boundingBox();
                assert.ok(box.width >= 44 && box.height >= 44);
                await contrast(button, `${kind}-${width}-normal`);
                await button.hover();
                await contrast(button, `${kind}-${width}-hover`);
                await button.focus();
                assert.notEqual(await button.evaluate(el => getComputedStyle(el).outlineStyle), 'none');
            }
            await contrast(dialog.locator('h2'), `${kind}-${width}-title`);
            await contrast(dialog.locator('p'), `${kind}-${width}-description`);
            if (directory && [390, 1440].includes(width)) await page.screenshot({ path: join(directory, `${kind}-${width}.png`) });
            await page.keyboard.press('Escape');
            await dialog.waitFor({ state: 'hidden' });
            await page.waitForFunction(() => document.activeElement?.matches('[data-logout-confirmation] button[type="submit"]'));
            if (kind === 'admin' && width < 992) assert.equal(await page.locator('#admin-menu-toggle').getAttribute('aria-expanded'), 'true');
            if (kind === 'dashboard' && width < 992) assert.equal(await page.locator('.oopy-dashboard-menu-toggle').getAttribute('aria-expanded'), 'true');
            await trigger.click();
            await cancel.click();
            await dialog.waitFor({ state: 'hidden' });
            await page.waitForFunction(() => document.activeElement?.matches('[data-logout-confirmation] button[type="submit"]'));
            await page.reload();
            assert.equal((await context.request.get(base + protectedPath, { maxRedirects: 0 })).status(), 200);
        }
        record(`${kind}: 320/390/768/1024/1440px, cancel, Escape, Enter, focus restoration, keyboard, contrast, session and refresh`);
        await page.addScriptTag({ url: base + '/js/oopy-logout.js' });
        if (kind !== 'navbar') {
            await page.setViewportSize({ width: 1440, height: 1000 });
            await (await triggerFor(page, kind)).click();
            await page.setViewportSize({ width: 390, height: 1000 });
            if (kind === 'admin') await page.locator('#admin-sidebar').waitFor({ state: 'hidden' });
            else await page.waitForFunction(() => document.querySelector('#oopyDashboardSidebar').inert);
            await page.keyboard.press('Escape');
            const menuSelector = kind === 'admin' ? '#admin-menu-toggle' : '.oopy-dashboard-menu-toggle';
            await page.waitForFunction(selector => document.activeElement === document.querySelector(selector), menuSelector);
            record(`${kind}: resizing while modal is open restores focus to the visible menu when the sidebar closes`);
        }
        const trigger = await triggerFor(page, kind);
        await trigger.click();
        await dialog.getByRole('button', { name: 'Batal', exact: true }).evaluate(el => { el.click(); el.click(); });
        await trigger.evaluate(el => el.click());
        await dialog.waitFor();
        await cancel.click();
        await page.waitForFunction(() => !document.querySelector('#oopy-logout-dialog').open);
        await trigger.evaluate(el => { el.click(); el.click(); el.click(); });
        await dialog.waitFor();
        assert.equal(posts, 0);
        await Promise.all([
            page.waitForResponse(r => r.url() === base + '/logout' && r.request().method() === 'POST'),
            confirm.evaluate(el => { el.click(); el.click(); el.click(); }),
        ]);
        await page.waitForURL(base + '/');
        assert.equal(posts, 1);
        assert.equal((await context.request.get(base + protectedPath, { maxRedirects: 0 })).status(), 302);
        record(`${kind}: rapid opening and confirmation sends exactly one POST and denies protected access`);
        await context.close();
    }
    for (const mode of ['disabled', 'missing-script', 'unsupported-dialog']) {
        const context = await makeContext({ javaScriptEnabled: mode !== 'disabled' });
        if (mode === 'missing-script') await context.route('**/js/oopy-logout.js', route => route.abort());
        if (mode === 'unsupported-dialog') await context.addInitScript(() => { HTMLDialogElement.prototype.showModal = undefined; });
        const page = await context.newPage();
        await login(page, true);
        await page.goto(base + '/admin');
        await Promise.all([page.waitForURL(base + '/'), page.locator('.admin-logout').click()]);
        assert.equal((await context.request.get(base + '/admin', { maxRedirects: 0 })).status(), 302);
        record(`${mode}: original POST fallback logs out`);
        await context.close();
    }
    for (const path of ['/', '/dashboard']) {
        const context = await makeContext({ javaScriptEnabled: false, viewport: { width: 320, height: 1000 } });
        const page = await context.newPage();
        await login(page);
        await page.goto(base + path);
        await Promise.all([
            page.waitForResponse(r => r.url() === base + '/logout' && r.request().method() === 'POST'),
            page.locator('[data-logout-confirmation] button[type="submit"]').click(),
        ]);
        assert.equal((await context.request.get(base + '/dashboard', { maxRedirects: 0 })).status(), 302);
        record(`User ${path}: logout remains reachable without JavaScript at 320px`);
        await context.close();
    }
    const context = await makeContext({ reducedMotion: 'reduce' });
    const page = await context.newPage();
    await login(page);
    await page.goto(base + '/');
    let trigger = await triggerFor(page, 'navbar');
    await trigger.click();
    await page.evaluate(() => document.documentElement.style.fontSize = '200%');
    await page.setViewportSize({ width: 320, height: 640 });
    assert.equal(await page.locator('[data-logout-confirm]').isVisible(), true);
    assert.equal(await page.locator('#oopy-logout-dialog').evaluate(el => el.scrollWidth <= el.clientWidth + 1 && getComputedStyle(el).transitionDuration === '0s'), true);
    await page.getByRole('button', { name: 'Batal', exact: true }).click();
    record('200% text at 320px and reduced motion remain usable');
    await page.goto(base + '/');
    const token = await page.locator('meta[name="csrf-token"]').getAttribute('content');
    assert.equal((await context.request.post(base + '/logout', { form: { _token: 'invalid' }, maxRedirects: 0 })).status(), 419);
    assert.equal((await context.request.get(base + '/dashboard', { maxRedirects: 0 })).status(), 200);
    // Invalidate the server-side session while leaving the current document open.
    assert.equal((await context.request.post(base + '/logout', { form: { _token: token }, maxRedirects: 0 })).status(), 302);
    trigger = await triggerFor(page, 'navbar');
    await trigger.click();
    const response = page.waitForResponse(r => r.url() === base + '/logout' && r.request().method() === 'POST');
    await page.getByRole('button', { name: 'Ya, Logout', exact: true }).click();
    assert.equal((await response).status(), 419);
    record('Invalid CSRF retains authentication; stale document after session invalidation receives 419');
    await context.close();
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
