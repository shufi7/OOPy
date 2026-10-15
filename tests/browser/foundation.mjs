// Run against a disposable seeded database: this test creates an account and quiz result.
import { chromium } from 'playwright';
import assert from 'node:assert/strict';
import { mkdir, readFile, writeFile } from 'node:fs/promises';
import { join } from 'node:path';
import { chapters } from './chapters.mjs';
import { registerAccount, answerQuiz } from './quiz-helpers.mjs';

const base = process.env.OOPY_BASE_URL || 'http://127.0.0.1:8017';
const directory = process.env.OOPY_SCREENSHOT_DIR;
const widths = [360, 390, 768, 1024, 1440];
const interactionsOnly = process.argv.includes('--interactions-only');
const results = interactionsOnly && directory
    ? JSON.parse(await readFile(join(directory, 'foundation-results.json'), 'utf8'))
    : { widths, layouts: [], colors: [], interactions: [], errors: [] };
if (interactionsOnly) {
    results.colors = []; results.interactions = []; results.errors = [];
    results.layouts = results.layouts.filter(layout => layout.state === 'initial');
    delete results.failure;
}
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
page.on('dialog', dialog => dialog.type() === 'beforeunload' ? dialog.accept() : dialog.dismiss());
const settle = () => page.evaluate(() => new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(resolve))));
const visit = async path => {
    assert.equal((await page.goto(`${base}${path}`, { waitUntil: 'load' })).status(), 200, path);
    await page.evaluate(() => document.fonts.ready);
    if (await page.locator('[data-role="editor-loading"]').count()) {
        await page.locator('[data-role="editor-loading"]').first().waitFor({ state: 'hidden', timeout: 30000 });
    }
    await settle();
};
const parseColor = value => {
    const channels = value.match(/[\d.]+/g).map(Number);
    return value.startsWith('color(srgb') ? channels.map((number, index) => index < 3 ? number * 255 : number) : channels;
};
const luminance = value => {
    const [r, g, b] = parseColor(value).slice(0, 3).map(channel => {
        const c = channel / 255;
        return c <= .04045 ? c / 12.92 : ((c + .055) / 1.055) ** 2.4;
    });
    return .2126 * r + .7152 * g + .0722 * b;
};
const contrast = (a, b) => (Math.max(luminance(a), luminance(b)) + .05) / (Math.min(luminance(a), luminance(b)) + .05);
const checkColor = async (locator, name, minimum = 4.5, foreground = 'color') => {
    let pair;
    for (let attempt = 0; attempt < 3; attempt++) {
        pair = await locator.evaluate((element, foreground) => {
            const style = getComputedStyle(element);
            let background = style.backgroundColor;
            for (let node = element.parentElement; background === 'rgba(0, 0, 0, 0)' && node; node = node.parentElement) {
                background = getComputedStyle(node).backgroundColor;
            }
            return { foreground: style[foreground], background, opacity: style.opacity, connected: element.isConnected };
        }, foreground);
        if (pair.connected && pair.opacity !== '') break;
        await settle();
    }
    assert.equal(pair.opacity, '1', `${name}: opacity hides the actual contrast`);
    const ratio = contrast(pair.foreground, pair.background);
    results.colors.push({ name, ...pair, ratio, minimum });
    assert.ok(ratio >= minimum, `${name}: ${ratio.toFixed(3)} < ${minimum}`);
};
const screenshot = async name => {
    if (directory) await page.screenshot({ path: join(directory, `${name}.png`), animations: 'disabled' });
};
const layout = async (path, state = 'initial') => {
    for (const width of widths) {
        await page.setViewportSize({ width, height: 1000 });
        await settle();
        const snapshot = await page.evaluate(() => {
            const visible = element => element.checkVisibility() && element.getBoundingClientRect().width > 0;
            const controls = [...document.querySelectorAll('.btn-brand,.navbar-toggler,.oopy-button,.oopy-file-tab,.oopy-file-button,.oopy-icon-button,.oopy-terminal-retry,[data-password-toggle],.exam-button,.exam-number,.exam-number-panel>summary,.material-toc a')]
                .filter(visible).map(element => {
                    const rect = element.getBoundingClientRect();
                    return { label: element.getAttribute('aria-label') || element.textContent.trim(), width: rect.width, height: rect.height };
                });
            const editors = [...document.querySelectorAll('.oopy-editor-panel')].map(panel => {
                const outer = panel.getBoundingClientRect();
                const editor = panel.querySelector('.monaco-editor').getBoundingClientRect();
                const statusbar = panel.nextElementSibling.getBoundingClientRect();
                return { panel: outer.height, editor: editor.height, editorWidth: editor.width, panelWidth: outer.width, overlap: editor.bottom > statusbar.top + 1 };
            });
            return { width: innerWidth, scrollWidth: document.documentElement.scrollWidth, controls, editors };
        });
        results.layouts.push({ path, state, ...snapshot });
        assert.ok(snapshot.scrollWidth <= width + 1, `${path}: overflow at ${width}`);
        for (const control of snapshot.controls) assert.ok(control.height >= 43.9 && control.width >= 43.9, `${path} ${width}: ${JSON.stringify(control)}`);
        for (const editor of snapshot.editors) {
            assert.equal(editor.overlap, false, `${path} ${width}: statusbar overlap`);
            assert.ok(Math.abs(editor.panel - editor.editor) <= 1 && Math.abs(editor.panelWidth - editor.editorWidth) <= 1, `${path} ${width}: Monaco did not relayout`);
        }
        if ([390, 1440].includes(width)) await screenshot(`${path === '/' ? 'home' : path.slice(1).replaceAll('/', '-')}-${state}-${width}`);
    }
    console.log(`PASS layout ${path} (${state}): five viewports, touch targets, Monaco geometry`);
};
const checkStates = async (locator, name, disabled = true) => {
    await page.mouse.move(0, 0);
    await locator.evaluate(element => element.getAnimations().forEach(animation => animation.finish()));
    await checkColor(locator, `${name} normal`);
    await locator.hover();
    await locator.evaluate(element => element.getAnimations().forEach(animation => animation.finish()));
    await checkColor(locator, `${name} hover`);
    await page.mouse.down();
    await locator.evaluate(element => element.getAnimations().forEach(animation => animation.finish()));
    await checkColor(locator, `${name} active`);
    await page.mouse.move(0, 0);
    await page.mouse.up();
    await locator.focus();
    await page.keyboard.press('ArrowRight');
    assert.equal(await locator.evaluate(element => element.matches(':focus-visible')), true, `${name}: no keyboard focus`);
    await locator.evaluate(element => element.getAnimations().forEach(animation => animation.finish()));
    await checkColor(locator, `${name} focus`);
    const outline = await locator.evaluate(element => getComputedStyle(element).outlineWidth);
    assert.ok(parseFloat(outline) >= 2, `${name}: no visible outline`);
    const focus = await locator.evaluate(element => {
        let node = element.parentElement;
        while (node && getComputedStyle(node).backgroundColor === 'rgba(0, 0, 0, 0)') node = node.parentElement;
        return { foreground: getComputedStyle(element).outlineColor, background: node ? getComputedStyle(node).backgroundColor : 'rgb(255, 255, 255)' };
    });
    const focusRatio = contrast(focus.foreground, focus.background);
    results.colors.push({ name: `${name} focus indicator`, ...focus, ratio: focusRatio, minimum: 3 });
    assert.ok(focusRatio >= 3, `${name}: weak focus ring`);
    if (disabled) {
        await locator.evaluate(element => element.tagName === 'A' ? element.classList.add('disabled') : element.disabled = true);
        await locator.evaluate(element => element.getAnimations().forEach(animation => animation.finish()));
        await checkColor(locator, `${name} disabled`);
        await locator.evaluate(element => element.tagName === 'A' ? element.classList.remove('disabled') : element.disabled = false);
    }
};

try {
    for (const path of interactionsOnly ? [] : ['/', '/materi', ...chapters.map(chapter => `/materi/${chapter.slug}`), '/coding', '/coding/latihan-1', '/coding/latihan-2', '/editor', '/login', '/register', '/materi/evaluasi-akhir', '/materi/evaluasi-akhir/ujian', '/materi/evaluasi-akhir/hasil']) {
        await visit(path);
        await layout(path);
    }
    await visit('/coding');
    await checkStates(page.locator('.coding-card .btn-brand').first(), 'Coding CTA');
    await checkColor(page.locator('.nav-link.active'), 'active navbar');
    await checkColor(page.locator('.coding-notice a'), 'guest notice link');
    await visit('/login');
    await checkColor(page.locator('#email'), 'login input boundary', 3, 'borderTopColor');
    await page.locator('[data-password-toggle]').focus();
    await page.keyboard.press('Enter');
    assert.equal(await page.locator('#password').getAttribute('type'), 'text');
    results.interactions.push('Password toggle via Enter');
    await visit('/register');
    await checkColor(page.locator('#password_confirmation'), 'registration input boundary', 3, 'borderTopColor');
    await visit('/materi/kelas-abstrak');
    await checkColor(page.locator('.material-tip code').filter({ hasText: '@abstractmethod baca_data()' }).first(), 'BAB 6 inline code');

    await visit('/coding/latihan-1');
    await page.waitForFunction(() => !document.querySelector('[data-role="run-code"]').disabled, null, { timeout: 30000 });
    const root = page.locator('[data-live-code]');
    const panel = root.locator('.oopy-editor-panel');
    await panel.evaluate(element => { element.style.width = '85%'; element.style.height = '487px'; });
    await page.waitForFunction(() => {
        const panel = document.querySelector('.oopy-editor-panel'), editor = panel.querySelector('.monaco-editor');
        return Math.abs(editor.clientWidth - panel.clientWidth) <= 1 && Math.abs(editor.clientHeight - panel.clientHeight) <= 1;
    });
    await panel.evaluate(element => { element.style.removeProperty('width'); element.style.removeProperty('height'); });
    results.interactions.push('Monaco automaticLayout follows container width and height changes');
    await checkColor(root.locator('.oopy-file-button.is-active'), 'active file explorer');
    for (const [role, label] of [['run-code', 'Run'], ['reset-code', 'Reset'], ['check-code', 'Submit']]) {
        await checkStates(root.locator(`[data-role="${role}"]`), label);
    }
    await page.evaluate(() => window.monaco.editor.getModels()[0].setValue('while True:\n    pass'));
    await root.locator('[data-role="run-code"]').click();
    await root.locator('[data-role="stop-code"]').waitFor({ state: 'visible' });
    await checkStates(root.locator('[data-role="stop-code"]'), 'Stop');
    await root.locator('[data-role="stop-code"]').click();
    await page.waitForFunction(() => !document.querySelector('[data-role="run-code"]').disabled, null, { timeout: 30000 });
    results.interactions.push('Stop interrupts running Python and runtime becomes ready again');
    await page.evaluate(() => window.monaco.editor.getModels()[0].setValue('# Catatan\nprint("Fondasi OOPy")'));
    await page.waitForFunction(() => document.querySelector('.view-lines').textContent.includes('Catatan'));
    const comment = page.locator('.view-lines span').filter({ hasText: /^#\s*Catatan$/ }).last();
    await checkColor(comment, 'Monaco comment');
    await checkColor(page.locator('.line-numbers').last(), 'Monaco inactive line numbers');
    for (const width of [390, 1440]) {
        await page.setViewportSize({ width, height: 1000 });
        await root.scrollIntoViewIfNeeded();
        await screenshot(`editor-after-${width}`);
    }

    await visit('/editor');
    const tabs = page.locator('#demo-objek [role="tab"]');
    await tabs.first().focus();
    await page.keyboard.press('ArrowRight');
    assert.equal(await tabs.nth(1).getAttribute('aria-selected'), 'true');
    assert.equal(await tabs.nth(1).evaluate(element => element === document.activeElement), true);
    results.interactions.push('Multi-file tab selection/focus via ArrowRight');

    await registerAccount(page, base);
    await visit('/materi/dasar-pemrograman-oop');
    await answerQuiz(page, 'dasar-pemrograman-oop');
    assert.equal(await page.locator('[data-quiz="status"]').textContent(), 'Lulus');
    await visit('/dashboard');
    await layout('/dashboard', 'one-chapter-complete');
    assert.equal(await page.getByRole('progressbar').getAttribute('aria-valuenow'), '17');
    await checkColor(page.locator('.site-footer .container'), 'dashboard footer');
    results.interactions.push('Real quiz submission preserves 100/Lulus and dashboard 17%');

    await visit('/materi/evaluasi-akhir');
    await page.locator('[data-exam="start"]').click();
    await page.locator('[data-exam="start-confirm"]').click();
    await page.waitForURL(url => url.pathname.endsWith('/ujian'));
    await layout('/materi/evaluasi-akhir/ujian', 'active');
    const questionIndex = await page.locator('[data-exam="config"]').evaluate(element => JSON.parse(element.textContent).questions.findIndex(question => question.type === 'essay'));
    await page.locator('[data-exam="numbers"] button').nth(questionIndex).click();
    await checkColor(page.locator('[data-exam="essay-answer"]'), 'evaluation input boundary', 3, 'borderTopColor');
    await page.locator('[data-exam="essay-answer"]').fill('Jawaban sementara untuk menguji antarmuka.');
    await page.locator('[data-exam="finish"]').click();
    await page.keyboard.press('Escape');
    assert.equal(await page.locator('[data-exam="finish-dialog"]').evaluate(element => element.open), false);
    await page.locator('[data-exam="finish"]').click();
    await page.locator('[data-exam="finish-confirm"]').click();
    await page.waitForURL(url => url.pathname.endsWith('/hasil'));
    await layout('/materi/evaluasi-akhir/hasil', 'completed');
    results.interactions.push('Evaluation start, essay, Escape dialog, submit and results');

    await visit('/');
    await page.setViewportSize({ width: 390, height: 1000 });
    const menu = page.getByRole('button', { name: 'Buka menu navigasi' });
    await menu.focus();
    await page.keyboard.press('Enter');
    await page.locator('#navbarOOPy.show').waitFor();
    await page.keyboard.press('Enter');
    await page.waitForFunction(() => document.querySelector('.navbar-toggler').getAttribute('aria-expanded') === 'false');
    results.interactions.push('Mobile navbar opens/closes with Enter');
    assert.deepEqual(results.errors, []);
    results.status = 'PASS';
    console.log('PASS foundation: contrast across control states, keyboard, container relayout, quiz, dashboard and evaluation');
} catch (error) {
    results.status = 'FAIL';
    results.failure = error.stack;
    throw error;
} finally {
    if (directory) await writeFile(join(directory, 'foundation-results.json'), JSON.stringify(results, null, 2));
    await browser.close();
}
