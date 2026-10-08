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
const next = () => part('next').click();
const choose = async (answer) => {
    if (await part('code-fill').isVisible()) await part('code-fill').fill(String(answer));
    else await options().nth(answer).check();
};
const ready = () => part('form').waitFor({ state: 'visible' });
const correctAnswer = (question) => question.type === 'code_fill' ? ` ${question.answer} ` : question.correct;
const wrongAnswer = (question) => question.type === 'code_fill' ? question.answer.toUpperCase() : (question.correct + 1) % question.options.length;
const answerAll = async (questions, correctIndices) => {
    for (const [index, question] of questions.entries()) {
        await choose(correctIndices.includes(index) ? correctAnswer(question) : wrongAnswer(question));
        await next();
    }
};
const assertNoFeedback = async () => {
    assert.equal(await quiz.locator('.oopy-quiz-review, [data-quiz="review"], .is-correct, .is-incorrect, .oopy-quiz-verdict').count(), 0);
    assert.doesNotMatch(await quiz.innerText(), /Pembahasan|Jawaban kamu:|Jawaban benar:|Jawaban yang benar|Penjelasan:/i);
};
const assertGate = async (unlocked, hasNext) => {
    const navigation = page.locator('.material-navigation');
    assert.equal(await navigation.getByRole('link', { name: 'Kembali ke Daftar Materi' }).isVisible(), true);
    if (!hasNext) {
        assert.equal(await navigation.locator('[rel="next"], [data-quiz-next-locked]').count(), 0);
        assert.equal(await part('continue').count(), 0);
        return;
    }
    assert.equal(await navigation.locator('[data-quiz-next-link]').isVisible(), unlocked);
    assert.equal(await navigation.locator('[data-quiz-next-locked]').isVisible(), !unlocked);
    assert.equal(await navigation.getByRole('link', { name: /Lanjut ke BAB/ }).count(), unlocked ? 1 : 0);
    if (!unlocked) assert.match(await navigation.innerText(), /terkunci.*minimal 4 dari 5 soal dengan benar/s);
};
const assertResult = async (correct, total, hasNext) => {
    const passed = correct >= 4;
    assert.equal(await part('results').isVisible(), true);
    assert.equal(await part('form').isVisible(), false);
    assert.equal(await part('score').textContent(), String(Math.round(correct / total * 100)));
    assert.equal(await part('correct').textContent(), String(correct));
    assert.equal(await part('incorrect').textContent(), String(total - correct));
    assert.equal(await part('status').textContent(), passed ? 'Lulus' : 'Belum Lulus');
    assert.equal(await part('result-title').evaluate((el) => el === document.activeElement), true);
    assert.equal(await part('results').locator('[role="status"][aria-atomic="true"]').count(), 1);
    if (hasNext) {
        assert.equal(await part('continue').isVisible(), passed);
        assert.equal(await part('continue').getAttribute('href'), await page.locator('[data-quiz-next-link]').getAttribute('href'));
        assert.match(await part('message').textContent(), passed ? /dapat melanjutkan/ : /minimal 4 dari 5 soal dengan benar/);
    } else {
        assert.doesNotMatch(await part('message').textContent(), /BAB berikutnya/);
        if (passed) assert.equal(await part('message').textContent(), 'Evaluasi selesai.');
    }
    await assertNoFeedback();
};
const retry = async (total) => {
    await part('retry').click();
    assert.equal(await part('results').isVisible(), false);
    assert.equal(await part('counter').textContent(), `Soal 1 dari ${total} soal`);
    assert.equal(await part('answered').textContent(), `0 dari ${total} soal dijawab`);
    assert.equal(await part('previous').isDisabled(), true);
    assert.equal(await part('options').locator('input:checked').count(), 0);
    assert.equal(await part('code-fill').inputValue(), '');
    assert.equal(await part('validation').textContent(), '');
    for (const role of ['score', 'correct', 'incorrect', 'status', 'message']) assert.equal(await part(role).textContent(), '');
};
const assertLayout = async (label) => {
    for (const width of [320, 390, 768, 1024, 1440]) {
        await page.setViewportSize({ width, height: 1000 });
        await quiz.evaluate((el) => el.scrollIntoView({ block: 'start', behavior: 'instant' }));
        assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1), `${label}: overflow at ${width}px`);
        assert.equal(await page.locator('.material-navigation').evaluate((el) => el.scrollWidth <= el.clientWidth + 1), true);
        for (const button of await quiz.locator('button:visible, a.btn:visible').all()) {
            assert.ok(await button.evaluate((el) => el.getBoundingClientRect().right <= innerWidth + 1), `${label}: button clipped at ${width}px`);
        }
        if (await part('code-fill').isVisible()) {
            assert.ok(await part('code-fill').evaluate((el) => Math.abs(el.getBoundingClientRect().width - el.parentElement.getBoundingClientRect().width) < 1));
        }
        if (process.env.OOPY_SCREENSHOT_DIR && [390, 1440].includes(width)) {
            await page.screenshot({ path: `${process.env.OOPY_SCREENSHOT_DIR}/${label}-${width}.png` });
        }
    }
};

const progressRecord = (slug) => page.evaluate((slug) => JSON.parse(localStorage.getItem('oopy.quiz.progress'))?.[slug] ?? null, slug);
const assertProgress = async (slug, bestCorrect) => {
    assert.deepEqual(await progressRecord(slug), { passed: bestCorrect >= 4, bestCorrect, bestScore: bestCorrect * 20 });
};

try {
    for (const [slug, hasNext] of [['dasar-pemrograman-oop', true], ['kelas-dan-objek', true], ['enkapsulasi', false]]) {
        await page.goto(`${base}/materi/${slug}`, { waitUntil: 'domcontentloaded' });
        await ready();
        const questions = JSON.parse(await part('questions').textContent());
        const total = questions.length;
        assert.equal(total, 5);
        assert.deepEqual(questions.map((question) => question.type), ['multiple_choice', 'multiple_choice', 'multiple_choice', 'code_fill', 'code_fill']);
        assert.equal(await quiz.getAttribute('data-chapter-slug'), slug);
        assert.equal(await quiz.evaluate((el) => el.previousElementSibling.id), 'refleksi');
        assert.equal(await progressRecord(slug), null);
        assert.ok(questions.every((question) => !Object.hasOwn(question, 'explanation')));
        assert.equal(await part('results').isVisible(), false);
        await assertGate(false, hasNext);
        await assertNoFeedback();
        await assertLayout(`${slug}-form`);
        const originalOutput = await page.locator('[data-role="code-output"]').first().textContent();

        await part('instructions-toggle').click();
        assert.equal(await part('instructions-toggle').getAttribute('aria-expanded'), 'true');
        assert.equal(await page.locator('#quiz-instructions').isVisible(), true);
        assert.doesNotMatch(await page.locator('#quiz-instructions').innerText(), /satu kata/);
        await part('instructions-toggle').click();
        assert.equal(await page.locator('#quiz-instructions').isVisible(), false);

        // Incomplete attempts neither compute a result nor save progress.
        for (let i = 0; i < total; i++) await next();
        assert.equal(await part('validation').textContent(), 'Masih ada soal yang belum dijawab.');
        assert.equal(await part('counter').textContent(), 'Soal 1 dari 5 soal');
        assert.equal(await part('results').isVisible(), false);
        await assertGate(false, hasNext);
        assert.equal(await progressRecord(slug), null);

        await page.locator('.material-toc a[href="#kuis"]').click();
        await page.waitForFunction(() => document.querySelector('.material-toc a[href="#kuis"]').getAttribute('aria-current') === 'location');
        // Keyboard selection, retained/changed answers, and no per-question verdict.
        await options().nth(0).focus();
        await page.keyboard.press('Space');
        await page.keyboard.press('ArrowDown');
        assert.equal(await options().nth(1).isChecked(), true);
        await next();
        assert.equal(await part('counter').evaluate((el) => el === document.activeElement), true);
        await part('previous').click();
        assert.equal(await options().nth(1).isChecked(), true);
        await choose(wrongAnswer(questions[0]));
        await assertNoFeedback();
        assert.equal(await part('results').isVisible(), false);
        await next();
        for (const question of questions.slice(1, 3)) { await choose(wrongAnswer(question)); await next(); }
        await assertLayout(`${slug}-code-fill`);
        assert.equal(await part('options-group').isVisible(), false);
        await choose('   ');
        assert.equal(await part('answered').textContent(), '3 dari 5 soal dijawab');
        await next();
        await choose(wrongAnswer(questions[4]));
        await next();
        assert.equal(await part('validation').textContent(), 'Masih ada soal yang belum dijawab.');
        assert.equal(await part('counter').textContent(), 'Soal 4 dari 5 soal');
        assert.equal(await part('results').isVisible(), false);
        await assertGate(false, hasNext);
        assert.equal(await progressRecord(slug), null);
        await choose(wrongAnswer(questions[3]));
        await part('previous').click();
        assert.equal(await part('code-fill').isDisabled(), true);
        await next();
        assert.equal(await part('code-fill').inputValue(), wrongAnswer(questions[3]));
        await part('code-fill').focus();
        await page.keyboard.press('Enter');
        assert.equal(await part('counter').textContent(), 'Soal 5 dari 5 soal');
        await next();

        // Case-sensitive code-fill and zero correct: failed, locked, best result 0.
        await assertResult(0, total, hasNext);
        await assertGate(false, hasNext);
        await assertProgress(slug, 0);
        await assertLayout(`${slug}-failed`);
        await page.reload({ waitUntil: 'domcontentloaded' });
        await ready();
        await assertGate(false, hasNext);

        // Every failing score, especially the 3/5 boundary, stays locked after refresh/retry.
        for (const correct of [1, 2, 3]) {
            await answerAll(questions, Array.from({ length: correct }, (_, index) => index));
            await assertResult(correct, total, hasNext);
            await assertGate(false, hasNext);
            await assertProgress(slug, correct);
            await retry(total);
            await assertGate(false, hasNext);
            await page.reload({ waitUntil: 'domcontentloaded' });
            await ready();
            assert.equal(await part('results').isVisible(), false);
            await assertGate(false, hasNext);
            await assertProgress(slug, correct);
        }

        // Four correct passes, including a trimmed code-fill answer in every chapter.
        await answerAll(questions, [0, 1, 2, 3]);
        await assertResult(4, total, hasNext);
        await assertGate(true, hasNext);
        await assertProgress(slug, 4);
        await assertLayout(`${slug}-passed`);
        await retry(total);
        await assertGate(true, hasNext);
        await assertProgress(slug, 4);
        await page.reload({ waitUntil: 'domcontentloaded' });
        await ready();
        assert.equal(await part('answered').textContent(), '0 dari 5 soal dijawab');
        assert.equal(await part('results').isVisible(), false);
        await assertGate(true, hasNext);

        // A lower score does not lower the best result or revoke an earlier pass.
        await answerAll(questions, [0, 1]);
        await assertResult(2, total, hasNext);
        await assertGate(true, hasNext);
        await assertProgress(slug, 4);
        await retry(total);

        await answerAll(questions, [0, 1, 2, 3, 4]);
        await assertResult(5, total, hasNext);
        await assertProgress(slug, 5);
        await retry(total);
        await assertGate(true, hasNext);

        await answerAll(questions, []);
        await assertResult(0, total, hasNext);
        await assertGate(true, hasNext);
        await assertProgress(slug, 5);
        await retry(total);
        assert.equal(await page.locator('[data-role="code-output"]').first().textContent(), originalOutput);
        assert.equal(await page.locator('.material-progress, .material-toc progress').count(), 0);
        console.log(`PASS: ${slug}: 5 questions (3 MC/2 code-fill), scores 0/20/40/60/80/100, 3 fails/4 passes, best results, retry/refresh, responsive form/results`);
    }
    const allProgress = await page.evaluate(() => JSON.parse(localStorage.getItem('oopy.quiz.progress')));
    assert.deepEqual(Object.keys(allProgress).sort(), ['dasar-pemrograman-oop', 'kelas-dan-objek', 'enkapsulasi'].sort());
    for (const slug of Object.keys(allProgress)) await assertProgress(slug, 5);

    const fresh = await browser.newContext();
    const freshPage = await fresh.newPage();
    await freshPage.goto(`${base}/materi/kelas-dan-objek`, { waitUntil: 'domcontentloaded' });
    await freshPage.locator('[data-quiz="form"]').waitFor({ state: 'visible' });
    assert.equal(await freshPage.locator('[data-quiz-next-locked]').isVisible(), true);
    assert.equal(await freshPage.locator('[data-quiz-next-link]').isVisible(), false);
    assert.equal(await freshPage.locator('.material-navigation [rel="prev"]').isVisible(), true);
    assert.equal(await freshPage.getByRole('link', { name: 'Kembali ke Daftar Materi' }).isVisible(), true);
    await fresh.close();

    // Corrupted progress fails closed, and a denied storage write still allows a session to pass.
    const malformed = await browser.newContext();
    const malformedPage = await malformed.newPage();
    const storageErrors = [];
    malformedPage.on('pageerror', (error) => storageErrors.push(error.message));
    await malformedPage.addInitScript(() => localStorage.setItem('oopy.quiz.progress', '{bad JSON'));
    await malformedPage.goto(`${base}/materi/kelas-dan-objek`, { waitUntil: 'domcontentloaded' });
    await malformedPage.locator('[data-quiz="form"]').waitFor({ state: 'visible' });
    assert.equal(await malformedPage.locator('[data-quiz-next-locked]').isVisible(), true);
    await malformedPage.evaluate(() => {
        Storage.prototype.setItem = () => { throw new Error('Storage disabled'); };
    });
    const storedQuestions = JSON.parse(await malformedPage.locator('[data-quiz="questions"]').textContent());
    for (const question of storedQuestions) {
        if (question.type === 'code_fill') await malformedPage.locator('[data-quiz="code-fill"]').fill(correctAnswer(question));
        else await malformedPage.locator('[data-quiz="options"] input').nth(question.correct).check();
        await malformedPage.locator('[data-quiz="next"]').click();
    }
    assert.equal(await malformedPage.locator('[data-quiz="status"]').textContent(), 'Lulus');
    assert.equal(await malformedPage.locator('[data-quiz-next-link]').isVisible(), true);
    assert.deepEqual(storageErrors, []);
    await malformed.close();

    // Passes under the retired one-correct rule must not unlock the new five-question quiz.
    const legacy = await browser.newContext();
    const legacyPage = await legacy.newPage();
    await legacyPage.addInitScript(() => {
        localStorage.setItem('oopy.quiz.passed.kelas-dan-objek', JSON.stringify({ passed: true }));
        localStorage.setItem('oopy.quiz.progress', JSON.stringify({ 'kelas-dan-objek': { passed: true, bestCorrect: 3, bestScore: 60 } }));
    });
    await legacyPage.goto(`${base}/materi/kelas-dan-objek`, { waitUntil: 'domcontentloaded' });
    await legacyPage.locator('[data-quiz="form"]').waitFor({ state: 'visible' });
    assert.equal(await legacyPage.locator('[data-quiz-next-link]').isVisible(), false);
    assert.equal(await legacyPage.locator('[data-quiz-next-locked]').isVisible(), true);
    await legacy.close();

    assert.deepEqual(writes, []);
    assert.deepEqual(errors, []);
    console.log('PASS: fresh/malformed/unavailable storage, no answer feedback, no server writes or Live Coding changes, no page errors');
} finally {
    await browser.close();
}
