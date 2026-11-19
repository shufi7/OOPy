import assert from 'node:assert/strict';
import { spawn, execFileSync } from 'node:child_process';
import { randomBytes } from 'node:crypto';
import { mkdir, writeFile, readFile } from 'node:fs/promises';
import { resolve } from 'node:path';
import { chromium } from 'playwright';
import { cacheAssets, loginAccount, logoutAccount } from './quiz-helpers.mjs';

const run = `certification-${randomBytes(16).toString('hex')}`;
const base = process.env.OOPY_BASE_URL || 'http://127.0.0.1:8047';
assert.equal(new URL(base).hostname, '127.0.0.1', 'Only the disposable local server is accepted');
const directory = resolve('storage/framework/testing', run);
const env = { ...process.env, XDEBUG_MODE: 'off', OOPY_CERTIFICATION_RUN: run, OOPY_BASE_URL: base,
    OOPY_ASSET_CACHE: process.env.OOPY_ASSET_CACHE || resolve('storage/framework/testing/auth-verification') };
await mkdir(directory, { recursive: true });
execFileSync('php', ['tests/browser/certification-sandbox.php'], { env, windowsHide: true, stdio: 'pipe' });
const server = spawn('php', ['-S', new URL(base).host, '-t', 'public', 'tests/browser/certification-sandbox.php'], { env, windowsHide: true, stdio: 'pipe' });
let serverLog = '';
server.stderr.on('data', chunk => { serverLog += chunk.toString(); });
const results = [];
let browser;
const record = message => { results.push(`PASS ${message}`); console.log(`PASS ${message}`); };
try {
    let ready = false;
    for (let attempt = 0; attempt < 100; attempt++) {
        try { if ((await fetch(`${base}/sertifikasi`)).ok) { ready = true; break; } } catch {}
        await new Promise(resolve => setTimeout(resolve, 100));
    }
    assert.ok(ready, 'Sandbox server started');
    browser = await chromium.launch({ channel: process.env.OOPY_BROWSER || 'msedge', headless: true });
    const context = await browser.newContext({ viewport: { width: 1440, height: 1000 }, reducedMotion: 'reduce', acceptDownloads: true });
    process.env.OOPY_ASSET_CACHE = env.OOPY_ASSET_CACHE;
    await cacheAssets(context);
    const page = await context.newPage();
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    const visit = path => page.goto(`${base}${path}`, { waitUntil: 'networkidle' });
    const layouts = async name => {
        for (const width of [320, 360, 390, 768, 1024, 1440]) {
            await page.setViewportSize({ width, height: 1000 });
            assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1), `${name}: no overflow at ${width}`);
            await page.screenshot({ path: `${directory}/${name}-${width}.png`, fullPage: true });
        }
        record(`${name}: responsive at 320, 360, 390, 768, 1024, 1440`);
    };
    await visit('/sertifikasi');
    assert.ok(await page.getByRole('link', { name: 'Masuk untuk Ambil Sertifikat' }).isVisible());
    await layouts('guest');
    await page.setViewportSize({ width: 390, height: 1000 });
    await page.getByRole('button', { name: 'Buka menu navigasi' }).click();
    await page.locator('#navbarOOPy.show').waitFor();
    assert.equal(await page.locator('.site-navbar a[href$="/sertifikasi"]').count(), 0);
    await page.getByRole('link', { name: 'Beranda', exact: true }).click();
    record('mobile navbar remains usable without a certification menu');

    await page.setViewportSize({ width: 1440, height: 1000 });
    await loginAccount(page, base, { email: 'empty.certification@example.test', password: 'password123' });
    await visit('/dashboard');
    const dashboardAction = page.getByRole('link', { name: 'Ambil Sertifikat', exact: true });
    assert.equal(await page.locator('.oopy-dashboard-certification a').count(), 1);
    assert.equal(await page.locator('.oopy-dashboard-container').evaluate(node => node.lastElementChild.classList.contains('oopy-dashboard-certification')), true);
    assert.notEqual(await dashboardAction.evaluate(node => getComputedStyle(node).boxShadow), 'none');
    await dashboardAction.scrollIntoViewIfNeeded();
    await dashboardAction.hover();
    await page.mouse.down();
    assert.match(await dashboardAction.evaluate(node => getComputedStyle(node).transform), /,\s*3\)$/);
    await Promise.all([page.waitForURL(`${base}/sertifikasi`), page.mouse.up()]);
    record('Dashboard has one 3D press action at the bottom, opening the unified page');
    assert.equal(await page.getByRole('button', { name: 'Ambil Sertifikat', exact: true }).isDisabled(), true);
    assert.equal(await page.locator('[data-simulation-form]').count(), 0);
    assert.equal(await page.locator('.certification-receipt').count(), 0);
    assert.equal(await page.locator('.certification-missing').evaluate(node => node.open), false);
    await page.locator('.certification-missing summary').click();
    assert.equal(await page.locator('.certification-missing li').count(), 15);
    await page.locator('.certification-missing summary').click();
    await layouts('ineligible');
    await visit('/sertifikat-saya');
    assert.equal(page.url(), `${base}/sertifikasi#sertifikat`);
    await visit('/sertifikasi/checkout');
    assert.equal(new URL(page.url()).pathname, '/sertifikasi');
    await logoutAccount(page, base);
    record('unfinished requirements stay readable; old pages redirect and cannot expose a checkout form');

    await loginAccount(page, base, { email: 'eligible.certification@example.test', password: 'password123' });
    await visit('/dashboard');
    await page.getByRole('link', { name: 'Ambil Sertifikat', exact: true }).focus();
    await Promise.all([page.waitForURL(`${base}/sertifikasi`), page.keyboard.press('Enter')]);
    assert.equal(await page.locator('[data-simulation-form]').getAttribute('action'), `${base}/sertifikasi`);
    await layouts('eligible');
    const rejected = await context.request.post(`${base}/sertifikasi`, { form: { payment_method: 'card', simulation_confirmed: '1' } });
    assert.equal(rejected.status(), 419);
    record('inline form uses the canonical page; real CSRF rejects an unprotected POST');
    for (const method of ['e_wallet', 'virtual_account', 'qr_payment', 'card']) {
        await page.locator(`[name="payment_method"][value="${method}"]`).check();
    }
    const open = page.locator('[data-simulation-open]');
    await open.focus(); await page.keyboard.press('Enter');
    assert.equal(await page.locator('[data-simulation-dialog]').evaluate(node => node.open), true);
    await page.keyboard.press('Escape');
    assert.equal(await page.locator('[data-simulation-dialog]').evaluate(node => node.open), false);
    await open.click(); await page.locator('[data-simulation-cancel]').click(); await open.click();
    await page.locator('[data-simulation-submit]').click();
    assert.equal(await page.locator('[name="simulation_confirmed"]').evaluate(node => node.validity.valueMissing), true);
    await page.locator('[name="simulation_confirmed"]').check();
    await page.setViewportSize({ width: 320, height: 1000 });
    assert.ok(await page.locator('[data-simulation-dialog]').evaluate(node => node.scrollWidth <= node.clientWidth + 1));
    await page.screenshot({ path: `${directory}/confirmation-320.png`, fullPage: true });
    await Promise.all([page.waitForURL(`${base}/sertifikasi#sertifikat`), page.locator('[data-simulation-submit]').click()]);
    assert.equal(await page.locator('[data-simulation-form]').count(), 0);
    assert.ok(await page.getByRole('link', { name: 'Unduh Sertifikat PDF' }).isVisible());
    await layouts('issued');
    record('all methods, keyboard confirmation, required consent and issuance finish on the same page');
    await page.getByRole('button', { name: 'Salin tautan verifikasi' }).click();
    await page.locator('[data-copy-status]').filter({ hasText: /disalin|Salin tautan ini/ }).waitFor();
    for (const summary of await page.locator('.certification-guide summary').all()) {
        await summary.click(); assert.equal(await summary.evaluate(node => node.parentElement.open), true); await summary.click();
    }
    const verification = await page.getByRole('link', { name: 'Verifikasi sertifikat', exact: true }).getAttribute('href');
    const publicId = verification.split('/').at(-1);
    const qr = await page.locator('.certificate-preview-qr').getAttribute('src');
    await writeFile(`${directory}/verification-qr.png`, Buffer.from(qr.split(',')[1], 'base64'));
    const decoded = execFileSync('python', ['-c', 'import cv2,sys; print(cv2.QRCodeDetector().detectAndDecode(cv2.imread(sys.argv[1]))[0])', `${directory}/verification-qr.png`], { encoding: 'utf8', windowsHide: true }).trim();
    assert.equal(decoded, verification);
    record('copy action, disclosure guidance and locally decoded QR verification URL');
    for (const filename of ['certificate.pdf', 'certificate-redownload.pdf']) {
        const event = page.waitForEvent('download');
        await page.getByRole('link', { name: 'Unduh Sertifikat PDF' }).click();
        const file = await event;
        const failure = await file.failure();
        if (failure && process.env.OOPY_PDF_HTTP_FALLBACK === '1') {
            const response = await context.request.get(await page.getByRole('link', { name: 'Unduh Sertifikat PDF' }).getAttribute('href'));
            assert.equal(response.status(), 200);
            assert.equal(response.headers()['content-type'], 'application/pdf');
            assert.equal(response.headers()['cache-control'], 'no-store, private');
            const bytes = await response.body(); assert.equal(bytes.subarray(0, 5).toString(), '%PDF-');
            await writeFile(`${directory}/${filename}`, bytes);
            const limitation = 'NOT TESTED browser file save: browser canceled download; authenticated HTTP PDF validated';
            if (!results.includes(limitation)) { results.push(limitation); console.log(limitation); }
        } else { assert.equal(failure, null); await file.saveAs(`${directory}/${filename}`); }
    }
    execFileSync('php', ['tests/browser/certification-sandbox.php', 'pdf-evidence'], { env, windowsHide: true });
    execFileSync('python', ['tests/browser/certification-pdf-check.py', directory], { env: { ...env, PYTHONPATH: resolve('storage/framework/testing/certification-tools') }, windowsHide: true });
    const pdfEvidence = JSON.parse(await readFile(`${directory}/pdf-results.json`, 'utf8'));
    assert.equal(pdfEvidence.files.length, 4);
    record('four PDFs pass independent parser, A4 landscape, expected text, QR images and long-name layout');
    await page.getByRole('link', { name: 'Verifikasi sertifikat', exact: true }).click();
    assert.ok(await page.getByText('Valid sebagai sertifikat simulasi penyelesaian OOPy.').isVisible());
    await layouts('verification');
    await visit('/sertifikat/verifikasi/unknown');
    assert.ok(await page.getByRole('heading', { name: 'Sertifikat tidak ditemukan' }).isVisible());
    await visit('/sertifikasi');
    const token = await page.locator('meta[name="csrf-token"]').getAttribute('content');
    const replay = await context.request.post(`${base}/sertifikasi`, { form: { _token: token, payment_method: 'e_wallet', simulation_confirmed: '1' } });
    assert.equal(replay.status(), 200);
    await logoutAccount(page, base);
    await loginAccount(page, base, { email: 'empty.certification@example.test', password: 'password123' });
    assert.equal((await context.request.get(`${base}/sertifikasi/${publicId}/pdf`)).status(), 404);
    assert.equal((await context.request.get(`${base}/sertifikat-saya/${publicId}`)).status(), 404);
    record('replay stays on the unified page and another user cannot download the certificate');
    assert.deepEqual(errors, []);
    record('no browser JavaScript exceptions');
    const worker = () => new Promise((resolveWorker, reject) => {
        const child = spawn('php', ['tests/browser/certification-sandbox.php', 'issue'], { env, windowsHide: true, stdio: 'pipe' });
        let stdout = ''; let stderr = '';
        child.stdout.on('data', data => { stdout += data; }); child.stderr.on('data', data => { stderr += data; });
        child.on('error', reject); child.on('close', code => code === 0 ? resolveWorker(stdout.trim()) : reject(new Error(stderr || stdout)));
    });
    assert.equal(new Set(await Promise.all([worker(), worker(), worker(), worker()])).size, 1);
    record('four concurrent PHP workers return one certificate');
    await writeFile(`${directory}/results.json`, JSON.stringify({ run, results, verification, publicId }, null, 2));
    console.log(`Evidence: ${directory}`);
} finally {
    if (browser) await browser.close();
    server.kill();
    await writeFile(`${directory}/server.log`, serverLog);
}
