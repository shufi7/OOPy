// Use a fresh database initialized by admin-sandbox.php, never the application database.
import { chromium } from 'playwright';
import assert from 'node:assert/strict';
import { mkdir, readFile, writeFile } from 'node:fs/promises';
import { join } from 'node:path';
import { quizFixtures } from './quiz-helpers.mjs';

assert.match(process.env.OOPY_ADMIN_TEST_DATABASE || '', /^admin-coding-[a-f0-9]{32}\.sqlite$/);
const base = process.env.OOPY_BASE_URL || 'http://127.0.0.1:8053';
const directory = process.env.OOPY_SCREENSHOT_DIR;
if (directory) await mkdir(directory, { recursive: true });
const assets = process.env.OOPY_ASSET_MANIFEST ? JSON.parse(await readFile(process.env.OOPY_ASSET_MANIFEST, 'utf8')) : {};
const browser = await chromium.launch({ channel: process.env.OOPY_BROWSER || 'msedge', headless: true });
const results = { interactions: [], layouts: [], errors: [] };
async function context() {
    const context = await browser.newContext({ viewport: { width: 1440, height: 1000 }, reducedMotion: 'reduce' });
    await context.route('**/*', route => {
        const url = route.request().url();
        if (!assets[url]) return route.continue();
        const path = new URL(url).pathname;
        const contentType = url.includes('fonts.googleapis.com') || path.endsWith('.css') ? 'text/css'
            : path.endsWith('.js') ? 'application/javascript' : path.endsWith('.woff2') ? 'font/woff2'
                : path.endsWith('.ttf') ? 'font/ttf' : 'application/octet-stream';
        return route.fulfill({ path: assets[url], contentType });
    });
    context.on('page', page => page.on('pageerror', error => results.errors.push(error.message)));
    return context;
}
const admin = await (await context()).newPage();
const student = await (await context()).newPage();
async function visit(page, path) {
    const response = await page.goto(base + path, { waitUntil: 'load' });
    assert.equal(response.status(), 200);
}
async function submit(page, button) {
    await Promise.all([page.waitForNavigation({ waitUntil: 'load' }), button.click()]);
}
async function login(page, email) {
    await visit(page, '/login');
    await page.locator('#email').fill(email);
    await page.locator('#password').fill('password123');
    await submit(page, page.locator('button[type="submit"]'));
}
const record = message => { results.interactions.push(message); console.log(`PASS ${message}`); };
const row = () => admin.getByRole('row').filter({ hasText: 'Soal arsip nomor 6' });
const publish = () => submit(admin, admin.getByRole('button', { name: 'Terbitkan paket terpilih', exact: true }));

try {
    await login(admin, 'admin.browser@example.test');
    await visit(admin, '/admin/questions/create?quiz_id=1');
    await admin.locator('#question_type').selectOption('multiple_choice');
    await admin.locator('#question_text').fill('Soal arsip nomor 6');
    await admin.locator('#options_text').fill('Benar\nSalah');
    await admin.locator('#correct_answer').fill('0');
    await admin.locator('#display_order').fill('6');
    await submit(admin, admin.getByRole('button', { name: 'Simpan draft', exact: true }));
    await visit(admin, '/admin/questions?quiz_id=1');
    await row().getByRole('checkbox').check();
    await publish();
    assert.equal(await admin.locator('[data-publication-active="count"]').innerText(), '6');

    await login(student, 'empty.browser@example.test');
    await visit(student, '/materi/dasar-pemrograman-oop');
    await student.locator('[data-quiz="form"]').waitFor({ state: 'visible' });
    const started = student.waitForResponse(response => response.url().endsWith('/kuis/attempts') && response.request().method() === 'POST');
    await student.locator('[data-quiz="options"] input').first().check();
    assert.equal((await (await started).json()).questions.length, 6);

    await row().getByRole('checkbox').uncheck();
    await publish();
    assert.equal(await admin.locator('[data-publication-active="count"]').innerText(), '5');
    assert.match(await row().innerText(), /Diarsipkan/);
    assert.equal(await row().getByRole('checkbox').isChecked(), false);
    await student.reload({ waitUntil: 'load' });
    assert.equal(await student.locator('[data-quiz="counter"]').innerText(), 'Soal 1 dari 6 soal');
    record('Question 6 becomes archived and remains selectable; the open student attempt still contains six questions');

    await admin.bringToFront();
    await row().getByRole('checkbox').focus();
    await admin.keyboard.press('Space');
    assert.equal(await row().getByRole('checkbox').isChecked(), true);
    assert.equal(await admin.locator('[data-publication="count"]').innerText(), '6');
    assert.equal(await admin.locator('[data-publication="weight"]').innerText(), '16,67 poin');
    assert.equal(await admin.locator('[data-publication-active="count"]').innerText(), '5');
    for (const width of [390, 1440]) {
        await admin.setViewportSize({ width, height: 1000 });
        assert.ok(await admin.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1));
        await admin.evaluate(() => { document.activeElement?.blur(); window.scrollTo(0, 0); });
        if (directory) await admin.screenshot({ path: join(directory, `archived-selected-${width}.png`), fullPage: true });
        results.layouts.push({ width, status: 'PASS' });
    }
    await publish();
    assert.equal(await admin.locator('[data-publication-active="count"]').innerText(), '6');
    assert.match(await row().innerText(), /Aktif/);
    assert.equal(await row().getByRole('checkbox').isChecked(), true);
    record('Admin checks archived question 6 with Space; preview changes to six and publication reactivates it');

    const keys = [...quizFixtures['dasar-pemrograman-oop'], { type: 'multiple_choice', correct: 0 }];
    for (const [index, key] of keys.entries()) {
        if (key.type === 'code_fill') await student.locator('[data-quiz="code-fill"]').fill(key.answer);
        else await student.locator('[data-quiz="options"] input').nth(index === 5 ? 1 : key.correct).check();
        await student.locator('[data-quiz="next"]').click();
    }
    await student.locator('[data-quiz="results"]').waitFor({ state: 'visible' });
    assert.equal(await student.locator('[data-quiz="score"]').innerText(), '83,33');
    assert.equal(await student.locator('[data-quiz="correct"]').innerText(), '5');
    record('Student completes the original six-question attempt after archival/reactivation and receives 83,33 for five correct answers');
    assert.deepEqual(results.errors, []);
    results.status = 'PASS';
} catch (error) {
    results.failure = error.stack;
    throw error;
} finally {
    if (directory) await writeFile(join(directory, 'results.json'), JSON.stringify(results, null, 2));
    await browser.close();
}
