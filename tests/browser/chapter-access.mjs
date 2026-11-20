import { chromium } from 'playwright';
import assert from 'node:assert/strict';
import { mkdir } from 'node:fs/promises';
import { join } from 'node:path';
import { answerQuiz, cacheAssets, quizFixtures, registerAccount } from './quiz-helpers.mjs';

assert.match(process.env.OOPY_ADMIN_TEST_DATABASE || '', /^admin-coding-[a-f0-9]{32}\.sqlite$/);
const base = process.env.OOPY_BASE_URL;
assert.match(base || '', /^http:\/\/127\.0\.0\.1:\d+$/);
const directory = process.env.OOPY_SCREENSHOT_DIR;
if (directory) await mkdir(directory, { recursive: true });
const browser = await chromium.launch({ channel: process.env.OOPY_BROWSER || 'msedge', headless: true });
const context = await browser.newContext({ viewport: { width: 1440, height: 1000 }, reducedMotion: 'reduce' });
await cacheAssets(context);
const page = await context.newPage();
const errors = [];
page.on('pageerror', error => errors.push(error.message));
const first = 'dasar-pemrograman-oop';
const second = 'kelas-dan-objek';
const visit = path => page.goto(base + path, { waitUntil: 'domcontentloaded' });
const checkCards = async (unlocked, label) => {
    assert.equal(await page.locator('.materi-card a').count(), unlocked);
    assert.equal(await page.locator('.materi-card button:disabled').count(), 7 - unlocked);
    for (const width of [360, 390, 768, 1440]) {
        await page.setViewportSize({ width, height: 1000 });
        await page.evaluate(() => new Promise(resolve => requestAnimationFrame(resolve)));
        assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1), `${label}: overflow at ${width}`);
        if (directory && [390, 1440].includes(width)) {
            await page.screenshot({ path: join(directory, `${label}-${width}.png`), fullPage: true });
        }
    }
};

try {
    await visit('/materi');
    await checkCards(1, 'guest');
    await visit(`/materi/${second}`);
    assert.equal(page.url(), base + '/materi');
    assert.match(await page.getByRole('alert').innerText(), /Selesaikan kuis BAB 1/);
    await registerAccount(page, base);
    await checkCards(1, 'new-account');
    await page.locator(`#bab-1 a`).focus();
    assert.notEqual(await page.locator('#bab-1 a').evaluate(el => getComputedStyle(el).outlineStyle), 'none');
    await Promise.all([page.waitForURL(`${base}/materi/${first}`), page.keyboard.press('Enter')]);
    await page.locator('[data-quiz="loading"]').waitFor({ state: 'hidden' });
    const keys = quizFixtures[first];
    await answerQuiz(page, first, keys.map((_, index) => index));
    assert.equal(await page.locator('[data-quiz-next-link]').isVisible(), true);
    await visit('/materi');
    await checkCards(2, 'first-completed');
    assert.match(await page.locator('#bab-1').innerText(), /Selesai/);
    await page.locator('#bab-2 a').click();
    await page.waitForURL(`${base}/materi/${second}`);
    await visit('/materi/enkapsulasi');
    assert.equal(page.url(), base + '/materi');
    assert.match(await page.getByRole('alert').innerText(), /Selesaikan kuis BAB 2/);
    await page.reload({ waitUntil: 'domcontentloaded' });
    await checkCards(2, 'progress-after-reload');
    assert.deepEqual(errors, []);
    console.log('PASS: locked cards, direct URLs, keyboard navigation, quiz completion, persisted progress and four viewport sizes.');
} finally {
    await browser.close();
}
