import assert from 'node:assert/strict';
import { spawn, execFileSync } from 'node:child_process';
import { randomBytes } from 'node:crypto';
import { mkdir, writeFile, readFile } from 'node:fs/promises';
import { resolve } from 'node:path';
import { chromium } from 'playwright';
import { cacheAssets, loginAccount } from './quiz-helpers.mjs';

const run = `midtrans-${randomBytes(16).toString('hex')}`;
const base = process.env.OOPY_BASE_URL || 'http://127.0.0.1:8048';
assert.equal(new URL(base).hostname, '127.0.0.1');
const directory = resolve('storage/framework/testing', run);
const env = { ...process.env, XDEBUG_MODE: 'off', OOPY_MIDTRANS_RUN: run, OOPY_BASE_URL: base };
const fixture = 'tests/browser/midtrans-sandbox.php';
await mkdir(directory, { recursive: true });
execFileSync('php', [fixture], { env, windowsHide: true, stdio: 'pipe' });
const server = spawn('php', ['-S', new URL(base).host, '-t', 'public', fixture], { env, windowsHide: true, stdio: 'pipe' });
let serverLog = '';
server.stderr.on('data', chunk => { serverLog += chunk; });
const results = [];
const record = message => { results.push(`PASS ${message}`); console.log(`PASS ${message}`); };
const provider = status => writeFile(`${directory}/provider-status.txt`, status);
let browser;
let page;
try {
    let ready = false;
    for (let i = 0; i < 100; i++) {
        try { if ((await fetch(`${base}/sertifikasi`)).ok) { ready = true; break; } } catch {}
        await new Promise(resolve => setTimeout(resolve, 100));
    }
    assert.ok(ready, 'Isolated server is ready');
    browser = await chromium.launch({ channel: process.env.OOPY_BROWSER || 'msedge', headless: true });
    const context = await browser.newContext({ viewport: { width: 1440, height: 1000 }, reducedMotion: 'reduce' });
    process.env.OOPY_ASSET_CACHE ||= resolve('storage/framework/testing/auth-verification');
    await cacheAssets(context);
    // The mocked SDK deliberately claims success before the provider is paid.
    await context.route('https://app.sandbox.midtrans.com/snap/snap.js', route => route.fulfill({ contentType: 'application/javascript', body:
        'window.snap={pay:(token,options)=>{window.__snap={token,options}; options.onSuccess({transaction_status:"settlement",status_code:"200"});}};' }));
    await context.route('https://app.sandbox.midtrans.com/snap/v2/vtweb/**', route => route.fulfill({ contentType: 'text/html', body: '<title>Mock Sandbox redirect</title><p>Isolated redirect fixture, no payment.</p>' }));
    page = await context.newPage();
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    const visit = path => page.goto(`${base}${path}`, { waitUntil: 'networkidle' });
    const layouts = async name => {
        await page.getByRole('tab', { name: 'Sertifikat', exact: true }).waitFor();
        const issued = name === 'issued' || name === 'issued-archived';
        for (const width of [320, 360, 390, 768, 1024, 1440]) {
            await page.setViewportSize({ width, height: issued && width < 640 ? 667 : 1000 });
            assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1), `${name}: overflow at ${width}`);
            if (issued) {
                const downloadIsNearTop = await page.evaluate(() => {
                    window.scrollTo(0, 0);
                    const panel = document.querySelector('#penerbitan');
                    const button = panel.querySelector('a[download]').getBoundingClientRect();
                    const receipt = panel.querySelector('.certification-receipt').getBoundingClientRect();
                    const overview = document.querySelector('.certification-overview').getBoundingClientRect();
                    return panel.getBoundingClientRect().bottom <= overview.top
                        && button.top >= 0 && button.bottom <= Math.min(receipt.top, innerHeight);
                });
                assert.ok(downloadIsNearTop, `issued: download is visible above the receipt at ${width}`);
                const documentPlacement = await page.evaluate(() => {
                    const preview = document.querySelector('#sertifikat').getBoundingClientRect();
                    const academics = document.querySelector('.certification-overview').getBoundingClientRect();
                    return innerWidth >= 992
                        ? preview.right <= academics.left + 1 && Math.abs(preview.top - academics.top) <= 1
                        : preview.bottom <= academics.top;
                });
                assert.ok(documentPlacement, `${name}: certificate and academic panel order at ${width}`);
                if (width < 640) {
                    const referenceFits = await page.locator('.certification-receipt .certificate-reference').evaluate(reference => {
                        const range = document.createRange();
                        range.selectNodeContents(reference);
                        return range.getClientRects().length === 1;
                    });
                    assert.ok(referenceFits, `${name}: certificate number fits on one line at ${width}`);
                }
            }
            if (await page.locator('#transcript-panel table').isVisible()) {
                const singleLine = await page.locator('#transcript-panel :is(.position, .score)').evaluateAll(cells => cells.every(cell => {
                    const range = document.createRange();
                    range.selectNodeContents(cell);
                    return range.getClientRects().length === 1;
                }));
                assert.ok(singleLine, `${name}: table numbers wrap at ${width}`);
            }
            await page.screenshot({ path: `${directory}/${name}-${width}.png`, fullPage: true });
        }
        record(`${name}: six viewport widths, no horizontal overflow`);
    };
    const login = kind => loginAccount(page, base, { email: `${kind}.midtrans@example.test`, password: 'password123' });
    await visit('/sertifikasi');
    assert.ok(await page.getByRole('link', { name: 'Masuk untuk Ambil Sertifikat' }).isVisible());
    await layouts('guest');
    await login('empty');
    await visit('/dashboard');
    assert.equal(await page.locator('.oopy-dashboard-certification a').count(), 1);
    assert.equal(await page.locator('.oopy-dashboard-container').evaluate(node => node.lastElementChild.classList.contains('oopy-dashboard-certification')), true);
    const dashboard = page.getByRole('link', { name: 'Ambil Sertifikat', exact: true });
    assert.notEqual(await dashboard.evaluate(node => getComputedStyle(node).boxShadow), 'none');
    await dashboard.focus();
    await Promise.all([page.waitForURL(`${base}/sertifikasi`), page.keyboard.press('Enter')]);
    assert.equal(await page.getByRole('button', { name: 'Ambil Sertifikat', exact: true }).isDisabled(), true);
    assert.equal(await page.locator('[data-midtrans-payment]').count(), 0);
    assert.equal(await page.locator('.site-navbar a[href$="/sertifikasi"]').count(), 0);
    await layouts('ineligible');
    await visit('/sertifikat-saya');
    assert.equal(page.url(), `${base}/sertifikasi#sertifikat`);
    record('Dashboard keyboard entry, one bottom 3D action, protected ineligible state and legacy redirect');
    await context.clearCookies();
    await login('eligible');
    await visit('/sertifikasi');
    assert.equal((await context.request.post(`${base}/sertifikasi/payments`, { data: { sandbox_confirmed: true } })).status(), 419);
    const open = page.locator('[data-payment-open]');
    assert.ok(await open.isDisabled());
    const csrf = await page.locator('meta[name="csrf-token"]').getAttribute('content');
    assert.equal((await context.request.post(`${base}/sertifikasi/payments`, {
        headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' }, data: { sandbox_confirmed: true },
    })).status(), 422);
    assert.equal(await page.locator('[data-participant-name]').textContent(), 'Peserta Sandbox Eligible');
    const edit = page.locator('[data-name-edit] summary');
    await edit.focus();
    await page.keyboard.press('Enter');
    await page.getByRole('button', { name: 'Tampilkan password' }).click();
    await page.locator('#certificate-current-password[type="text"]').waitFor();
    await page.getByRole('button', { name: 'Sembunyikan password' }).click();
    await page.locator('#certificate-current-password[type="password"]').waitFor();
    await page.locator('[data-cancel-name]').click();
    assert.equal(await page.locator('[data-name-edit]').evaluate(node => node.open), false);
    await edit.click();
    const participantName = 'Rara Dwi O’Malley-Santoso';
    await page.getByLabel('Nama lengkap baru').fill('  Rara  Dwi O’Malley-Santoso  ');
    await page.getByLabel('Password akun OOPy saat ini').fill('password-wrong');
    await page.getByRole('button', { name: 'Simpan Perubahan' }).click();
    await page.getByRole('alert').filter({ hasText: 'Password yang Anda masukkan salah' }).waitFor();
    assert.equal(await page.getByLabel('Password akun OOPy saat ini').inputValue(), '');
    await page.setViewportSize({ width: 320, height: 1000 });
    assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1));
    await page.screenshot({ path: `${directory}/name-error-320.png`, fullPage: true });
    await page.getByLabel('Password akun OOPy saat ini').fill('password123');
    await page.getByRole('button', { name: 'Simpan Perubahan' }).click();
    await page.getByText('Nama sertifikat berhasil diperbarui.', { exact: false }).waitFor();
    assert.equal(await page.locator('.certificate-preview-name').textContent(), participantName);
    assert.ok(await open.isDisabled());
    const certificateTab = page.getByRole('tab', { name: 'Sertifikat', exact: true });
    const transcriptTab = page.getByRole('tab', { name: 'Transkrip Nilai', exact: true });
    await certificateTab.focus();
    await page.keyboard.press('ArrowRight');
    assert.equal(await transcriptTab.getAttribute('aria-selected'), 'true');
    assert.equal(await page.locator('#transcript-panel tbody tr').count(), 9);
    assert.match(await page.locator('#transcript-panel').textContent(), /91,25[\s\S]+92,5[\s\S]+84,75/);
    await layouts('transcript-preview');
    await page.getByRole('link', { name: 'Lihat rincian nilai' }).click();
    assert.equal(await transcriptTab.getAttribute('aria-selected'), 'true');
    await certificateTab.click();
    await page.locator('#name-confirmed').check();
    await page.getByRole('button', { name: 'Konfirmasi Nama', exact: true }).click();
    await page.getByText('Nama sudah dikonfirmasi', { exact: true }).waitFor();
    assert.ok(await open.isEnabled());
    await layouts('eligible');
    record('account name, password error and recovery, confirmation gate, accessible transcript tabs and official values');
    await open.focus(); await page.keyboard.press('Enter');
    assert.ok(await page.locator('[data-payment-dialog]').evaluate(node => node.open));
    await page.keyboard.press('Escape');
    assert.equal(await page.locator('[data-payment-dialog]').evaluate(node => node.open), false);
    await open.click(); await page.locator('[data-payment-cancel]').click();
    assert.equal(await page.locator('[data-payment-dialog]').evaluate(node => node.open), false);
    await open.click(); await page.locator('[data-payment-confirm]').click();
    assert.match(await page.locator('[data-payment-dialog-message]').textContent(), /Centang/);
    await page.locator('[data-payment-consent]').check();
    await page.setViewportSize({ width: 320, height: 1000 });
    assert.ok(await page.locator('[data-payment-dialog]').evaluate(node => node.scrollWidth <= node.clientWidth + 1));
    await page.screenshot({ path: `${directory}/confirmation-320.png`, fullPage: true });
    await page.locator('[data-payment-confirm]').click();
    await page.waitForFunction(() => window.__snap && !document.querySelector('[data-payment-check]').disabled);
    assert.equal(await page.evaluate(() => window.__snap.options.uiMode), 'qr');
    assert.equal(await page.locator('[data-payment-label]').textContent(), 'Belum Dibayar');
    assert.equal(await page.getByRole('link', { name: 'Unduh Sertifikat PDF', exact: true }).count(), 0);
    assert.match(await page.locator('[data-payment-redirect]').getAttribute('href'), /^https:\/\/app\.sandbox\.midtrans\.com\/snap\//);
    const [redirectPage] = await Promise.all([context.waitForEvent('page'), page.locator('[data-payment-redirect]').click()]);
    await redirectPage.waitForLoadState();
    assert.equal(await redirectPage.title(), 'Mock Sandbox redirect');
    await redirectPage.close();
    const firstReference = await page.locator('[data-midtrans-payment]').getAttribute('data-status-url');
    await provider('pending'); await page.locator('[data-payment-check]').click();
    await page.getByText('Menunggu Pembayaran', { exact: true }).waitFor();
    assert.equal(await edit.isVisible(), false);
    assert.equal(await page.locator('[data-name-locked]').isVisible(), true);
    await open.click(); await page.locator('[data-payment-confirm]').click();
    await page.waitForFunction(() => !document.querySelector('[data-midtrans-payment]').hasAttribute('aria-busy'));
    assert.equal(await page.locator('[data-midtrans-payment]').getAttribute('data-status-url'), firstReference);
    await page.waitForFunction(() => !document.querySelector('[data-payment-check]').disabled);
    await layouts('pending');
    record('consent, CSRF, QR mode, validated redirect, reused pending order; forged SDK success cannot issue');
    for (const [status, label] of [['deny', 'Pembayaran Gagal'], ['expire', 'Pembayaran Kedaluwarsa']]) {
        await provider(status); await page.locator('[data-payment-check]').click();
        await page.getByText(label, { exact: true }).waitFor();
        assert.equal(await open.textContent(), 'Coba Lagi');
        assert.equal(await edit.isVisible(), true);
    }
    await open.click(); await page.locator('[data-payment-confirm]').click();
    await page.waitForFunction(old => document.querySelector('[data-midtrans-payment]').dataset.statusUrl !== old, firstReference);
    record('failed and expired payment states; safe retry retains old attempt and creates a new reference');
    await provider('settlement');
    await page.locator('[data-payment-check]').click();
    await page.getByRole('link', { name: 'Unduh Sertifikat PDF', exact: true }).waitFor();
    assert.equal(new URL(page.url()).hash, '#penerbitan');
    assert.equal(await page.locator('.certificate-preview-name').textContent(), participantName);
    await transcriptTab.click();
    assert.equal(await page.locator('.certificate-transcript-name').textContent(), participantName);
    await layouts('issued-transcript');
    await certificateTab.click();
    await layouts('issued');
    const download = await page.getByRole('link', { name: 'Unduh Sertifikat PDF', exact: true }).getAttribute('href');
    const verification = await page.getByRole('link', { name: 'Verifikasi sertifikat', exact: true }).getAttribute('href');
    await page.getByRole('button', { name: 'Salin tautan verifikasi' }).click();
    await page.locator('[data-copy-status]').filter({ hasText: /disalin|Salin tautan ini/ }).waitFor();
    for (const summary of await page.locator('.certification-guide summary').all()) {
        await summary.click(); assert.ok(await summary.evaluate(node => node.parentElement.open)); await summary.click();
    }
    try {
        const [downloadEvent] = await Promise.all([page.waitForEvent('download', { timeout: 8000 }), page.getByRole('link', { name: 'Unduh Sertifikat PDF', exact: true }).click()]);
        await downloadEvent.saveAs(`${directory}/certificate-browser.pdf`);
        record('browser file download saved successfully');
    } catch {
        const limitation = 'NOT TESTED browser file save: environment canceled/blocked the download; authenticated HTTP PDF and parser checks run separately';
        results.push(limitation); console.log(limitation);
    }
    const response = await context.request.get(download);
    assert.equal(response.status(), 200); assert.match(response.headers()['content-type'], /application\/pdf/);
    assert.equal((await response.body()).subarray(0, 5).toString(), '%PDF-');
    await writeFile(`${directory}/certificate-http.pdf`, await response.body());
    const qr = await page.locator('.certificate-preview-qr').getAttribute('src');
    await writeFile(`${directory}/qr.png`, Buffer.from(qr.split(',')[1], 'base64'));
    execFileSync('php', [fixture, 'pdf-evidence'], { env, windowsHide: true, stdio: 'pipe' });
    await visit(verification.replace(base, ''));
    assert.ok(await page.getByRole('heading', { name: 'Sertifikat Sandbox OOPy' }).isVisible());
    assert.ok(await page.getByText(participantName, { exact: true }).isVisible());
    assert.equal((await page.textContent('body')).includes('eligible.midtrans@example.test'), false);
    assert.equal((await page.textContent('body')).includes('91,25'), false);
    await page.getByRole('link', { name: 'Kembali ke Sertifikasi', exact: true }).click();
    assert.equal(new URL(page.url()).pathname, '/sertifikasi');
    record('verified settlement issues one Sandbox PDF; private HTTP download, embedded QR and public verification work');
    await visit('/sertifikasi');
    const correction = page.locator('[data-issued-name-edit]');
    await correction.locator('summary').click();
    const correctionPassword = correction.getByLabel('Password akun OOPy', { exact: true });
    assert.equal(await correction.getByLabel('Nama pada sertifikat', { exact: true }).inputValue(), participantName);
    await correction.getByRole('button', { name: 'Tampilkan password' }).click();
    assert.equal(await correctionPassword.getAttribute('type'), 'text');
    await correction.getByRole('button', { name: 'Batal' }).click();
    assert.equal(await correction.evaluate(element => element.open), false);
    await correction.locator('summary').click();
    const correctedName = 'Rara Áyu O’Malley';
    await correction.getByLabel('Nama pada sertifikat', { exact: true }).fill(correctedName);
    await correctionPassword.fill('password-wrong');
    await correction.getByRole('checkbox').check();
    await correction.getByRole('button', { name: 'Simpan Nama Sertifikat' }).click();
    await correction.getByRole('alert').filter({ hasText: 'Password yang Anda masukkan salah' }).waitFor();
    assert.equal(await correctionPassword.inputValue(), '');
    await page.setViewportSize({ width: 390, height: 780 });
    assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1));
    await page.screenshot({ path: `${directory}/issued-name-form-390.png`, fullPage: true });
    await correctionPassword.fill('password123');
    await correction.getByRole('checkbox').check();
    await correction.getByRole('button', { name: 'Simpan Nama Sertifikat' }).click();
    await page.getByText('Koreksi nama tersimpan.', { exact: false }).waitFor();
    assert.equal(await page.locator('.certificate-preview-name').textContent(), correctedName);
    await transcriptTab.click();
    assert.equal(await page.locator('.certificate-transcript-name').textContent(), correctedName);
    await certificateTab.click();
    assert.equal(await page.locator('.certificate-preview-qr').getAttribute('src'), qr);
    const correctedPdf = await context.request.get(download);
    assert.equal(correctedPdf.status(), 200);
    await writeFile(`${directory}/certificate-corrected.pdf`, await correctedPdf.body());
    await visit(verification.replace(base, ''));
    assert.ok(await page.getByText(correctedName, { exact: true }).isVisible());
    assert.ok(await page.getByText('Nama pada sertifikat ini telah dikoreksi.', { exact: false }).isVisible());
    record('issued name correction requires password and updates PDF, transcript and public verification with unchanged QR');
    execFileSync('php', [fixture, 'simulate-archived-certificate'], { env, windowsHide: true, stdio: 'pipe' });
    await visit('/sertifikasi');
    assert.equal(await page.getByRole('heading', { name: 'Ringkasan nilai pembelajaran' }).count(), 0);
    await transcriptTab.click();
    await page.getByText('Transkrip belum disimpan saat sertifikat ini diterbitkan.').waitFor();
    await certificateTab.click();
    await layouts('issued-archived');
    await transcriptTab.click();
    await page.getByRole('button', { name: 'Lengkapi Transkrip Nilai' }).click();
    await page.locator('#transcript-panel').filter({ hasText: 'setelah sertifikat diterbitkan' }).waitFor({ state: 'visible' });
    assert.equal(await page.locator('#transcript-panel tbody tr').count(), 9);
    assert.equal(await page.getByRole('button', { name: 'Lengkapi Transkrip Nilai' }).count(), 0);
    record('archived Sandbox certificate shows a dated transcript after owner capture');
    await provider('refund'); await page.locator('[data-payment-check]').click();
    await page.getByRole('heading', { name: 'Sertifikat tidak aktif' }).waitFor();
    assert.equal((await context.request.get(download)).status(), 409);
    record('verified refund revokes download and updates the unified page');
    await context.clearCookies(); await login('legacy'); await visit('/sertifikasi');
    await page.locator('summary').filter({ hasText: 'Sertifikat simulasi lama' }).click();
    const legacy = await page.getByRole('link', { name: 'Unduh sertifikat simulasi lama' }).getAttribute('href');
    const legacyPdf = await context.request.get(legacy);
    assert.equal(legacyPdf.status(), 200);
    await writeFile(`${directory}/certificate-legacy.pdf`, await legacyPdf.body());
    assert.ok(await page.getByRole('button', { name: 'Bayar dengan QRIS', exact: true }).isVisible());
    assert.equal((await context.request.get(download)).status(), 404);
    record('legacy simulation remains downloadable and does not masquerade as Sandbox payment; other-owner PDF denied');
    execFileSync('php', [fixture, 'prepare-race'], { env, windowsHide: true, stdio: 'pipe' });
    await provider('settlement');
    await Promise.all(Array.from({ length: 4 }, () => new Promise((resolve, reject) => {
        const worker = spawn('php', [fixture, 'sync-race'], { env, windowsHide: true, stdio: 'pipe' });
        let output = ''; worker.stderr.on('data', chunk => { output += chunk; });
        worker.on('error', reject); worker.on('exit', code => code === 0 ? resolve() : reject(new Error(output || `Worker failed: ${code}`)));
    })));
    const report = JSON.parse(execFileSync('php', [fixture, 'report'], { env, windowsHide: true, encoding: 'utf8' }));
    assert.equal(report.race_certificates, 1); assert.equal(report.race_orders, 1);
    assert.deepEqual(errors, []);
    record('four concurrent verification workers produce exactly one order and certificate; no browser JavaScript errors');
    await writeFile(`${directory}/verification-url.txt`, verification);
    await writeFile(`${directory}/results.json`, JSON.stringify({ run, results, provider: 'HTTP fake and mocked Snap SDK; no live merchant or QR payment' }, null, 2));
    console.log(`Evidence: ${directory}`);
} catch (error) {
    if (page) {
        await writeFile(`${directory}/failure.html`, await page.content());
        await page.screenshot({ path: `${directory}/failure.png`, fullPage: true });
        console.error(`Browser failure at ${page.url()}; evidence ${directory}`);
    }
    throw error;
} finally {
    await browser?.close(); server.kill();
    await writeFile(`${directory}/server.log`, serverLog);
}
