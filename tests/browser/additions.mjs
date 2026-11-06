// Run only against a fresh admin-sandbox.php fixture.
import { chromium } from 'playwright';
import assert from 'node:assert/strict';
import { mkdir, readFile, writeFile } from 'node:fs/promises';
import { join } from 'node:path';

assert.match(process.env.OOPY_ADMIN_TEST_DATABASE || '', /^admin-coding-[a-f0-9]{32}\.sqlite$/);
const base = process.env.OOPY_BASE_URL;
assert.match(base || '', /^http:\/\/127\.0\.0\.1:\d+$/);
const directory = process.env.OOPY_SCREENSHOT_DIR;
if (directory) await mkdir(directory, { recursive: true });
const browser = await chromium.launch({ channel: process.env.OOPY_BROWSER || 'msedge', headless: true });
const context = await browser.newContext({ viewport: { width: 1440, height: 1000 }, reducedMotion: 'reduce' });
const page = await context.newPage();
const results = { layouts: [], colors: [], interactions: [], errors: [], googleLive: 'NOT TESTED: OAuth credentials required' };
page.on('pageerror', error => results.errors.push(error.message));
const visit = async path => assert.equal((await page.goto(base + path)).status(), 200, path);
const record = message => { results.interactions.push(message); console.log(`PASS ${message}`); };
const screenshot = async name => { if (directory) await page.screenshot({ path: join(directory, name + '.png') }); };
const layout = async name => {
    const data = await page.evaluate(() => ({ width: innerWidth, scroll: document.documentElement.scrollWidth }));
    assert.ok(data.scroll <= data.width + 1, `${name}: ${JSON.stringify(data)}`);
    results.layouts.push({ name, ...data });
};
const luminance = rgb => rgb.match(/[\d.]+/g).slice(0, 3).map(Number)
    .map(channel => channel / 255).map(channel => channel <= .04045 ? channel / 12.92 : ((channel + .055) / 1.055) ** 2.4)
    .reduce((sum, channel, index) => sum + channel * [.2126, .7152, .0722][index], 0);
const contrast = async locator => {
    const colors = await locator.evaluate(el => ({ color: getComputedStyle(el).color, background: getComputedStyle(el).backgroundColor }));
    const values = [luminance(colors.color), luminance(colors.background)];
    const ratio = (Math.max(...values) + .05) / (Math.min(...values) + .05);
    assert.ok(ratio >= 4.5, JSON.stringify({ ...colors, ratio }));
    results.colors.push({ ...colors, ratio });
};
const login = async (target, email) => {
    await target.goto(base + '/login');
    await target.locator('#email').fill(email);
    await target.locator('#password').fill('password123');
    await Promise.all([target.waitForURL(url => url.pathname !== '/login'), target.locator('button[type="submit"]').click()]);
};
try {
    const before = process.env.OOPY_BEFORE_LAYOUT ? JSON.parse(await readFile(process.env.OOPY_BEFORE_LAYOUT, 'utf8')) : [];
    for (const width of [320, 390, 768, 1024, 1440, 1920]) {
        await page.setViewportSize({ width, height: 1000 });
        await visit('/materi/dasar-pemrograman-oop');
        await page.evaluate(() => document.fonts.ready);
        await layout('material');
        const dimensions = await page.evaluate(() => ({
            sidebar: document.querySelector('.material-sidebar').getBoundingClientRect().toJSON(),
            article: document.querySelector('.material-article').getBoundingClientRect().toJSON(),
        }));
        const original = before.find(row => row.width === width);
        if (width >= 1440) {
            assert.ok(dimensions.sidebar.x <= 24.5);
            if (original) {
                assert.ok(dimensions.sidebar.x < original.sidebar.x);
                assert.ok(dimensions.article.width > original.article.width);
            }
        }
        if ([390, 1440, 1920].includes(width)) await screenshot(`after-material-${width}`);
    }
    record('Material uses a wider article and left sidebar; six viewport widths have no page overflow');

    for (const width of [320, 390, 768, 1440]) {
        await page.setViewportSize({ width, height: 1000 });
        await visit('/login');
        await layout('login');
        await contrast(page.locator('.oopy-auth-google'));
        await screenshot(`after-login-${width}`);
    }
    await page.getByRole('link', { name: 'Masuk dengan Google', exact: true }).focus();
    await page.keyboard.press('Enter');
    await page.waitForURL(base + '/login');
    await page.getByRole('alert').filter({ hasText: 'Login Google belum tersedia' }).waitFor();
    assert.equal(await page.locator('#password').count(), 1);
    await screenshot('google-unconfigured');
    await visit('/register');
    assert.equal(await page.getByRole('link', { name: 'Masuk dengan Google', exact: true }).count(), 1);
    record('Google link works with keyboard; missing credentials show an error and retain password login');

    await login(page, 'admin.browser@example.test');
    await visit('/admin');
    const logout = page.locator('.admin-logout');
    assert.equal(await logout.evaluate(el => getComputedStyle(el).backgroundColor), 'rgb(160, 38, 38)');
    await contrast(logout);
    await logout.hover();
    await contrast(logout);
    await logout.focus();
    assert.notEqual(await logout.evaluate(el => getComputedStyle(el).outlineStyle), 'none');
    for (const section of ['users', 'questions']) {
        for (const width of [390, 768, 1024, 1440]) {
            await page.setViewportSize({ width, height: 1000 });
            await visit('/admin/' + section);
            await layout(section);
            await screenshot(`after-${section}-${width}`);
        }
    }
    record('Admin delete controls reflow and tables scroll locally; red Logout has visible focus and passing contrast');

    await visit('/admin/questions');
    await page.getByRole('link', { name: 'Hapus soal 1', exact: true }).click();
    await page.getByText('Soal aktif tidak dapat dihapus.', { exact: false }).waitFor();
    assert.equal(await page.getByRole('button', { name: 'Hapus soal permanen' }).count(), 0);
    await visit('/admin/questions/create?quiz_id=1');
    await page.locator('#question_type').selectOption('multiple_choice');
    await page.locator('#question_text').fill('Soal fixture untuk menguji Hapus');
    await page.locator('#options_text').fill('Pilihan A\nPilihan B');
    await page.locator('#correct_answer').fill('0');
    await page.locator('#display_order').fill('99');
    await Promise.all([page.waitForURL(/\/admin\/questions\/\d+$/), page.getByRole('button', { name: 'Simpan draft' }).click()]);
    const draftPath = new URL(page.url()).pathname;
    await page.getByRole('link', { name: 'Hapus soal', exact: true }).click();
    await page.getByRole('link', { name: 'Batal', exact: true }).click();
    await page.getByText('Soal fixture untuk menguji Hapus', { exact: true }).waitFor();
    await visit(draftPath + '/delete');
    await page.locator('#confirmation').check();
    await page.setViewportSize({ width: 390, height: 1000 });
    await layout('delete-question');
    await contrast(page.getByRole('button', { name: 'Hapus soal permanen' }));
    await screenshot('confirm-question');
    await Promise.all([page.waitForURL(/\/admin\/questions\?quiz_id=/), page.getByRole('button', { name: 'Hapus soal permanen' }).click()]);
    assert.equal((await context.request.get(base + draftPath)).status(), 404);
    record('Active questions remain protected; creating, cancelling and permanently deleting an unused draft works');

    const studentContext = await browser.newContext();
    const studentPage = await studentContext.newPage();
    await login(studentPage, 'student.browser@example.test');
    await visit('/admin/users');
    await page.getByRole('link', { name: 'Hapus pengguna student.browser@example.test', exact: true }).click();
    await page.locator('#email_confirmation').fill('wrong@example.test');
    await Promise.all([page.waitForNavigation(), page.getByRole('button', { name: 'Hapus pengguna permanen' }).click()]);
    await page.locator('#email-confirmation-error').waitFor();
    assert.equal((await context.request.get(base + '/admin/users/2')).status(), 200);
    await page.locator('#email_confirmation').fill('student.browser@example.test');
    await layout('delete-user');
    await screenshot('confirm-user');
    await Promise.all([page.waitForURL(base + '/admin/users'), page.getByRole('button', { name: 'Hapus pengguna permanen' }).click()]);
    assert.equal((await context.request.get(base + '/admin/users/2')).status(), 404);
    assert.equal((await context.request.get(base + '/admin/users/3')).status(), 200);
    assert.equal((await studentContext.request.get(base + '/dashboard', { maxRedirects: 0 })).status(), 302);
    await studentContext.close();
    record('User deletion requires the correct email, removes the selected account and denies its old session; other users survive');

    await page.setViewportSize({ width: 1440, height: 1000 });
    await page.locator('.admin-logout').focus();
    await page.keyboard.press('Enter');
    await page.waitForURL(base + '/');
    assert.equal((await context.request.get(base + '/admin', { maxRedirects: 0 })).status(), 302);
    record('Red Logout submits successfully with keyboard and revokes Admin access');
    assert.deepEqual(results.errors, []);
    results.status = 'PASS';
} catch (error) {
    results.status = 'FAIL';
    results.failure = error.stack;
    throw error;
} finally {
    if (directory) await writeFile(join(directory, 'results.json'), JSON.stringify(results, null, 2));
    await browser.close();
}
