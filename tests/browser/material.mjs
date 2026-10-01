import { chromium } from 'playwright';
import assert from 'node:assert/strict';

const base = process.env.OOPY_BASE_URL || 'http://127.0.0.1:8017';
const browser = await chromium.launch({ channel: process.env.OOPY_BROWSER || 'msedge', headless: true });
const page = await browser.newPage({ viewport: { width: 1440, height: 1000 } });
const errors = [];
page.on('pageerror', (error) => errors.push(error.message));
page.on('dialog', (dialog) => dialog.accept());
const chapterPath = '/materi/dasar-pemrograman-oop';
const waitReady = () => page.waitForFunction(() => !document.querySelector('[data-role="run-code"]').disabled, null, { timeout: 110000 });

try {
    await page.goto(base, { waitUntil: 'domcontentloaded' });
    await page.getByRole('link', { name: /Mulai Belajar/i }).click();
    await page.waitForURL(`${base}/materi`);
    assert.equal(await page.locator('.materi-card').count(), 6);
    assert.equal(await page.locator('.materi-card a').count(), 2);
    await page.getByRole('link', { name: /Pelajari BAB 1/ }).click();
    await page.waitForURL(`${base}${chapterPath}`);
    await waitReady();
    assert.equal(await page.locator('[data-material-section]').count(), 12);
    assert.equal(await page.locator('[data-live-code]').count(), 1);
    assert.equal(await page.locator('.material-toc nav a').count(), 12);

    for (const width of [390, 768, 1024, 1440]) {
        await page.setViewportSize({ width, height: 1000 });
        await page.waitForFunction((width) => document.querySelector('.material-toc').open === (width >= 992), width);
        assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1), `Page overflow at ${width}`);
        if (width < 992) await page.locator('.material-toc summary').click();
        await page.locator('.material-toc a[href="#fungsi"]').click();
        await page.waitForFunction(() => document.querySelector('.material-toc a[href="#fungsi"]').getAttribute('aria-current') === 'location');
        assert.equal(await page.evaluate(() => document.activeElement.id), 'fungsi');
        assert.equal(await page.locator('.material-toc').evaluate((el) => el.open), width >= 992);
        assert.ok(await page.locator('#fungsi').evaluate((el) => el.getBoundingClientRect().top >= 0), `Anchor hidden at ${width}px`);
        if (width >= 992) {
            const overlap = await page.evaluate(() => {
                const sidebar = document.querySelector('.material-sidebar').getBoundingClientRect();
                const article = document.querySelector('.material-article').getBoundingClientRect();
                return sidebar.right > article.left;
            });
            assert.equal(overlap, false);
        }
    }
    console.log('PASS: home -> list -> chapter; 12 sections; responsive TOC, focus and anchors at 390/768/1024/1440px');

    // A keyboard user can reach the horizontally scrollable code example.
    await page.locator('#fungsi .material-code pre').focus();
    assert.equal(await page.locator('#fungsi .material-code pre').evaluate((el) => el === document.activeElement), true);
    const role = (name) => page.locator(`#bab1-variabel [data-role="${name}"]`);
    await role('run-code').click();
    await waitReady();
    assert.match(await role('code-output').textContent(), /Sungai Barito/);
    await role('check-code').click();
    await waitReady();
    assert.equal(await role('practice-percentage').textContent(), '0%');
    await page.evaluate(() => {
        const model = window.monaco.editor.getModel(window.monaco.Uri.parse('file:///workspaces/bab1-variabel/main.py'));
        model.setValue('nama_ekosistem = "Rawa Bangkau"\nprint(nama_ekosistem)');
    });
    await role('check-code').click();
    await waitReady();
    assert.equal(await role('practice-percentage').textContent(), '100%');
    assert.equal(await page.locator('.material-progress progress').getAttribute('value'), '0');
    assert.equal(await page.locator('#kuis [data-quiz="form"]').isVisible(), true);
    await role('reset-code').click();
    console.log('PASS: existing Live Coding Run/Submit/Reset; exercise score independent from chapter display; interactive quiz rendered');

    // Direct anchors and no-JS reading work independently of Monaco/Pyodide.
    const mobile = await browser.newPage({ viewport: { width: 390, height: 900 } });
    await mobile.goto(`${base}${chapterPath}#tipe-data`, { waitUntil: 'domcontentloaded' });
    await mobile.waitForFunction(() => !document.querySelector('.material-toc').open);
    await mobile.waitForFunction(() => Math.abs(document.querySelector('#tipe-data').getBoundingClientRect().top - 24) < 5);
    await mobile.close();
    const noJs = await browser.newPage({ javaScriptEnabled: false, viewport: { width: 390, height: 900 } });
    await noJs.goto(`${base}${chapterPath}`, { waitUntil: 'domcontentloaded' });
    assert.equal(await noJs.locator('#kuis').count(), 1);
    await noJs.locator('.material-toc summary').click();
    assert.equal(await noJs.locator('.material-toc').evaluate((el) => el.open), false);
    await noJs.locator('.material-toc summary').click();
    await noJs.locator('.material-toc a[href="#kuis"]').click();
    assert.match(noJs.url(), /#kuis$/);
    await noJs.close();
    assert.deepEqual(errors, []);
    console.log('PASS: direct mobile anchors, native no-JS navigation; no page errors');

    if (process.env.OOPY_SCREENSHOT_DIR) {
        await page.evaluate(() => scrollTo(0, 0));
        await page.screenshot({ path: `${process.env.OOPY_SCREENSHOT_DIR}/material-desktop.png` });
        await page.setViewportSize({ width: 390, height: 900 });
        await page.evaluate(() => scrollTo(0, 0));
        await page.screenshot({ path: `${process.env.OOPY_SCREENSHOT_DIR}/material-mobile.png` });
    }
} finally {
    await browser.close();
}
