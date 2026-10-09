import { chromium } from 'playwright';
import assert from 'node:assert/strict';
import { chapters } from './chapters.mjs';
import { cacheAssets, registerAccount, loginAccount, logoutAccount, answerQuiz, quizFixtures } from './quiz-helpers.mjs';

const base = process.env.OOPY_BASE_URL || 'http://127.0.0.1:8017';
const browser = await chromium.launch({ channel: process.env.OOPY_BROWSER || 'msedge', headless: true });
const context = await browser.newContext({ viewport: { width: 1440, height: 1000 } });
await cacheAssets(context);
const page = await context.newPage();
const errors = [];
page.on('pageerror', error => errors.push(error.message));
const quiz = page.locator('#kuis');
const part = role => quiz.locator(`[data-quiz="${role}"]`);
const ready = () => part('form').waitFor({ state: 'visible' });
const load = async slug => {
    const progressResponse = page.waitForResponse(response => response.url().endsWith(`/materi/${slug}/kuis/progress`));
    await page.goto(`${base}/materi/${slug}`, { waitUntil: 'domcontentloaded' });
    await ready();
    await progressResponse;
};
const gate = async unlocked => {
    assert.equal(await page.locator('[data-quiz-next-link]').isVisible(), unlocked);
    assert.equal(await page.locator('[data-quiz-next-locked]').isVisible(), !unlocked);
};
const result = async (correct, unlocked = correct >= 4) => {
    await part('results').waitFor({ state: 'visible' });
    assert.equal(await part('score').textContent(), String(correct * 20));
    assert.equal(await part('correct').textContent(), String(correct));
    assert.equal(await part('incorrect').textContent(), String(5 - correct));
    assert.equal(await part('status').textContent(), correct >= 4 ? 'Lulus' : 'Belum Lulus');
    assert.equal(await part('continue').isVisible(), unlocked);
    assert.equal(await part('result-title').evaluate(el => el === document.activeElement), true);
    assert.equal(await quiz.locator('.oopy-quiz-review, .is-correct, .is-incorrect, [data-quiz="review"]').count(), 0);
    assert.doesNotMatch(await quiz.innerText(), /Pembahasan|Jawaban benar:|Jawaban kamu:|Penjelasan:/i);
    await gate(unlocked);
};
const progress = async slug => {
    const response = await page.request.get(`${base}/materi/${slug}/kuis/progress`);
    assert.equal(response.status(), 200);
    return response.json();
};
const retry = async () => {
    await part('retry').click();
    await ready();
    assert.equal(await part('counter').textContent(), 'Soal 1 dari 5 soal');
    assert.equal(await part('answered').textContent(), '0 dari 5 soal dijawab');
    assert.equal(await part('options').locator('input:checked').count(), 0);
};
const layout = async label => {
    for (const width of [320, 390, 768, 1024, 1440]) {
        await page.setViewportSize({ width, height: 1000 });
        await quiz.evaluate(el => el.scrollIntoView({ block: 'start', behavior: 'instant' }));
        assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1), `${label}: overflow at ${width}px`);
        for (const button of await quiz.locator('button:visible, a.btn:visible').all()) {
            assert.ok(await button.evaluate(el => el.getBoundingClientRect().right <= innerWidth + 1), `${label}: clipped button at ${width}px`);
        }
        if (await part('code-fill').isVisible()) {
            await part('code-fill').scrollIntoViewIfNeeded();
            assert.ok(await part('code-fill').evaluate(el => {
                const rect = el.getBoundingClientRect();
                return rect.left >= 0 && rect.right <= innerWidth + 1 && rect.height >= 44;
            }));
        }
        if (process.env.OOPY_SCREENSHOT_DIR && [390, 1440].includes(width)) {
            await page.screenshot({ path: `${process.env.OOPY_SCREENSHOT_DIR}/${label}-${width}.png` });
        }
    }
};
const noSecrets = data => {
    for (const [key, value] of Object.entries(data)) {
        assert.ok(!['correct', 'correct_answer', 'answer', 'answers', 'is_correct', 'explanation'].includes(key), `Secret field ${key}`);
        if (value && typeof value === 'object') noSecrets(value);
    }
};

try {
    const account = await registerAccount(page, base);
    await logoutAccount(page, base);
    await loginAccount(page, base, account);
    if (!process.argv.includes('--resilience-only')) {
    for (const { slug, next } of chapters) {
        await load(slug);
        const publicQuestions = JSON.parse(await part('questions').textContent());
        assert.equal(publicQuestions.length, 5);
        assert.deepEqual(publicQuestions.map(q => q.type), ['multiple_choice', 'multiple_choice', 'multiple_choice', 'code_fill', 'code_fill']);
        publicQuestions.forEach(question => { noSecrets(question); assert.ok(Number.isInteger(question.question_id)); });
        assert.equal(await page.locator('[data-quiz-next-link]').getAttribute('href'), `${base}/materi/${next}`);
        await gate(false);
        await layout(`${slug}-form`);
        await page.evaluate(slug => localStorage.setItem('oopy.quiz.progress', JSON.stringify({ [slug]: { passed: true, bestCorrect: 5, bestScore: 100 } })), slug);
        await load(slug);
        await gate(false);
        assert.equal((await progress(slug)).best_score, 0);
        for (let i = 0; i < 5; i++) await part('next').click();
        assert.equal(await part('validation').textContent(), 'Masih ada soal yang belum dijawab.');
        assert.equal(await part('results').isVisible(), false);
        for (const correct of [0, 1, 2, 3, 4, 2, 5, 0]) {
            const unlocked = correct >= 4 || (await progress(slug)).next_unlocked;
            const finalResponse = page.waitForResponse(response => response.url().includes('/submit') && response.request().method() === 'POST');
            await answerQuiz(page, slug, Array.from({ length: correct }, (_, index) => index));
            const response = await finalResponse;
            assert.equal(response.status(), 200);
            const summary = await response.json();
            noSecrets(summary);
            await result(correct, unlocked);
            if ([3, 4].includes(correct)) await layout(`${slug}-${correct === 3 ? 'failed' : 'passed'}`);
            const body = response.request().postDataJSON();
            body.answers[0].answer = (body.answers[0].answer + 1) % 4;
            const duplicate = await page.request.post(response.url(), { data: body, headers: { 'X-CSRF-TOKEN': await page.locator('meta[name="csrf-token"]').getAttribute('content') } });
            assert.equal(duplicate.status(), 200);
            assert.deepEqual(await duplicate.json(), summary);
            await retry();
            await load(slug);
            await gate(unlocked);
        }
        assert.equal((await progress(slug)).best_score, 100);
        for (let i = 0; i < 3; i++) await part('next').click();
        const source = quizFixtures[slug][3].code;
        const blank = /_{3,}/.exec(source);
        assert.equal(await part('code-before').textContent(), source.slice(0, blank.index));
        assert.equal(await part('code-after').textContent(), source.slice(blank.index + blank[0].length));
        await part('code-fill').fill('   ');
        assert.equal(await part('answered').textContent(), '0 dari 5 soal dijawab');
        await part('code-fill').fill(quizFixtures[slug][3].answer);
        await layout(`${slug}-inline`);
        console.log(`PASS ${slug}: scores 0/20/40/60/80/100, no keys, validation, immutable replay, monotonic progress, retry/refresh, five responsive sizes`);
    }
    await logoutAccount(page, base);
    await loginAccount(page, base, account);
    await load(chapters[0].slug);
    await gate(true);
    const device = await browser.newContext({ viewport: { width: 390, height: 900 } });
    await cacheAssets(device);
    const devicePage = await device.newPage();
    await loginAccount(devicePage, base, account);
    await devicePage.goto(`${base}/materi/${chapters[0].slug}`);
    assert.equal(await devicePage.locator('[data-quiz-next-link]').isVisible(), true);
    await device.close();
    }
    await logoutAccount(page, base);
    await registerAccount(page, base);
    let releaseProgress;
    let captureProgress;
    const delayedProgress = new Promise(resolve => { releaseProgress = resolve; });
    const capturedProgress = new Promise(resolve => { captureProgress = resolve; });
    await page.route('**/kuis/progress', async route => {
        const snapshot = await route.fetch();
        captureProgress();
        await delayedProgress;
        await route.fulfill({ response: snapshot });
    });
    await page.goto(`${base}/materi/${chapters[0].slug}`);
    await ready();
    await capturedProgress;
    await gate(false);
    assert.equal((await progress(chapters[0].slug)).best_score, 0);

    const slug = chapters[0].slug;
    await page.route('**/kuis/attempts/*/submit', route => route.abort());
    for (const question of quizFixtures[slug]) {
        if (question.type === 'code_fill') await part('code-fill').fill(question.answer);
        else await part('options').locator('input').nth(question.correct).check();
        await part('next').click();
    }
    await page.waitForFunction(() => document.querySelector('[data-quiz="validation"]').textContent.includes('Koneksi gagal'));
    assert.equal(await part('results').isVisible(), false);
    assert.equal(await part('code-fill').inputValue(), quizFixtures[slug][4].answer);
    await gate(false);
    await page.unroute('**/kuis/attempts/*/submit');
    let release;
    const barrier = new Promise(resolve => { release = resolve; });
    let requests = 0;
    await page.route('**/kuis/attempts/*/submit', async route => { requests++; await barrier; await route.continue(); });
    await part('next').click();
    await page.waitForFunction(() => document.querySelector('[data-quiz="next"]').disabled);
    assert.equal(await part('next').textContent(), 'Menyimpan hasil…');
    assert.equal(await part('results').isVisible(), false);
    await part('form').evaluate(form => form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true })));
    release();
    await result(5);
    assert.equal(requests, 1);
    await retry();
    const staleResponse = page.waitForResponse(response => response.url().endsWith('/kuis/progress'));
    releaseProgress();
    await (await staleResponse).finished();
    await page.evaluate(() => new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(resolve))));
    await gate(true);
    await page.unroute('**/kuis/progress');
    console.log('PASS separate accounts, loading/double-submit, network failure/retry, delayed progress cannot revoke confirmed completion after Coba Lagi');
    await logoutAccount(page, base);
    await page.goto(`${base}/materi/${slug}`);
    assert.equal(await part('form').isVisible(), false);
    assert.equal(await page.getByRole('link', { name: 'Masuk untuk Mengerjakan Kuis' }).isVisible(), true);
    await gate(false);
    const guest = await page.request.post(`${base}/materi/${slug}/kuis/attempts`, { headers: { Accept: 'application/json', 'X-CSRF-TOKEN': await page.locator('meta[name="csrf-token"]').getAttribute('content') } });
    assert.equal(guest.status(), 401);
    assert.deepEqual(errors, []);
    console.log('PASS guest public reading, login CTA, authenticated endpoints, no JavaScript errors');
} finally {
    await browser.close();
}
