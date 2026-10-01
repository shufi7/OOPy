import { chromium } from 'playwright';
import assert from 'node:assert/strict';

const base = process.env.OOPY_BASE_URL || 'http://127.0.0.1:8017';
const browser = await chromium.launch({ channel: process.env.OOPY_BROWSER || 'msedge', headless: true });
const page = await browser.newPage({ viewport: { width: 1440, height: 1000 } });
const errors = [];
const writes = [];
page.on('pageerror', (error) => errors.push(error.message));
page.on('request', (request) => { if (request.method() !== 'GET') writes.push(request.url()); });
const quiz = page.locator('#kuis');
const part = (role) => quiz.locator(`[data-quiz="${role}"]`);
const options = () => part('options').locator('input[type="radio"]');
const choose = (index) => options().nth(index).check();
const next = () => part('next').click();

try {
    await page.goto(`${base}/materi/dasar-pemrograman-oop`, { waitUntil: 'domcontentloaded' });
    await part('form').waitFor({ state: 'visible' });
    assert.equal(await page.locator('#latihan, a[href="#latihan"]').count(), 0);
    assert.equal(await quiz.evaluate((el) => el.previousElementSibling.id), 'rangkuman');
    await page.locator('.material-toc a[href="#kuis"]').click();
    await page.waitForFunction(() => document.querySelector('.material-toc a[href="#kuis"]').getAttribute('aria-current') === 'location');
    const originalOutput = await page.locator('[data-role="code-output"]').textContent();
    assert.equal(await part('counter').textContent(), 'Soal 1 dari 5 soal');
    assert.equal(await part('previous').isDisabled(), true);
    await part('instructions-toggle').click();
    assert.equal(await part('instructions-toggle').getAttribute('aria-expanded'), 'true');
    assert.equal(await page.locator('#quiz-instructions').isVisible(), true);
    await part('instructions-toggle').click();
    assert.equal(await page.locator('#quiz-instructions').isVisible(), false);

    // Skipping is allowed, but finishing an incomplete quiz returns to a missing answer.
    for (let i = 0; i < 4; i++) await next();
    assert.equal(await part('next').textContent(), 'Selesai Kuis');
    await next();
    assert.match(await part('validation').textContent(), /belum lengkap/);
    assert.equal(await part('results').isVisible(), false);
    assert.equal(await part('counter').textContent(), 'Soal 1 dari 5 soal');

    // Native radio keyboard behavior, selection persistence, then a changed answer.
    await options().nth(0).focus();
    await page.keyboard.press('Space');
    await page.keyboard.press('ArrowDown');
    assert.equal(await options().nth(1).isChecked(), true);
    await next();
    assert.equal(await part('counter').evaluate((el) => el === document.activeElement), true);
    await part('previous').click();
    assert.equal(await options().nth(1).isChecked(), true);
    await choose(0);
    assert.equal(await part('review').locator('li').count(), 0);
    assert.equal(await part('results').isVisible(), false);
    await next();
    for (const answer of [1, 2, 1, 0]) { await choose(answer); await next(); }
    assert.equal(await part('score').textContent(), '4 / 5');
    assert.equal(await part('percentage').textContent(), '80%');
    assert.match(await part('totals').textContent(), /Jawaban benar: 4.*Jawaban salah: 1/);
    assert.equal(await part('review').locator('li').count(), 5);
    assert.equal(await part('review').locator('.is-correct').count(), 4);
    assert.equal(await part('review').locator('.is-incorrect').count(), 1);
    assert.match(await part('review').locator('li').last().textContent(), /Jawaban kamu: A.*Jawaban benar: D.*Penjelasan:/s);
    assert.equal(await part('result-title').evaluate((el) => el === document.activeElement), true);
    assert.equal(await part('form').isVisible(), false);
    console.log('PASS: instructions, counter, incomplete submission, keyboard, retained/changed answers, 80% score and five reviews');

    for (const [answers, expected] of [[[1, 0, 0, 0, 0], '0%'], [[0, 1, 2, 1, 3], '100%']]) {
        await part('retry').click();
        assert.equal(await options().evaluateAll((inputs) => inputs.filter((input) => input.checked).length), 0);
        assert.equal(await part('answered').textContent(), '0 dari 5 soal dijawab');
        assert.equal(await part('previous').isDisabled(), true);
        for (const answer of answers) { await choose(answer); await next(); }
        assert.equal(await part('percentage').textContent(), expected);
    }
    assert.equal(await page.locator('[data-role="code-output"]').textContent(), originalOutput);
    assert.equal(await page.locator('.material-progress progress').getAttribute('value'), '0');
    assert.deepEqual(writes, []);
    console.log('PASS: retry resets answers; 0% and 100% scoring; no server writes or changes to Live Coding/chapter progress');

    await part('retry').click();
    for (const width of [390, 768, 1024, 1440]) {
        await page.setViewportSize({ width, height: 1000 });
        await quiz.evaluate((el) => el.scrollIntoView({ block: 'start', behavior: 'instant' }));
        assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1), `Overflow at ${width}px`);
        const optionSizes = await part('options').locator('label').evaluateAll((labels) => labels.map((el) => ({ width: el.getBoundingClientRect().width, parent: el.parentElement.getBoundingClientRect().width })));
        assert.ok(optionSizes.every(({ width, parent }) => Math.abs(width - parent) < 1));
        if (width === 390) assert.equal(await part('next').evaluate((el) => getComputedStyle(el.parentElement).flexDirection), 'column');
        if (process.env.OOPY_SCREENSHOT_DIR && [390, 1440].includes(width)) {
            await page.screenshot({ path: `${process.env.OOPY_SCREENSHOT_DIR}/quiz-${width}.png` });
        }
    }
    await choose(2);
    await page.reload({ waitUntil: 'domcontentloaded' });
    await part('form').waitFor({ state: 'visible' });
    assert.equal(await part('answered').textContent(), '0 dari 5 soal dijawab');
    assert.equal(await part('results').isVisible(), false);
    assert.deepEqual(errors, []);
    console.log('PASS: 390/768/1024/1440px layout, full-width choices, refresh reset, no page errors');

    await page.goto(`${base}/materi/kelas-dan-objek`, { waitUntil: 'domcontentloaded' });
    await part('form').waitFor({ state: 'visible' });
    assert.equal(await quiz.evaluate((el) => el.previousElementSibling.id), 'refleksi');
    assert.equal(await part('counter').textContent(), 'Soal 1 dari 8 soal');
    const questions = JSON.parse(await part('questions').textContent());
    for (const question of questions) { await choose(question.correct); await next(); }
    assert.equal(await part('score').textContent(), '8 / 8');
    assert.equal(await part('percentage').textContent(), '100%');
    assert.equal(await part('review').locator('li').count(), 8);
    assert.ok(await part('review').locator('.token.keyword').count() > 0);
    for (const width of [320, 390, 768, 1024, 1440]) {
        await page.setViewportSize({ width, height: 1000 });
        assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1), `BAB 2 quiz overflow at ${width}px`);
    }
    await part('retry').click();
    assert.equal(await part('answered').textContent(), '0 dari 8 soal dijawab');
    assert.equal(await page.locator('.material-progress progress').getAttribute('value'), '0');
    assert.deepEqual(writes, []);
    assert.deepEqual(errors, []);
    console.log('PASS: BAB 2 eight-question quiz, scoring, highlighted reviews, retry, responsive results, no persistence');
} finally {
    await browser.close();
}
