import { chromium } from 'playwright';
import assert from 'node:assert/strict';
import { existsSync } from 'node:fs';
import { mkdir } from 'node:fs/promises';
import { cacheAssets, loginAccount } from './quiz-helpers.mjs';

const base = process.env.OOPY_BASE_URL || 'http://127.0.0.1:8017';
const directory = process.env.OOPY_SCREENSHOT_DIR;
if (directory) await mkdir(directory, { recursive: true });
const browser = await chromium.launch({ channel: process.env.OOPY_BROWSER || 'msedge', headless: true });
const context = await browser.newContext();
await cacheAssets(context);
const page = await context.newPage();
const errors = [];
page.on('pageerror', error => errors.push(error.message));
page.on('console', message => {
    if (message.type() === 'error' && page.url() === `${base}/coding`) errors.push(message.text());
});
const widths = [320, 390, 768, 1024, 1440, 1920];
const cards = page.locator('.coding-index .coding-card');
const images = ['latihan-1-sensorair.svg', 'latihan-2-ekosistem-rawa.svg'];
const capture = async name => {
    if (directory) await page.screenshot({ path: `${directory}/${name}.png`, fullPage: true });
};
const checkContrast = async () => {
    const samples = await page.locator('.coding-eyebrow, .coding-index h1, .coding-index h2, .coding-index p, .coding-skills li, .coding-index .coding-status, .coding-index .btn').evaluateAll(nodes => nodes.map(node => {
        let surface = node;
        while (getComputedStyle(surface).backgroundColor === 'rgba(0, 0, 0, 0)' && surface.parentElement) surface = surface.parentElement;
        return { text: node.textContent.trim(), color: getComputedStyle(node).color, background: getComputedStyle(surface).backgroundColor };
    }));
    const luminance = color => {
        const [r, g, b] = color.match(/[\d.]+/g).slice(0, 3).map(Number).map(value => {
            const c = value / 255;
            return c <= .04045 ? c / 12.92 : ((c + .055) / 1.055) ** 2.4;
        });
        return .2126 * r + .7152 * g + .0722 * b;
    };
    const ratios = samples.map(sample => {
        const values = [luminance(sample.color), luminance(sample.background)].sort((a, b) => b - a);
        const ratio = (values[0] + .05) / (values[1] + .05);
        assert.ok(ratio >= 4.5, `${sample.text}: contrast ${ratio.toFixed(2)}`);
        return ratio;
    });
    return Math.min(...ratios).toFixed(2);
};
const reflow = async label => {
    for (const width of widths) {
        await page.setViewportSize({ width, height: 1000 });
        await page.evaluate(() => document.fonts.ready);
        assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1), `${label}: overflow at ${width}`);
        const boxes = await cards.evaluateAll(nodes => nodes.map(node => {
            const rect = node.getBoundingClientRect();
            const button = node.querySelector('.coding-action').getBoundingClientRect();
            return { top: rect.top, bottom: rect.bottom, left: rect.left, right: rect.right,
                buttonBottom: button.bottom, buttonHeight: button.height, buttonWidth: button.width };
        }));
        assert.ok(boxes.every(box => box.buttonHeight >= 44 && box.buttonWidth >= 44));
        if (width >= 992) {
            assert.equal(boxes[0].top, boxes[1].top);
            assert.equal(boxes[0].bottom, boxes[1].bottom);
            assert.equal(boxes[0].buttonBottom, boxes[1].buttonBottom);
        } else {
            assert.ok(boxes[1].top > boxes[0].bottom);
        }
        if ([390, 1440].includes(width)) await capture(`${label}-${width}`);
    }
};

try {
    assert.equal((await page.goto(`${base}/coding`, { waitUntil: 'networkidle' })).status(), 200);
    assert.equal(await cards.count(), 2);
    assert.equal(await page.locator('.coding-index h1').innerText(), 'Latihan Coding OOPy');
    assert.equal(await page.locator('.coding-index .coding-notice').count(), 1);
    assert.equal(await page.locator('.coding-index .coding-status').count(), 0);
    assert.deepEqual(await page.locator('.coding-skills').first().locator('li').allTextContents(), ['Python', 'Class & Object', 'SensorAir']);
    assert.deepEqual(await page.locator('.coding-skills').last().locator('li').allTextContents(), ['Python', 'Inheritance', 'Multi-File']);
    console.log(`PASS contrast: minimum ${await checkContrast()}:1 for headings, body, labels and normal buttons`);
    for (const [index, file] of images.entries()) {
        const image = cards.nth(index).locator('img');
        const available = existsSync(new URL(`../../public/images/coding/${file}`, import.meta.url));
        assert.equal(await image.count(), available ? 1 : 0);
        if (available) {
            await image.scrollIntoViewIfNeeded();
            await expectImage(image);
            assert.ok((await image.getAttribute('alt')).length > 20);
            assert.ok((await image.getAttribute('src')).endsWith(`/images/coding/${file}`));
        } else {
            console.log(`MISSING ASSET: public/images/coding/${file}; no broken image rendered`);
        }
    }
    await reflow('after-guest');
    await page.setViewportSize({ width: 390, height: 1000 });
    const menu = page.getByRole('button', { name: 'Buka menu navigasi' });
    await menu.click();
    assert.equal(await menu.getAttribute('aria-expanded'), 'true');
    await page.locator('.navbar .collapsing').waitFor({ state: 'hidden' });
    await menu.click();
    assert.equal(await menu.getAttribute('aria-expanded'), 'false');
    await page.locator('.navbar .collapsing').waitFor({ state: 'hidden' });
    const links = page.locator('.coding-index a');
    await page.evaluate(() => document.activeElement.blur());
    await page.keyboard.press('Control+Home');
    const visited = [];
    for (let index = 0; index < 25 && visited.length < 3; index++) {
        await page.keyboard.press('Tab');
        const focused = await page.evaluate(() => {
            const node = document.activeElement;
            if (!node.closest('.coding-index')) return null;
            const style = getComputedStyle(node);
            return { href: new URL(node.href).pathname, outline: style.outlineStyle, width: parseFloat(style.outlineWidth) };
        });
        if (focused) {
            assert.notEqual(focused.outline, 'none');
            assert.ok(focused.width >= 3);
            visited.push(focused.href);
        }
    }
    assert.deepEqual(visited, ['/login', '/coding/latihan-1', '/coding/latihan-2']);
    for (const [index, path] of ['/login', '/coding/latihan-1', '/coding/latihan-2'].entries()) {
        await links.nth(index).focus();
        await Promise.all([page.waitForURL(`${base}${path}`), page.keyboard.press('Enter')]);
        assert.ok((await page.locator('h1').innerText()).length);
        await page.goto(`${base}/coding`, { waitUntil: 'networkidle' });
    }
    await page.emulateMedia({ reducedMotion: 'reduce' });
    assert.equal(await page.locator('.coding-action').first().evaluate(node => getComputedStyle(node).transitionDuration), '0s');
    const action = page.locator('.coding-action').first();
    await action.hover();
    console.log(`PASS hover contrast: minimum ${await checkContrast()}:1`);
    await page.mouse.down();
    console.log(`PASS active contrast: minimum ${await checkContrast()}:1`);
    await page.mouse.move(0, 0);
    await page.mouse.up();
    await page.evaluate(() => document.documentElement.style.fontSize = '200%');
    assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1), '200% text resize');
    await page.evaluate(() => document.documentElement.style.fontSize = '');
    await page.emulateMedia({ reducedMotion: 'no-preference' });
    console.log('PASS guest: two exercises, links activated by Enter, visible focus, mobile navbar, six widths, aligned buttons, reduced motion, 200% text');

    // The existing admin-sandbox.php fixture supplies these accounts in a disposable SQLite DB.
    if (process.argv.includes('--auth')) {
        for (const scenario of [
            { email: 'empty.browser@example.test', statuses: ['Belum Dikerjakan', 'Belum Dikerjakan'], actions: ['Mulai Latihan', 'Mulai Latihan'], label: 'unstarted' },
            { email: 'zero.browser@example.test', statuses: ['Belum Lulus', 'Belum Dikerjakan'], actions: ['Lanjutkan Latihan', 'Mulai Latihan'], label: 'attempted' },
            { email: 'student.browser@example.test', statuses: ['Selesai', 'Belum Lulus'], actions: ['Kerjakan Lagi', 'Lanjutkan Latihan'], label: 'completed' },
        ]) {
            await context.clearCookies();
            await loginAccount(page, base, { email: scenario.email, password: 'password123' });
            await page.goto(`${base}/coding`, { waitUntil: 'networkidle' });
            assert.equal(await page.locator('.coding-index .coding-notice').count(), 0);
            assert.deepEqual((await page.locator('.coding-index .coding-status').allTextContents()).map(text => text.trim()), scenario.statuses);
            assert.deepEqual(await page.locator('.coding-action').allTextContents(), scenario.actions);
            console.log(`PASS ${scenario.label} contrast: minimum ${await checkContrast()}:1`);
            await reflow(`after-${scenario.label}`);
        }
        console.log('PASS authenticated: all three statuses and action labels from isolated backend fixtures, no guest banner, six widths per state');
    }
    assert.deepEqual(errors, []);
} finally {
    await browser.close();
}

async function expectImage(locator) {
    await locator.evaluate(node => node.decode());
    assert.ok(await locator.evaluate(node => node.complete && node.naturalWidth > 0));
    assert.equal(await locator.evaluate(node => getComputedStyle(node).objectFit), 'contain');
}
