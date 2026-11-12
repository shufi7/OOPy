// Run only against a fresh tests/browser/admin-sandbox.php database.
import { chromium } from 'playwright';
import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { mkdir } from 'node:fs/promises';
import { cacheAssets, loginAccount } from './quiz-helpers.mjs';

assert.match(process.env.OOPY_ADMIN_TEST_DATABASE || '', /^admin-coding-[a-f0-9]{32}\.sqlite$/);
const base = process.env.OOPY_BASE_URL || 'http://127.0.0.1:8017';
const url = `${base}/materi/evaluasi-akhir`;
const directory = process.env.OOPY_SCREENSHOT_DIR;
if (directory) await mkdir(directory, { recursive: true });
const bank = JSON.parse(execFileSync('php', ['-r', "echo json_encode((require 'resources/materi/evaluasi-akhir.php')['questions']);"], { encoding: 'utf8' }));
const browser = await chromium.launch({ channel: process.env.OOPY_BROWSER || 'msedge', headless: true });
const errors = [];
const context = await browser.newContext({ viewport: { width: 1440, height: 1000 }, reducedMotion: 'reduce' });
await cacheAssets(context);
const page = await context.newPage();
page.on('pageerror', error => errors.push(error.message));
const part = role => page.locator(`[data-exam="${role}"]`);
const ready = () => part('counter').waitFor({ state: 'visible' });
const state = () => page.evaluate(async () => (await fetch('/evaluasi/attempts', { headers: { Accept: 'application/json' } })).json());
const navigate = async number => {
    if (!await part('number-panel').evaluate(el => el.open)) await part('number-panel').locator('summary').click();
    await part('numbers').locator('button').nth(number - 1).click();
    assert.equal(await part('counter').textContent(), `Soal ${number} dari 20`);
};
const start = async () => {
    await part('start').click();
    await part('start-dialog').waitFor({ state: 'visible' });
    await part('start-confirm').click();
    await page.waitForURL(`${url}/ujian`);
    await ready();
    const attempt = (await state()).active;
    assert.equal(attempt.questions.length, 20);
    assert.equal(attempt.questions.filter(q => q.type === 'code_fill').length, 10);
    assert.equal(attempt.questions.filter(q => q.type === 'essay').length, 0);
    assert.equal(attempt.deadline - attempt.startedAt, 2400_000);
    for (const q of attempt.questions) {
        assert.equal(Object.hasOwn(q, 'correct'), false);
        assert.equal(Object.hasOwn(q, 'answer'), false);
    }
    // Page loading can consume a second; the server deadline is authoritative.
    const [minutes, seconds] = (await part('timer').textContent()).split(':').map(Number);
    assert.ok(minutes * 60 + seconds <= 2400 && minutes * 60 + seconds >= 2385);
    return attempt;
};
const finish = async () => {
    await part('finish').click();
    await part('finish-dialog').waitFor({ state: 'visible' });
    await part('finish-confirm').evaluate(button => { button.click(); button.click(); });
    await page.waitForURL(target => target.pathname.endsWith('/hasil'));
    await part('results').waitFor({ state: 'visible' });
};
const layout = async label => {
    for (const width of [320, 390, 768, 1024, 1440]) {
        await page.setViewportSize({ width, height: 1000 });
        await page.evaluate(() => new Promise(resolve => requestAnimationFrame(resolve)));
        assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1), `${label}: overflow at ${width}`);
        if (label === 'code') {
            await part('code-answer').scrollIntoViewIfNeeded();
            assert.ok(await part('code-answer').evaluate(el => el.getBoundingClientRect().left >= 0 && el.getBoundingClientRect().right <= innerWidth + 1));
        }
        if (directory && [390, 1440].includes(width)) await page.screenshot({ path: `${directory}/${label}-${width}.png`, fullPage: true });
    }
};
const answerAll = async correct => {
    for (const [index, q] of bank.entries()) {
        await navigate(index + 1);
        assert.equal(await part('essay-group').isVisible(), false);
        if (q.type === 'multiple_choice') {
            assert.equal(await part('code-answer').isVisible(), false);
            assert.equal(await part('options').locator('input').count(), 4);
            if (q.code) assert.equal(await part('code-caption').textContent(), 'Potongan kode Python');
            if (q.code) {
                const shown = await part('code-before').textContent() + await part('code-after').textContent();
                assert.equal(shown, q.code);
            }
            await part('options').locator('input').nth(index < correct ? q.correct : (q.correct + 1) % q.options.length).check();
        } else {
            const blank = /_{3,}/.exec(q.code);
            assert.equal(await part('code-before').textContent(), q.code.slice(0, blank.index));
            assert.equal(await part('code-after').textContent(), q.code.slice(blank.index + blank[0].length));
            await part('code-answer').fill(index < correct ? q.answer : '<script>alert(1)</script>');
        }
        for (const width of [320, 1440]) {
            await page.setViewportSize({ width, height: 1000 });
            assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1), `question ${index + 1} overflow at ${width}`);
        }
    }
    assert.equal(await part('progress-text').textContent(), '20 dari 20 soal terjawab');
};

try {
    await page.goto(url);
    await page.waitForFunction(() => !document.querySelector('[data-exam=start]').disabled);
    assert.match(await page.locator('.oopy-final-exam').innerText(), /10 pilihan ganda dan 10 isian kode Python/);
    await layout('intro');
    await part('start').click();
    await page.keyboard.press('Escape');
    assert.equal((await state()).active, null);
    const first = await start();
    assert.equal(first.official, false);
    await page.evaluate(() => { window.__realNow = Date.now; Date.now = () => window.__realNow() + 35 * 60_000; });
    await page.waitForFunction(() => document.querySelector('.exam-timer').dataset.warning === 'five');
    await page.evaluate(() => { Date.now = () => window.__realNow() + 39 * 60_000; });
    await page.waitForFunction(() => document.querySelector('.exam-timer').dataset.warning === 'one');
    await page.evaluate(() => { Date.now = window.__realNow; });
    await part('options').locator('input').first().focus();
    await page.keyboard.press('Space');
    await part('mark').click();
    await part('next').click();
    await part('previous').click();
    assert.equal(await part('mark').getAttribute('aria-pressed'), 'true');
    await layout('exam');
    await navigate(11);
    await context.route('**/evaluasi/attempts/*/answers', route => route.abort('failed'));
    await part('code-answer').fill('self.nilai=nilai');
    await page.waitForFunction(() => document.querySelector('[data-exam=save-status]').textContent.includes('belum tersimpan'));
    await context.unroute('**/evaluasi/attempts/*/answers');
    await part('code-answer').fill('self.nilai = nilai');
    await page.waitForFunction(() => document.querySelector('[data-exam=save-status]').textContent === 'Jawaban tersimpan pada server.');
    assert.equal(await part('storage-warning').isVisible(), false);
    await layout('code');
    await page.reload();
    await ready();
    assert.equal(await part('code-answer').inputValue(), 'self.nilai = nilai');
    assert.equal((await state()).active.deadline, first.deadline);
    await navigate(1);
    assert.equal(await part('mark').getAttribute('aria-pressed'), 'true');
    await page.goto(url);
    await part('resume').click();
    await ready();
    await answerAll(13);
    await part('finish').click();
    await part('finish-cancel').click();
    assert.equal((await state()).active.id, first.id);
    await finish();
    assert.equal(await part('score').textContent(), '65');
    assert.equal(await part('objective-status').textContent(), 'Belum Lulus');
    assert.equal(await part('correct').textContent(), '13 dari 20');
    assert.equal(await part('incorrect').textContent(), '7 dari 20');
    assert.equal(await part('essay-result').isVisible(), false);
    await part('review-open').click();
    assert.equal(await part('review-list').locator('section').count(), 20);
    assert.equal(await part('review-list').locator('script').count(), 0);
    assert.doesNotMatch(await part('review-list').innerText(), /Jawaban benar:/);
    await layout('results');
    await page.reload();
    await part('results').waitFor({ state: 'visible' });
    assert.equal((await state()).history.length, 1);
    await page.goto(url);
    await part('history-table').waitFor({ state: 'visible' });
    assert.match(await part('history').innerText(), /65/);
    assert.doesNotMatch(await part('history').innerText(), /Menunggu|Belum dinilai/);
    console.log('PASS guest: composition, keyboard, navigation, flags, autosave, refresh, deadline, score 65, review, immutable history, layouts');

    await loginAccount(page, base, { email: 'student.browser@example.test', password: 'password123' });
    await page.goto(url);
    const official = await start();
    assert.equal(official.official, true);
    await answerAll(14);
    await context.route('**/evaluasi/attempts/*/submit', route => route.abort('failed'));
    await part('finish').click();
    await part('finish-confirm').click();
    await part('local-results').waitFor({ state: 'visible' });
    assert.match(await part('local-summary').textContent(), /belum dikumpulkan/);
    await context.unroute('**/evaluasi/attempts/*/submit');
    await part('retry-save').click();
    await page.waitForURL(target => target.pathname.endsWith('/hasil'));
    await part('results').waitFor({ state: 'visible' });
    assert.equal(await part('score').textContent(), '70');
    assert.equal(await part('objective-status').textContent(), 'Lulus');
    await page.goto(`${base}/dashboard`);
    assert.match(await page.locator('[data-dashboard=final-exam]').innerText(), /70/);
    await layout('dashboard');
    const adminContext = await browser.newContext();
    await cacheAssets(adminContext);
    const admin = await adminContext.newPage();
    await loginAccount(admin, base, { email: 'admin.browser@example.test', password: 'password123' });
    await admin.goto(`${base}/admin/evaluations/${official.id}`);
    assert.equal(await admin.locator('input[name^="scores["]').count(), 0);
    assert.match(await admin.locator('main').innerText(), /70/);
    const csv = await admin.evaluate(async () => (await fetch('/admin/scores/export?q=student.browser%40example.test')).text());
    assert.match(csv, /;70;Dinilai;/);
    await adminContext.close();
    await page.goto(url);
    await start();
    await answerAll(20);
    await finish();
    assert.equal(await part('score').textContent(), '100');
    assert.equal(await part('objective-status').textContent(), 'Lulus');
    console.log('PASS official: score 70 and 100, submission retry after network failure, dashboard, Admin automatic result, CSV');
    assert.deepEqual(errors, []);
} finally {
    await browser.close();
}
