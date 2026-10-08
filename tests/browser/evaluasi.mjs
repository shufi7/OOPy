import { chromium } from 'playwright';
import assert from 'node:assert/strict';

const base = process.env.OOPY_BASE_URL || 'http://127.0.0.1:8017';
const url = `${base}/materi/evaluasi-akhir`;
const key = 'oopy.finalExam.v1';
const browser = await chromium.launch({ channel: process.env.OOPY_BROWSER || 'msedge', headless: true });
const context = await browser.newContext({ viewport: { width: 1440, height: 1000 } });
const page = await context.newPage();
const errors = [];
page.on('pageerror', (error) => errors.push(error.message));
page.on('dialog', (dialog) => dialog.accept());
await page.addInitScript(() => {
    window.__testNow = Number(sessionStorage.getItem('oopy.test.clock') || Date.now());
    sessionStorage.setItem('oopy.test.clock', String(window.__testNow));
    Date.now = () => window.__testNow;
    const schedule = window.setInterval.bind(window), cancel = window.clearInterval.bind(window);
    window.__examIntervals = new Set();
    window.setInterval = (fn, delay, ...args) => {
        const id = schedule(fn, delay, ...args);
        if (delay === 1000) window.__examIntervals.add(id);
        return id;
    };
    window.clearInterval = (id) => { window.__examIntervals.delete(id); return cancel(id); };
});
const part = (role) => page.locator(`[data-exam="${role}"]`);
const inlineAnswer = async (question) => {
    const blank = /_{3,}/.exec(question.code);
    assert.equal(await part('code-answer').evaluate((input) => input.parentElement === document.querySelector('[data-exam="code"]') && input.closest('pre') !== null), true);
    assert.equal(await part('code-before').textContent(), question.code.slice(0, blank.index));
    assert.equal(await part('code-after').textContent(), question.code.slice(blank.index + blank[0].length));
    assert.equal(await part('code-card').locator('input').count(), 1);
    assert.doesNotMatch(await part('code').textContent(), /_{3,}/);
};
const stored = () => page.evaluate((key) => JSON.parse(localStorage.getItem(key)), key);
const ready = () => part('counter').waitFor({ state: 'visible' });
const advance = async (ms) => {
    await page.evaluate((ms) => { window.__testNow += ms; sessionStorage.setItem('oopy.test.clock', String(window.__testNow)); }, ms);
};
const navigate = async (number) => {
    const panel = part('number-panel');
    if (!await panel.evaluate((el) => el.open)) await panel.locator('summary').click();
    await part('numbers').locator('button').nth(number - 1).click();
    assert.equal(await part('counter').textContent(), `Soal ${number} dari 20`);
};
const start = async () => {
    await part('start').click();
    await part('start-dialog').waitFor({ state: 'visible' });
    await part('start-confirm').click();
    await page.waitForURL(`${url}/ujian`);
    await ready();
};
const finish = async () => {
    await part('finish').click();
    await part('finish-dialog').waitFor({ state: 'visible' });
    await part('finish-confirm').evaluate((button) => { button.click(); button.click(); });
    await page.waitForURL((target) => target.pathname.endsWith('/hasil'));
    await part('results').waitFor({ state: 'visible' });
};
const layout = async (label) => {
    for (const width of [320, 390, 768, 1024, 1440]) {
        await page.setViewportSize({ width, height: 1000 });
        await page.evaluate(() => new Promise((resolve) => requestAnimationFrame(() => requestAnimationFrame(resolve))));
        assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1), `${label}: overflow at ${width}px`);
        for (const button of await page.locator('.oopy-final-exam button:visible, .oopy-final-exam a.exam-button:visible').all()) {
            assert.ok(await button.evaluate((el) => el.getBoundingClientRect().right <= innerWidth + 1), `${label}: clipped button at ${width}px`);
        }
        if (label === 'code-fill') {
            await part('code-answer').scrollIntoViewIfNeeded();
            assert.ok(await part('code-answer').evaluate((el) => {
                const rect = el.getBoundingClientRect();
                return rect.left >= 0 && rect.right <= innerWidth + 1;
            }), `Inline code input clipped at ${width}px`);
        }
        if (label === 'exam') {
            const columns = await page.locator('.exam-layout').evaluate((el) => getComputedStyle(el).gridTemplateColumns.split(' ').length);
            assert.equal(columns, width >= 992 ? 2 : 1);
        }
        if (process.env.OOPY_SCREENSHOT_DIR && width === 390 && ['intro', 'exam'].includes(label)) {
            await page.screenshot({ path: `${process.env.OOPY_SCREENSHOT_DIR}/evaluasi-${label}-390.png`, fullPage: true });
        }
    }
};
const fillObjective = async (questions, correct) => {
    for (const [index, question] of questions.slice(0, 15).entries()) {
        await navigate(index + 1);
        if (question.type === 'multiple_choice') await part('options').locator('input').nth(index < correct ? question.correct : (question.correct + 1) % 4).check();
        else {
            await inlineAnswer(question);
            await part('code-answer').fill(index < correct ? ` ${question.answer} ` : question.answer.toUpperCase());
        }
    }
};

try {
    assert.equal((await page.goto(url, { waitUntil: 'networkidle' })).status(), 200);
    assert.equal(await part('history-empty').isVisible(), true);
    assert.equal(await part('history-table').isVisible(), false);
    assert.doesNotMatch(await page.locator('.oopy-final-exam').innerText(), /Mini Project|60 menit|1 jam/);
    assert.equal(await stored(), null);
    await layout('intro');
    if (process.env.OOPY_SCREENSHOT_DIR) await page.screenshot({ path: `${process.env.OOPY_SCREENSHOT_DIR}/evaluasi-intro-1440.png`, fullPage: true });
    await part('start').click();
    assert.equal(await stored(), null, 'Timer must not start before confirmation');
    await part('start-cancel').click();
    assert.equal(await stored(), null);
    const quizProgress = '{"kelas-abstrak":{"passed":true,"bestCorrect":4,"bestScore":80}}';
    await page.evaluate((value) => localStorage.setItem('oopy.quiz.progress', value), quizProgress);
    await start();
    assert.equal(await part('timer').textContent(), '40:00');
    assert.equal(await part('numbers').locator('button').count(), 20);
    assert.equal(await page.evaluate(() => window.__examIntervals.size), 1);
    assert.equal(await part('numbers').locator('[aria-current="step"]').count(), 1);
    await layout('exam');
    if (process.env.OOPY_SCREENSHOT_DIR) await page.screenshot({ path: `${process.env.OOPY_SCREENSHOT_DIR}/evaluasi-exam-1440.png`, fullPage: true });
    const questions = JSON.parse(await part('config').textContent()).questions;
    const first = (await stored()).active;

    await part('options').locator('input').first().focus();
    await page.keyboard.press('Space');
    await page.keyboard.press('ArrowDown');
    assert.equal(await part('options').locator('input').nth(1).isChecked(), true);
    await part('mark').click();
    await navigate(10);
    await part('previous').click();
    assert.equal(await part('counter').textContent(), 'Soal 9 dari 20');
    await navigate(20);
    const essay = '<script>alert(1)</script> ABC menyatakan kontrak minimum.';
    await part('essay-answer').fill(essay);
    await part('mark').click();
    await layout('essay');
    await navigate(11);
    assert.equal(await part('code-group').isVisible(), true);
    await inlineAnswer(questions[10]);
    assert.ok(await part('code').locator('.token.keyword').count() > 0);
    await part('code-answer').fill('self.nilai=nilai');
    await layout('code-fill');
    await page.reload({ waitUntil: 'networkidle' });
    await ready();
    assert.equal(await part('code-answer').inputValue(), 'self.nilai=nilai');
    await inlineAnswer(questions[10]);
    await part('code-answer').fill('self.nilai = nilai');
    assert.equal((await stored()).active.answers.q11, 'self.nilai = nilai');
    if (process.env.OOPY_SCREENSHOT_DIR) await page.screenshot({ path: `${process.env.OOPY_SCREENSHOT_DIR}/evaluasi-inline-code-1440.png`, fullPage: true });
    await navigate(14);
    await inlineAnswer(questions[13]);
    await part('code-answer').fill('baca_data');
    await layout('code-fill');
    for (let number = 11; number <= 15; number++) {
        await navigate(number);
        await inlineAnswer(questions[number - 1]);
        await part('code-answer').fill(questions[number - 1].answer);
        await layout('code-fill');
    }
    await navigate(20);
    await advance(105_000);
    await page.reload({ waitUntil: 'networkidle' });
    await ready();
    assert.equal(await part('counter').textContent(), 'Soal 20 dari 20');
    assert.equal(await part('essay-answer').inputValue(), essay);
    assert.equal(await part('mark').getAttribute('aria-pressed'), 'true');
    assert.equal((await stored()).active.deadline, first.deadline);
    assert.equal((await stored()).active.startedAt, first.startedAt);
    assert.equal(await part('timer').textContent(), '38:15');
    assert.equal(await page.evaluate(() => window.__examIntervals.size), 1);
    await navigate(1);
    assert.equal(await part('options').locator('input').nth(1).isChecked(), true);
    assert.equal(await part('mark').getAttribute('aria-pressed'), 'true');
    assert.equal(await part('counter').evaluate((el) => el === document.activeElement), true);
    assert.doesNotMatch(await page.locator('.oopy-final-exam').innerText(), /Jawaban benar:|Tidak benar \/ belum dijawab|Lulus Evaluasi Akhir/);
    // Leaving for another page does not reset the deadline or answers.
    await page.goto(`${base}/materi/kelas-abstrak`, { waitUntil: 'domcontentloaded' });
    await page.goto(url, { waitUntil: 'networkidle' });
    assert.equal(await part('resume').isVisible(), true);
    assert.equal(await part('start').isVisible(), false);
    await part('resume').click(); await ready();
    assert.equal((await stored()).active.deadline, first.deadline);
    await fillObjective(questions, 10);
    for (let number = 16; number <= 20; number++) {
        await navigate(number);
        await part('essay-answer').fill(number === 20 ? essay : `Jawaban uraian ${number}.`);
    }
    assert.equal(await part('progress-text').textContent(), '20 dari 20 soal terjawab');
    assert.equal(await part('progress').getAttribute('value'), '20');
    await part('finish').click();
    assert.match(await part('finish-summary').textContent(), /20 soal terjawab, 0 soal belum dijawab/);
    await part('finish-cancel').click();
    assert.equal((await stored()).active.status, 'active');
    await finish();
    assert.equal(await part('score').textContent(), '66,67');
    assert.equal(await part('correct').textContent(), '10 dari 15');
    assert.equal(await part('incorrect').textContent(), '5 dari 15');
    assert.equal(await part('essays').textContent(), '5 dari 5 — Belum dinilai');
    assert.equal(await part('objective-status').textContent(), 'Belum memenuhi ambang objektif');
    assert.equal(await part('start').isEnabled(), true);
    assert.equal((await stored()).history.length, 1);
    assert.doesNotMatch(await page.locator('.oopy-final-exam').innerText(), /Mini Project|Mulai Lagi dalam/);
    await part('review-open').click();
    assert.match(await part('review-list').innerText(), /Jawaban benar:/);
    assert.match(await part('review-list').innerText(), /<script>alert\(1\)<\/script>/);
    assert.equal(await part('review-list').locator('script, img').count(), 0);
    assert.equal(await part('review-list').locator('.exam-review-item').count(), 20);
    await layout('results');
    await page.reload({ waitUntil: 'networkidle' });
    assert.equal((await stored()).history.length, 1);
    // Existing failed results must not retain the retired one-hour restriction.
    await page.evaluate((key) => {
        const state = JSON.parse(localStorage.getItem(key));
        state.history[0].policy.cooldownSeconds = 3600;
        localStorage.setItem(key, JSON.stringify(state));
    }, key);
    await page.reload({ waitUntil: 'networkidle' });
    assert.equal(await part('start').isEnabled(), true);
    await page.waitForFunction(() => !document.querySelector('[data-exam="start"]').disabled);
    await start();
    await fillObjective(questions, 11);
    await advance(1000);
    await finish();
    assert.equal(await part('score').textContent(), '73,33');
    assert.equal(await part('objective-status').textContent(), 'Memenuhi ambang objektif');
    assert.equal(await part('essays').textContent(), '0 dari 5 — Belum dinilai');
    assert.equal(await part('start').isEnabled(), true);
    assert.equal((await stored()).history.length, 2);
    assert.doesNotMatch(await page.locator('.oopy-final-exam').innerText(), /Lulus Evaluasi Akhir/);
    await page.goto(url, { waitUntil: 'networkidle' });
    assert.equal(await part('history').locator('tr').count(), 2);
    assert.match(await part('history').locator('tr').first().innerText(), /73,33/);
    await start();
    await part('options').locator('input').nth(questions[0].correct).check();
    await navigate(16); await part('essay-answer').fill('x');
    await advance(35 * 60_000);
    await page.waitForFunction(() => document.querySelector('.exam-timer').dataset.warning === 'five');
    await advance(4 * 60_000 + 1000);
    await page.waitForFunction(() => document.querySelector('.exam-timer').dataset.warning === 'one');
    assert.equal(await part('timer').textContent(), '00:59');
    await part('finish').click();
    await advance(59_000);
    await page.waitForURL((target) => target.pathname.endsWith('/hasil'));
    assert.equal(await part('completion-reason').textContent(), 'Waktu habis');
    assert.equal(await part('duration').textContent(), '40:00');
    assert.equal(await part('correct').textContent(), '1 dari 15');
    assert.equal(await part('essays').textContent(), '1 dari 5 — Belum dinilai');
    assert.equal((await stored()).history.length, 3);
    await page.reload({ waitUntil: 'networkidle' });
    assert.equal((await stored()).history.length, 3);
    assert.equal(await page.evaluate(() => localStorage.getItem('oopy.quiz.progress')), quizProgress);
    // Expiration on the intro page also permits an immediate retry.
    await page.waitForFunction(() => !document.querySelector('[data-exam="start"]').disabled);
    await start();
    await page.goto(url, { waitUntil: 'networkidle' });
    assert.equal(await part('resume').isVisible(), true);
    await advance(40 * 60_000);
    await page.waitForFunction(() => !document.querySelector('[data-exam="start"]').disabled && document.querySelector('[data-exam="resume"]').hidden);
    assert.equal((await stored()).history.length, 4);
    assert.equal(await page.evaluate(() => window.__examIntervals.size), 1);
    assert.equal(await part('start').textContent(), 'Ulangi Evaluasi');
    console.log('PASS: confirmation, 20-number navigation, keyboard/flags, autosave/resume/refresh, objective threshold, ungraded essays/review, immediate retries, deadline warnings/expiry and idempotent history');

    const damaged = await browser.newContext();
    const damagedPage = await damaged.newPage();
    await damagedPage.goto(url, { waitUntil: 'networkidle' });
    for (const raw of ['{broken', '[]', 'null', JSON.stringify({ version: 1, history: [], active: { bad: true } })]) {
        await damagedPage.evaluate(({ key, raw }) => localStorage.setItem(key, raw), { key, raw });
        await damagedPage.reload({ waitUntil: 'networkidle' });
        assert.equal(await damagedPage.locator('[data-exam="storage-warning"]').isVisible(), true);
        assert.equal(await damagedPage.locator('[data-exam="history-empty"]').isVisible(), true);
    }
    await damaged.close();

    const denied = await browser.newContext();
    const deniedPage = await denied.newPage();
    await deniedPage.addInitScript(() => {
        Storage.prototype.getItem = () => { throw new Error('Storage denied'); };
        Storage.prototype.setItem = () => { throw new Error('Storage denied'); };
    });
    await deniedPage.goto(url, { waitUntil: 'networkidle' });
    assert.equal(await deniedPage.locator('[data-exam="storage-warning"]').isVisible(), true);
    await deniedPage.locator('[data-exam="start"]').click();
    await deniedPage.locator('[data-exam="start-confirm"]').click();
    assert.equal(deniedPage.url(), url);
    assert.match(await deniedPage.locator('[data-exam="storage-warning"]').textContent(), /belum dimulai/);
    await denied.close();

    const failedWrite = await browser.newContext();
    const failedPage = await failedWrite.newPage();
    await failedPage.goto(url, { waitUntil: 'networkidle' });
    await failedPage.locator('[data-exam="start"]').click();
    await failedPage.locator('[data-exam="start-confirm"]').click();
    await failedPage.waitForURL(`${url}/ujian`);
    await failedPage.locator('[data-exam="counter"]').waitFor({ state: 'visible' });
    await failedPage.evaluate(() => { window.restoreStorage = Storage.prototype.setItem; Storage.prototype.setItem = () => { throw new Error('Quota exceeded'); }; });
    await failedPage.locator('[data-exam="numbers"] button').nth(15).click();
    await failedPage.locator('[data-exam="essay-answer"]').fill('Jawaban dalam memori.');
    await failedPage.locator('[data-exam="finish"]').click();
    await failedPage.locator('[data-exam="finish-confirm"]').click();
    assert.equal(await failedPage.locator('[data-exam="local-results"]').isVisible(), true);
    assert.match(await failedPage.locator('[data-exam="local-summary"]').textContent(), /Uraian 1 dari 5/);
    await failedPage.evaluate(() => { Storage.prototype.setItem = window.restoreStorage; });
    await failedPage.locator('[data-exam="retry-save"]').click();
    await failedPage.waitForURL((target) => target.pathname.endsWith('/hasil'));
    assert.equal(await failedPage.locator('[data-exam="essays"]').textContent(), '1 dari 5 — Belum dinilai');
    await failedWrite.close();
    console.log('PASS: corrupted/denied storage, no phantom start, in-memory completion and storage retry recovery');

    assert.equal((await page.goto(`${url}/mini-project`, { waitUntil: 'domcontentloaded' })).status(), 404);
    assert.deepEqual(errors, []);
    console.log('PASS: mini project removed; inline code answers and all pages responsive at 320/390/768/1024/1440px; no page errors');
} finally {
    await browser.close();
}
