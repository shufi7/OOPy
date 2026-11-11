// Run only against a fresh, isolated admin-sandbox.php database.
import { chromium } from 'playwright';
import assert from 'node:assert/strict';
import { mkdir, readFile, writeFile } from 'node:fs/promises';
import { join } from 'node:path';
import { registerAccount, quizFixtures } from './quiz-helpers.mjs';

assert.match(process.env.OOPY_ADMIN_TEST_DATABASE || '', /^admin-coding-[a-f0-9]{32}\.sqlite$/);
const base = process.env.OOPY_BASE_URL || 'http://127.0.0.1:8048';
const directory = process.env.OOPY_SCREENSHOT_DIR;
if (directory) await mkdir(directory, { recursive: true });
const results = { interactions: [], layouts: [], contrast: [], errors: [] };
const browser = await chromium.launch({ channel: process.env.OOPY_BROWSER || 'msedge', headless: true });
const assets = process.env.OOPY_ASSET_MANIFEST ? JSON.parse(await readFile(process.env.OOPY_ASSET_MANIFEST, 'utf8')) : {};
const makeContext = async () => {
    const context = await browser.newContext({ viewport: { width: 1440, height: 1000 }, reducedMotion: 'reduce', acceptDownloads: true });
    await context.route('**/*', route => {
        const url = route.request().url();
        if (!assets[url]) return route.continue();
        const path = new URL(url).pathname;
        const contentType = url.includes('fonts.googleapis.com') || path.endsWith('.css') ? 'text/css'
            : path.endsWith('.js') ? 'application/javascript' : path.endsWith('.woff2') ? 'font/woff2'
                : path.endsWith('.ttf') ? 'font/ttf' : 'application/octet-stream';
        return route.fulfill({ path: assets[url], contentType, headers: { 'access-control-allow-origin': '*' } });
    });
    context.on('page', page => page.on('pageerror', error => results.errors.push(error.message)));
    return context;
};
const adminContext = await makeContext();
const studentContext = await makeContext();
const otherContext = await makeContext();
const admin = await adminContext.newPage();
const student = await studentContext.newPage();
const other = await otherContext.newPage();
const visit = async (page, path) => {
    const response = await page.goto(base + path, { waitUntil: 'load' });
    assert.equal(response.status(), 200, path);
    await page.evaluate(() => document.fonts.ready);
};
const save = async (page, button) => {
    await Promise.all([page.waitForNavigation({ waitUntil: 'load' }), button.click()]);
};
const record = message => { results.interactions.push(message); console.log(`PASS ${message}`); };
const luminance = color => {
    const channels = color.match(/[\d.]+/g).slice(0, 3).map(Number).map(value => {
        const channel = value / 255;
        return channel <= .04045 ? channel / 12.92 : ((channel + .055) / 1.055) ** 2.4;
    });
    return .2126 * channels[0] + .7152 * channels[1] + .0722 * channels[2];
};
const layout = async (page, name) => {
    await page.bringToFront();
    for (const width of [360, 390, 768, 1024, 1440]) {
        await page.setViewportSize({ width, height: 1000 });
        await page.evaluate(() => new Promise(resolve => requestAnimationFrame(resolve)));
        assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1), `${name}: overflow at ${width}`);
        if (width === 390 && name.startsWith('bank-')) {
            const region = page.getByRole('region', { name: 'Daftar bank soal', exact: true });
            assert.ok(await region.evaluate(el => el.scrollWidth >= 850 && el.scrollWidth > el.clientWidth));
            await region.scrollIntoViewIfNeeded();
            await region.focus();
            for (let index = 0; index < 5; index++) await page.keyboard.press('ArrowRight');
            await page.waitForTimeout(200);
            assert.ok(await region.evaluate(el => el.scrollLeft > 0), `${name}: table keyboard scroll`);
            await region.evaluate(el => { el.scrollLeft = 0; });
        }
        results.layouts.push({ name, width });
        if (directory && [390, 1440].includes(width)) {
            const path = join(directory, `${name}-${width}.png`);
            if (name.startsWith('quiz-')) await page.locator('#kuis').screenshot({ path });
            else await page.screenshot({ path, fullPage: true });
        }
    }
    for (const element of await page.locator('.admin-metrics dt, .admin-metrics dd, [data-publication="notice"], [data-quiz="score"]').all()) {
        const pair = await element.evaluate(el => {
            const style = getComputedStyle(el);
            let background = style.backgroundColor;
            for (let node = el.parentElement; background === 'rgba(0, 0, 0, 0)' && node; node = node.parentElement) background = getComputedStyle(node).backgroundColor;
            return { color: style.color, background, size: parseFloat(style.fontSize), weight: parseInt(style.fontWeight) };
        });
        const ratio = (Math.max(luminance(pair.color), luminance(pair.background)) + .05) / (Math.min(luminance(pair.color), luminance(pair.background)) + .05);
        assert.ok(ratio >= (pair.size >= 24 || pair.size >= 18.667 && pair.weight >= 700 ? 3 : 4.5), `${name}: contrast ${ratio}`);
        results.contrast.push({ name, ...pair, ratio });
    }
};
const part = (page, name) => page.locator(`[data-quiz="${name}"]`);
const ready = page => part(page, 'form').waitFor({ state: 'visible' });
const pin = async page => {
    const response = page.waitForResponse(response => response.url().endsWith('/kuis/attempts') && response.request().method() === 'POST');
    await part(page, 'options').locator('input').first().check();
    return (await response).json();
};
const answer = async (page, keys, correct) => {
    for (const [index, key] of keys.entries()) {
        if (key.type === 'code_fill') await part(page, 'code-fill').fill(index < correct ? key.answer : 'WRONG');
        else await part(page, 'options').locator('input').nth(index < correct ? key.correct : (key.correct + 1) % key.options.length).check();
        await part(page, 'next').click();
    }
    await part(page, 'results').waitFor({ state: 'visible' });
};
const checkResult = async (page, total, correct, score) => {
    assert.equal(await part(page, 'score').innerText(), score);
    assert.equal(await part(page, 'correct').innerText(), String(correct));
    assert.equal(await part(page, 'incorrect').innerText(), String(total - correct));
    assert.equal(await part(page, 'status').innerText(), 'Lulus');
    assert.equal(await page.locator('[data-quiz-next-link]').isVisible(), true);
};
const selectFirst = async (page, count) => {
    const boxes = page.locator('input[name="question_ids[]"]');
    for (let index = 0; index < await boxes.count(); index++) await boxes.nth(index).setChecked(index < count);
};

try {
    const account = await registerAccount(student, base);
    await visit(student, '/materi/dasar-pemrograman-oop');
    await ready(student);
    const old = await pin(student);
    assert.equal(old.scoring.total_questions, 5);

    await visit(admin, '/login');
    await admin.locator('#email').fill('admin.browser@example.test');
    await admin.locator('#password').fill('password123');
    await save(admin, admin.locator('button[type="submit"]'));
    const added = [];
    for (let number = 6; number <= 10; number++) {
        await visit(admin, '/admin/questions/create?quiz_id=1');
        const code = number > 8;
        await admin.locator('#question_type').selectOption(code ? 'code_fill' : 'multiple_choice');
        await admin.locator('#question_text').fill(`Soal browser dinamis ${number}`);
        if (code) await admin.locator('#code_snippet').fill('def fungsi():\n    ___ 1');
        else await admin.locator('#options_text').fill('Benar\nSalah');
        await admin.locator('#correct_answer').fill(code ? 'return' : '0');
        await admin.locator('#display_order').fill(String(number));
        await save(admin, admin.getByRole('button', { name: 'Simpan draft', exact: true }));
        assert.match(await admin.locator('main').innerText(), /Draft/);
        added.push(code ? { type: 'code_fill', answer: 'return' } : { type: 'multiple_choice', correct: 0, options: ['Benar', 'Salah'] });
    }
    await visit(admin, '/admin/questions?quiz_id=1');
    assert.equal(await admin.locator('[data-publication-active="count"]').innerText(), '5');
    assert.equal(await admin.locator('[data-publication="count"]').innerText(), '5');
    await layout(admin, 'bank-before-publication');
    await selectFirst(admin, 0);
    assert.equal(await admin.locator('[data-publication="count"]').innerText(), '0');
    assert.equal(await admin.getByRole('button', { name: 'Terbitkan paket terpilih', exact: true }).isDisabled(), true);
    await selectFirst(admin, 10);
    assert.equal(await admin.locator('[data-publication="multiple-choice"]').innerText(), '6');
    assert.equal(await admin.locator('[data-publication="code-fill"]').innerText(), '4');
    assert.equal(await admin.locator('[data-publication="weight"]').innerText(), '10 poin');
    await admin.locator('input[name="question_ids[]"]').last().focus();
    await admin.keyboard.press('Space');
    assert.equal(await admin.locator('[data-publication="count"]').innerText(), '9');
    assert.equal(await admin.locator('.admin-question-select').last().evaluate(el => getComputedStyle(el).outlineStyle), 'solid');
    assert.ok(await admin.locator('.admin-question-select').last().evaluate(el => el.getBoundingClientRect().height >= 44));
    await admin.keyboard.press('Space');
    const stale = await adminContext.newPage();
    await visit(stale, '/admin/questions?quiz_id=1');
    await save(admin, admin.getByRole('button', { name: 'Terbitkan paket terpilih', exact: true }));
    assert.equal(await admin.locator('[data-publication-active="count"]').innerText(), '10');
    await layout(admin, 'bank-ten-active');
    record('Admin creates five drafts; active package stays five until publication; preview, keyboard selection and empty-package guard work');

    await selectFirst(stale, 6);
    await save(stale, stale.getByRole('button', { name: 'Terbitkan paket terpilih', exact: true }));
    assert.match(await stale.locator('[role="alert"]').first().innerText(), /Admin lain/);
    assert.equal(await stale.locator('[data-publication-active="count"]').innerText(), '10');
    record('A stale Admin tab cannot replace the newly published ten-question package');

    await registerAccount(other, base);
    await visit(other, '/materi/dasar-pemrograman-oop');
    await ready(other);
    const ten = await pin(other);
    assert.equal(ten.scoring.total_questions, 10);
    assert.equal(ten.scoring.minimum_correct, 8);
    await selectFirst(admin, 6);
    await save(admin, admin.getByRole('button', { name: 'Terbitkan paket terpilih', exact: true }));
    assert.equal(await admin.locator('[data-publication-active="count"]').innerText(), '6');
    assert.equal(await admin.locator('[data-publication="weight"]').innerText(), '16,67 poin');
    const keys = quizFixtures['dasar-pemrograman-oop'];
    await answer(student, keys, 4);
    await checkResult(student, 5, 4, '80');
    await answer(other, [...keys, ...added], 8);
    await checkResult(other, 10, 8, '80');
    record('Open five- and ten-question attempts retain their own counts and grade as 80 after a six-question package is published');

    await part(student, 'retry').click();
    const six = await pin(student);
    assert.equal(six.scoring.total_questions, 6);
    assert.equal(await part(student, 'counter').innerText(), 'Soal 1 dari 6 soal');
    assert.equal(await part(student, 'answered').innerText(), '0 dari 6 soal dijawab');
    assert.match(await student.locator('[data-quiz-next-reason]').innerText(), /5 dari 6/);
    await answer(student, [...keys, added[0]], 5);
    await checkResult(student, 6, 5, '83,33');
    await layout(student, 'quiz-six-result');
    record('Retry accepts a different API package, clears old answers, updates the passing message and displays 5/6 as 83,33');

    await visit(admin, '/admin/scores?q=' + encodeURIComponent(account.email) + '&chapter=1&status=passed&sort=best');
    assert.equal(await admin.locator('[data-score-user]').count(), 1);
    assert.match(await admin.locator('[data-score-user]').innerText(), /83,33/);
    const [download] = await Promise.all([admin.waitForEvent('download'), admin.getByRole('link', { name: 'Export CSV', exact: true }).click()]);
    const chunks = [];
    for await (const chunk of await download.createReadStream()) chunks.push(chunk);
    const csv = Buffer.concat(chunks).toString('utf8');
    assert.ok(csv.includes(account.email) && csv.includes('83,33'));
    const detail = await admin.locator('[data-score-user] a').getAttribute('href');
    await admin.goto(detail);
    assert.match(await admin.locator('main').innerText(), /83,33/);
    record('Admin status filter, best score, user detail and filtered CSV preserve two decimal places');

    await visit(admin, '/admin/questions?quiz_id=7');
    assert.equal(await admin.locator('[data-question-publication]').count(), 0);
    assert.equal(await admin.locator('input[name="question_ids[]"]:checked').count(), 20);
    assert.match(await admin.locator('main').innerText(), /10 pilihan ganda, 5 isian kode, dan 5 uraian/);
    record('Final Exam retains its separate twenty-question publication form');
    assert.deepEqual(results.errors, []);
    results.status = 'PASS';
} catch (error) {
    results.failure = error.stack;
    throw error;
} finally {
    if (directory) await writeFile(join(directory, 'results.json'), JSON.stringify(results, null, 2));
    await browser.close();
}
