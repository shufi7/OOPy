// Use a disposable seeded database: authenticated navigation creates a test account.
import { chromium } from 'playwright';
import assert from 'node:assert/strict';
import { mkdir, readFile, writeFile } from 'node:fs/promises';
import { join } from 'node:path';
import { registerAccount } from './quiz-helpers.mjs';

const base = process.env.OOPY_BASE_URL || 'http://127.0.0.1:8017';
const directory = process.env.OOPY_SCREENSHOT_DIR;
const baseline = process.argv.includes('--baseline');
const widths = [320, 360, 390, 768, 1024, 1366, 1440, 1920];
const results = { phase: baseline ? 'before' : 'after', layouts: [], colors: [], navigation: [], errors: [] };
if (directory) await mkdir(directory, { recursive: true });
const browser = await chromium.launch({ channel: process.env.OOPY_BROWSER || 'msedge', headless: true });
const context = await browser.newContext({ viewport: { width: 1440, height: 1000 }, reducedMotion: 'reduce' });
if (process.env.OOPY_ASSET_MANIFEST) {
    const assets = JSON.parse(await readFile(process.env.OOPY_ASSET_MANIFEST, 'utf8'));
    await context.route('**/*', route => {
        const url = route.request().url();
        if (!assets[url]) return route.continue();
        const path = new URL(url).pathname;
        const type = url.includes('fonts.googleapis.com') || path.endsWith('.css') ? 'text/css'
            : path.endsWith('.js') ? 'application/javascript' : path.endsWith('.wasm') ? 'application/wasm'
                : path.endsWith('.woff2') ? 'font/woff2' : path.endsWith('.woff') ? 'font/woff'
                    : path.endsWith('.ttf') ? 'font/ttf' : 'application/octet-stream';
        return route.fulfill({ path: assets[url], contentType: type, headers: { 'access-control-allow-origin': '*' } });
    });
}
const page = await context.newPage();
page.on('pageerror', error => results.errors.push(error.message));
const settle = () => page.evaluate(() => new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(resolve))));
const visit = async () => {
    assert.equal((await page.goto(base, { waitUntil: 'load' })).status(), 200);
    await page.evaluate(() => document.fonts.ready);
    await settle();
};
const layout = async state => {
    for (const width of widths) {
        await page.setViewportSize({ width, height: 1000 });
        await settle();
        const data = await page.evaluate(() => {
            const main = document.querySelector('main');
            const links = [...main.querySelectorAll('a')].filter(element => element.checkVisibility()).map(element => {
                const r = element.getBoundingClientRect();
                return { text: element.textContent.trim(), width: r.width, height: r.height, left: r.left, right: r.right, top: r.top, bottom: r.bottom };
            });
            const rect = selector => {
                const element = typeof selector === 'string' ? document.querySelector(selector) : selector;
                const r = element?.getBoundingClientRect();
                return r && { left: r.left, right: r.right, top: r.top, bottom: r.bottom, width: r.width, height: r.height };
            };
            return { width: innerWidth, scroll: document.documentElement.scrollWidth, font: getComputedStyle(document.body).fontFamily,
                title: rect('h1'), titleSize: parseFloat(getComputedStyle(document.querySelector('h1')).fontSize),
                description: rect('.hero-description'), actions: rect('.home-actions'), logo: rect('.hero-logo'),
                journey: rect('.home-learning'), steps: [...document.querySelectorAll('.home-step')].map(rect),
                arrows: [...document.querySelectorAll('.home-step-arrow')].filter(el => el.checkVisibility()).map(rect),
                features: rect('.home-features'), progress: rect('.home-account-note'), footer: rect('.site-footer'),
                clippedText: [...main.querySelectorAll('h1,h2:not(.visually-hidden),h3,p,dt,dd')]
                    .filter(el => el.scrollWidth > el.clientWidth + 1).map(el => el.textContent), links };
        });
        results.layouts.push({ state, ...data });
        assert.ok(data.scroll <= width + 1, `${state}: overflow at ${width}`);
        assert.match(data.font, /Plus Jakarta Sans/);
        for (const link of data.links) {
            assert.ok(link.width >= 44 && link.height >= 44 && link.left >= 0 && link.right <= width + 1, JSON.stringify(link));
        }
        if (!baseline) {
            assert.deepEqual(data.clippedText, [], `${state} ${width}: clipped text`);
            assert.ok(data.title.bottom <= data.description.top && data.description.bottom <= data.actions.top, `${state} ${width}: text overlap`);
            assert.ok(Math.abs(data.logo.width - data.logo.height) < 1, `${state} ${width}: mascot distorted`);
            assert.ok(data.logo.bottom <= data.journey.top && data.journey.bottom <= data.features.top, `${state} ${width}: sections overlap`);
            assert.ok(data.progress.bottom <= data.footer.top, `${state} ${width}: footer overlaps progress`);
            assert.equal(data.steps.length, 3);
            if (width < 768) {
                assert.ok(data.actions.bottom <= data.logo.top, `${state} ${width}: mobile hero order`);
                assert.ok(data.features.bottom <= data.progress.top, `${state} ${width}: mobile panel order`);
                for (let i = 1; i < data.steps.length; i++) assert.ok(data.steps[i - 1].bottom <= data.steps[i].top, `${state} ${width}: mobile step order`);
                assert.equal(data.arrows.length, 0);
            } else {
                assert.ok(data.description.right <= data.logo.left, `${state} ${width}: logo overlaps copy`);
                for (let i = 1; i < data.steps.length; i++) {
                    assert.ok(data.steps[i - 1].right <= data.steps[i].left, `${state} ${width}: cards overlap`);
                    assert.equal(data.steps[i].top, data.steps[0].top);
                    assert.equal(data.steps[i].height, data.steps[0].height);
                }
            }
            if (width >= 1366) {
                assert.ok(data.titleSize >= 48 && data.titleSize <= 52 && data.logo.width >= 330 && data.logo.width <= 390);
                assert.equal(data.arrows.length, 2);
                data.arrows.forEach((arrow, i) => assert.ok(arrow.left >= data.steps[i].right && arrow.right <= data.steps[i + 1].left, `${state} ${width}: arrow touches card`));
                assert.ok(data.features.right <= data.progress.left, `${state} ${width}: panels overlap`);
            }
        }
        if (directory) await page.screenshot({ path: join(directory, `${baseline ? 'before' : 'after'}-${state}-${width}.png`), fullPage: true, animations: 'disabled' });
    }
    console.log(`PASS ${baseline ? 'baseline' : 'home'} ${state}: ${widths.length} widths, typography and no overflow`);
};
const reflow = async state => {
    // 720 CSS pixels models the layout width of a 1440px desktop at 200% browser zoom.
    await page.setViewportSize({ width: 720, height: 500 });
    await settle();
    const data = await page.evaluate(() => ({
        width: innerWidth, scroll: document.documentElement.scrollWidth,
        clipped: [...document.querySelectorAll('main h1,main h2:not(.visually-hidden),main h3,main p,main dt,main dd')]
            .filter(el => el.scrollWidth > el.clientWidth + 1).map(el => el.textContent),
        mobileMenu: document.querySelector('.navbar-toggler').checkVisibility(),
        controls: [...document.querySelectorAll('.oopy-home a')].map(el => ({ width: el.getBoundingClientRect().width, height: el.getBoundingClientRect().height })),
    }));
    assert.ok(data.scroll <= data.width + 1 && data.mobileMenu);
    assert.deepEqual(data.clipped, []);
    data.controls.forEach(control => assert.ok(control.width >= 44 && control.height >= 44));
    results.layouts.push({ state, reflow: '1440px at 200% equivalent', ...data });
    if (directory) await page.screenshot({ path: join(directory, `after-${state}-200-percent-reflow.png`), fullPage: true, animations: 'disabled' });
    console.log(`PASS ${state}: equivalent 200% zoom reflow at 720 CSS pixels`);
    await page.setViewportSize({ width: 1440, height: 1000 });
    await settle();
};
const rgb = value => {
    const values = value.match(/[\d.]+/g).map(Number);
    return value.startsWith('color(srgb') ? values.map((v, i) => i < 3 ? v * 255 : v) : values;
};
const luminance = value => {
    const c = rgb(value).slice(0, 3).map(v => { v /= 255; return v <= .04045 ? v / 12.92 : ((v + .055) / 1.055) ** 2.4; });
    return .2126 * c[0] + .7152 * c[1] + .0722 * c[2];
};
const ratio = (a, b) => (Math.max(luminance(a), luminance(b)) + .05) / (Math.min(luminance(a), luminance(b)) + .05);
const auditText = async () => {
    const pairs = await page.locator('main').evaluate(main => [...main.querySelectorAll('*')].filter(element => element.checkVisibility()
        && [...element.childNodes].some(node => node.nodeType === 3 && node.textContent.trim())).map(element => {
            const style = getComputedStyle(element);
            let background = style.backgroundColor;
            for (let node = element.parentElement; background === 'rgba(0, 0, 0, 0)' && node; node = node.parentElement) background = getComputedStyle(node).backgroundColor;
            return { text: element.textContent.trim(), color: style.color, background, size: parseFloat(style.fontSize), weight: parseInt(style.fontWeight) };
        }));
    for (const pair of pairs) {
        const minimum = pair.size >= 24 || (pair.size >= 18.667 && pair.weight >= 700) ? 3 : 4.5;
        const contrast = ratio(pair.color, pair.background);
        results.colors.push({ ...pair, ratio: contrast, minimum });
        assert.ok(contrast >= minimum, `${pair.text}: ${contrast}`);
    }
};
const clickLink = async (name, path) => {
    await page.getByRole('link', { name, exact: true }).click();
    await page.waitForURL(url => url.pathname === path);
    results.navigation.push(`${name} -> ${path}`);
    await visit();
};

try {
    await visit();
    await layout('guest');
    if (!baseline) {
        assert.deepEqual(await page.locator('.home-step h3').allTextContents(), ['Pelajari Materi', 'Praktik Coding', 'Uji Pemahaman']);
        assert.deepEqual((await page.locator('.home-learning-list dt').allTextContents()).map(text => text.trim()), ['Materi per BAB', 'Kuis interaktif', 'Praktik Python di browser']);
        assert.equal(await page.locator('#home-account-title').textContent(), 'Simpan progres belajarmu.');
        assert.equal(await page.locator('.home-account-art svg[aria-hidden="true"][focusable="false"]').count(), 1);
        assert.equal(await page.locator('h1').textContent().then(text => text.replace(/\s+/g, ' ').trim()), 'Belajar OOP Python, langsung lewat praktik.');
        assert.equal(await page.locator('.stacked-card-back,.stacked-card-front').count(), 0);
        assert.equal(await page.locator('.hero-logo').getAttribute('src'), `${base}/images/oopy-logo.png`);
        await auditText();
        await reflow('guest');
        const primary = page.locator('.home-actions .btn-brand');
        const secondary = page.locator('.btn-home-secondary');
        for (const [name, control] of [['primary', primary], ['secondary', secondary]]) {
            for (const state of ['normal', 'hover', 'active', 'focus']) {
                await page.mouse.move(0, 0);
                if (state === 'hover' || state === 'active') await control.hover();
                if (state === 'active') await page.mouse.down();
                if (state === 'focus') { await control.focus(); await page.keyboard.press('ArrowRight'); }
                await control.evaluate(element => element.getAnimations().forEach(animation => animation.finish()));
                const colors = await control.evaluate(element => { const s = getComputedStyle(element); return { color: s.color, background: s.backgroundColor, outline: s.outlineColor, outlineWidth: s.outlineWidth, focus: element.matches(':focus-visible') }; });
                const contrast = ratio(colors.color, colors.background);
                results.colors.push({ text: `${name} ${state}`, ...colors, ratio: contrast, minimum: 4.5 });
                assert.ok(contrast >= 4.5);
                if (state === 'focus') {
                    assert.ok(colors.focus && parseFloat(colors.outlineWidth) >= 2);
                    assert.ok(ratio(colors.outline, 'rgb(246,251,249)') >= 3);
                }
                if (state === 'active') { await page.mouse.move(0, 0); await page.mouse.up(); }
            }
        }
        await page.emulateMedia({ reducedMotion: 'no-preference' });
        await page.mouse.move(0, 0);
        await primary.evaluate(el => el.blur());
        await primary.hover();
        const offset = () => primary.evaluate(el => {
            el.getAnimations().forEach(animation => animation.finish());
            return new DOMMatrixReadOnly(getComputedStyle(el).transform).m42;
        });
        assert.equal(await offset(), -1);
        await page.mouse.down();
        assert.equal(await offset(), 2);
        await page.mouse.move(0, 0); await page.mouse.up();
        await page.emulateMedia({ reducedMotion: 'reduce' });
        assert.equal(await primary.evaluate(el => getComputedStyle(el).transform), 'none');
        await clickLink('Mulai Belajar', '/materi');
        await clickLink('Latihan Coding', '/coding');
        await clickLink('Masuk ke akun', '/login');
        for (const [name, path] of [['OOPy', '/'], ['Beranda', '/'], ['Materi', '/materi'], ['Coding', '/coding'], ['Masuk', '/login'], ['Daftar', '/register']]) {
            await clickLink(name, path);
        }
        await page.setViewportSize({ width: 390, height: 1000 });
        const menu = page.getByRole('button', { name: 'Buka menu navigasi' });
        await menu.focus(); await page.keyboard.press('Enter');
        await page.locator('#navbarOOPy.show').waitFor();
        await page.getByRole('link', { name: 'Materi', exact: true }).click();
        await page.waitForURL(url => url.pathname === '/materi');
        results.navigation.push('guest mobile menu -> /materi');
        await registerAccount(page, base);
        await visit();
        await layout('authenticated');
        await auditText();
        assert.equal(await page.locator('#home-account-title').textContent(), 'Lihat progres belajarmu.');
        await reflow('authenticated');
        assert.equal(await page.getByRole('link', { name: 'Masuk ke akun', exact: true }).count(), 0);
        await clickLink('Mulai Belajar', '/materi');
        await clickLink('Latihan Coding', '/coding');
        await clickLink('Buka Dashboard', '/dashboard');
        await page.setViewportSize({ width: 390, height: 1000 });
        await menu.focus(); await page.keyboard.press('Enter');
        await page.locator('#navbarOOPy.show').waitFor();
        await page.getByRole('link', { name: 'Dashboard', exact: true }).click();
        await page.waitForURL(url => url.pathname === '/dashboard');
        results.navigation.push('authenticated mobile menu -> /dashboard');
        await visit();
        assert.equal(await primary.evaluate(element => getComputedStyle(element).transitionDuration), '0s');
        await page.setViewportSize({ width: 1440, height: 1000 });
        await clickLink('Dashboard', '/dashboard');
        const account = page.locator('#oopyAccountMenu');
        await account.focus(); await page.keyboard.press('Enter');
        await page.locator('.oopy-account-menu.show').waitFor();
        await page.keyboard.press('Escape');
        assert.equal(await account.getAttribute('aria-expanded'), 'false');
        await page.keyboard.press('Enter');
        await page.getByRole('button', { name: 'Logout', exact: true }).click();
        await page.getByRole('dialog', { name: 'Yakin ingin keluar dari OOPy?' }).waitFor();
        await page.getByRole('button', { name: 'Ya, Logout', exact: true }).click();
        await page.waitForURL(url => url.pathname === '/');
        await page.getByRole('link', { name: 'Masuk', exact: true }).waitFor();
        results.navigation.push('account menu -> opens, Escape closes, Logout -> guest home');
        assert.deepEqual(results.errors, []);
        results.status = 'PASS';
        console.log('PASS home: real CTA destinations for guests/accounts, contrast, keyboard, logo and reduced motion');
    }
} catch (error) {
    results.status = 'FAIL'; results.failure = error.stack; throw error;
} finally {
    if (directory) await writeFile(join(directory, `${baseline ? 'before' : 'after'}-results.json`), JSON.stringify(results, null, 2));
    await browser.close();
}
