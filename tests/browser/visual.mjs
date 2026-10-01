import { chromium } from 'playwright';
import assert from 'node:assert/strict';

const base = process.env.OOPY_BASE_URL || 'http://127.0.0.1:8017';
const browser = await chromium.launch({ channel: process.env.OOPY_BROWSER || 'msedge', headless: true });
const page = await browser.newPage({ viewport: { width: 1440, height: 1000 } });
const errors = [];
page.on('pageerror', (error) => errors.push(error.message));
page.on('dialog', (dialog) => dialog.accept());
const chapters = ['/materi/dasar-pemrograman-oop', '/materi/kelas-dan-objek'];
const samples = new Map();
const luminance = (color) => {
    const [r, g, b] = color.match(/[\d.]+/g).slice(0, 3).map(Number).map((value) => {
        const channel = value / 255;
        return channel <= .04045 ? channel / 12.92 : ((channel + .055) / 1.055) ** 2.4;
    });
    return .2126 * r + .7152 * g + .0722 * b;
};
const contrast = (a, b) => (Math.max(luminance(a), luminance(b)) + .05) / (Math.min(luminance(a), luminance(b)) + .05);

try {
    for (const path of ['/', '/materi', ...chapters, '/editor']) {
        assert.equal((await page.goto(`${base}${path}`, { waitUntil: 'networkidle' })).status(), 200);
        if (chapters.includes(path)) {
            const codes = page.locator('.material-section > .material-code code');
            assert.ok(await codes.count());
            samples.set(path, await codes.allTextContents());
            assert.equal(await codes.evaluateAll((nodes) => nodes.every((code) => code.querySelector('.token'))), true);
            if (await page.locator('[data-quiz="code-card"]').isVisible()) {
                assert.ok(await page.locator('[data-quiz="code"] .token').count());
            }
        } else {
            assert.equal(await page.locator('script[src*="vendor/prism"]').count(), 0);
        }
        for (const width of [390, 768, 1024, 1440]) {
            await page.setViewportSize({ width, height: 1000 });
            await page.evaluate(() => new Promise((resolve) => requestAnimationFrame(() => requestAnimationFrame(resolve))));
            assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1), `${path}: overflow at ${width}px`);
        }
    }
    console.log('PASS: five pages, highlighted BAB 1/BAB 2 examples, no overflow at 390/768/1024/1440px');

    await page.goto(`${base}${chapters[1]}`, { waitUntil: 'networkidle' });
    const source = '# Catatan habitat\nclass Ekosistem:\n    def info(self, nama):\n        return f"{nama} memiliki {120} hektar"\n\nprint(len(range(85)))\nhtml = "<img src=x onerror=alert(1)> & rawa"';
    const tokens = await page.evaluate((source) => {
        const code = document.querySelector('.material-section > .material-code code');
        code.textContent = source;
        window.OopySyntax.highlight(code);
        return {
            text: code.textContent,
            htmlNodes: code.querySelectorAll('img, script').length,
            background: getComputedStyle(code.closest('.material-code')).backgroundColor,
            styles: ['keyword', 'class-name', 'function', 'builtin', 'string', 'number', 'comment'].map((token) => {
                const node = code.querySelector(`.token.${token}`);
                return { token, text: node?.textContent, color: node && getComputedStyle(node).color };
            }),
            printIsBuiltin: [...code.querySelectorAll('.token.builtin')].some((node) => node.textContent === 'print'),
        };
    }, source);
    assert.equal(tokens.text, source);
    assert.equal(tokens.htmlNodes, 0);
    assert.equal(tokens.printIsBuiltin, true);
    assert.ok(tokens.styles.every((token) => token.text && token.color));
    assert.equal(new Set(tokens.styles.map((token) => token.color)).size, 7);
    assert.ok(tokens.styles.every((token) => contrast(token.color, tokens.background) >= 4.5));
    const buttonColors = await page.locator('.btn-brand').first().evaluate((button) => {
        const style = getComputedStyle(button);
        return [style.color, style.backgroundColor];
    });
    assert.ok(contrast(...buttonColors) >= 4.5);
    assert.equal(await page.evaluate((source) => {
        const code = document.querySelector('.material-code code');
        const original = window.Prism.highlightElement;
        window.Prism.highlightElement = () => { throw new Error('Simulated failure'); };
        code.textContent = source;
        window.OopySyntax.highlight(code);
        window.Prism.highlightElement = original;
        return code.textContent;
    }, source), source);
    console.log('PASS: seven Python token colors, Python 3 built-ins, preserved source, escaped HTML and failure fallback');

    for (const path of chapters) {
        await page.goto(`${base}${path}`, { waitUntil: 'networkidle' });
        const quizQuestions = await page.locator('[data-quiz="questions"]').evaluate((node) => JSON.parse(node.textContent));
        for (const question of quizQuestions) {
            if (question.code) {
                assert.equal(await page.locator('[data-quiz="code"]').textContent(), question.code);
                assert.ok(await page.locator('[data-quiz="code"] .token').count());
            }
            await page.locator('[data-quiz="options"] input').nth(question.correct).check();
            await page.locator('[data-quiz="next"]').click();
        }
        assert.equal(await page.locator('[data-quiz="percentage"]').textContent(), '100%');
        assert.deepEqual(await page.locator('[data-quiz="review"] code').allTextContents(), quizQuestions.filter((question) => question.code).map((question) => question.code));
        assert.equal(await page.locator('[data-quiz="review"] code').evaluateAll((nodes) => nodes.every((code) => code.querySelector('.token'))), true);

        const noJs = await browser.newPage({ javaScriptEnabled: false, viewport: { width: 390, height: 900 } });
        await noJs.goto(`${base}${path}`, { waitUntil: 'networkidle' });
        assert.deepEqual(await noJs.locator('.material-section > .material-code code').allTextContents(), samples.get(path));
        assert.equal(await noJs.locator('.material-code .token').count(), 0);
        assert.ok(await noJs.locator('.material-code pre').first().isVisible());
        assert.ok(await noJs.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1));
        await noJs.close();

        const fallback = await browser.newPage();
        const fallbackErrors = [];
        fallback.on('pageerror', (error) => fallbackErrors.push(error.message));
        await fallback.route('**/js/vendor/prism/**', (route) => route.abort());
        await fallback.goto(`${base}${path}`, { waitUntil: 'networkidle' });
        assert.deepEqual(await fallback.locator('.material-section > .material-code code').allTextContents(), samples.get(path));
        assert.equal(await fallback.locator('.material-code .token').count(), 0);
        const questions = await fallback.locator('[data-quiz="questions"]').evaluate((node) => JSON.parse(node.textContent));
        for (const question of questions) {
            await fallback.locator('[data-quiz="options"] input').nth(question.correct).check();
            await fallback.locator('[data-quiz="next"]').click();
        }
        assert.equal(await fallback.locator('[data-quiz="percentage"]').textContent(), '100%');
        assert.deepEqual(fallbackErrors, []);
        await fallback.close();
    }
    assert.deepEqual(errors, []);
    console.log('PASS: no-JS reading, blocked Prism fallback, working quizzes in both chapters, no page errors');

    if (process.env.OOPY_SCREENSHOT_DIR) {
        await page.goto(`${base}${chapters[1]}`, { waitUntil: 'networkidle' });
        for (const width of [390, 1440]) {
            await page.setViewportSize({ width, height: 1000 });
            await page.locator('.material-section > .material-code').first().scrollIntoViewIfNeeded();
            await page.screenshot({ path: `${process.env.OOPY_SCREENSHOT_DIR}/syntax-${width}.png` });
        }
    }
} finally {
    await browser.close();
}
