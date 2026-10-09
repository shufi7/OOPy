import { execFileSync } from 'node:child_process';
import { randomUUID, randomBytes } from 'node:crypto';
import { fileURLToPath } from 'node:url';
import { basename, join } from 'node:path';

// Keys are read by the local test runner, never from HTTP/page JSON.
export const quizFixtures = JSON.parse(execFileSync('php', ['-d', 'display_errors=stderr', '-r', `
$quizzes = [];
foreach (require 'resources/materi/chapters.php' as $slug => $metadata) {
    $content = require 'resources/materi/'.$metadata['content'];
    if (isset($content['quiz'])) $quizzes[$slug] = $content['quiz'];
}
echo json_encode($quizzes, JSON_THROW_ON_ERROR);
`], { cwd: fileURLToPath(new URL('../../', import.meta.url)), encoding: 'utf8' }));

export async function cacheAssets(context) {
    const directory = process.env.OOPY_ASSET_CACHE;
    if (!directory) return;
    await context.route('https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css', route => route.fulfill({ path: join(directory, 'bootstrap.min.css'), contentType: 'text/css' }));
    await context.route('https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js', route => route.fulfill({ path: join(directory, 'bootstrap.bundle.min.js'), contentType: 'application/javascript' }));
    await context.route('https://fonts.googleapis.com/**', route => route.fulfill({ path: join(directory, 'font.css'), contentType: 'text/css' }));
    await context.route('https://fonts.gstatic.com/**', route => route.fulfill({ path: join(directory, basename(new URL(route.request().url()).pathname)), contentType: 'font/ttf' }));
}

export async function registerAccount(page, base) {
    const account = { email: `quiz-browser-${randomUUID()}@example.test`, password: randomBytes(18).toString('hex') };
    await page.goto(`${base}/register`);
    await page.locator('#name').fill('Pelajar Kuis Browser');
    await page.locator('#email').fill(account.email);
    await page.locator('#password').fill(account.password);
    await page.locator('#password_confirmation').fill(account.password);
    await Promise.all([page.waitForURL(`${base}/materi`), page.locator('.oopy-auth-form button[type="submit"]').click()]);
    return account;
}

export async function loginAccount(page, base, account) {
    await page.goto(`${base}/login`);
    await page.locator('#email').fill(account.email);
    await page.locator('#password').fill(account.password);
    await Promise.all([page.waitForURL(`${base}/materi`), page.locator('.oopy-auth-form button[type="submit"]').click()]);
}

export async function logoutAccount(page, base) {
    if (!await page.locator('#oopyAccountMenu').isVisible()) await page.getByRole('button', { name: 'Buka menu navigasi' }).click();
    await page.locator('#oopyAccountMenu').click();
    await Promise.all([page.waitForURL(`${base}/`), page.getByRole('button', { name: 'Logout', exact: true }).click()]);
}

export async function answerQuiz(page, slug, correctIndices = [0, 1, 2, 3, 4]) {
    for (const [index, question] of quizFixtures[slug].entries()) {
        const correct = correctIndices.includes(index);
        if (question.type === 'code_fill') {
            await page.locator('[data-quiz="code-fill"]').fill(correct ? ` ${question.answer} ` : question.answer.toUpperCase());
        } else {
            await page.locator('[data-quiz="options"] input').nth(correct ? question.correct : (question.correct + 1) % question.options.length).check();
        }
        await page.locator('[data-quiz="next"]').click();
    }
    await page.locator('[data-quiz="results"]').waitFor({ state: 'visible' });
}
