import { chromium } from 'playwright';
import assert from 'node:assert/strict';

const base = process.env.OOPY_BASE_URL || 'http://127.0.0.1:8017';
const browser = await chromium.launch({ channel: process.env.OOPY_BROWSER || 'msedge', headless: true });
const page = await browser.newPage({ viewport: { width: 1440, height: 1000 } });
const errors = [];
page.on('pageerror', (error) => errors.push(error.message));
page.on('dialog', (dialog) => dialog.accept());
const chapterPath = '/materi/dasar-pemrograman-oop';
const waitReady = () => page.waitForFunction(() => [...document.querySelectorAll('[data-role="run-code"]')].every((button) => !button.disabled), null, { timeout: 110000 });
const inspectInstructions = async () => {
    for (const exercise of await page.locator('[data-live-code]').all()) {
        const config = JSON.parse(await exercise.locator('[data-role="config"]').textContent());
        const header = exercise.locator('.oopy-activity-header');
        assert.equal(await header.count(), 1);
        assert.equal(await header.locator('.oopy-activity-title').textContent(), config.title);
        assert.equal(await header.locator('.oopy-live-task-description').textContent(), config.description.replace(/`([^`\r\n]+)`/g, '$1'));
        assert.equal(await header.locator('.oopy-live-task').count(), 1);
        assert.ok(await header.locator('.oopy-live-task code').count() >= 2);
        assert.equal(await header.locator('ol').count(), 0);
        assert.equal(await header.locator('[tabindex]').count(), 0);
        assert.equal(await exercise.evaluate((el) => el.querySelector('.oopy-activity-header').nextElementSibling.className), 'oopy-workspace-body');
    }
};
const inspectInstructionLayout = async (width) => {
    for (const header of await page.locator('.oopy-activity-header').all()) {
        assert.equal(await header.evaluate((el) => {
            const title = el.querySelector('.oopy-activity-title').getBoundingClientRect();
            const task = el.querySelector('.oopy-live-task').getBoundingClientRect();
            const bounds = el.getBoundingClientRect();
            return title.bottom <= task.top && task.bottom <= bounds.bottom;
        }), true, `Instruction groups overlap at ${width}px`);
        if (process.env.OOPY_SCREENSHOT_DIR && [390, 1440].includes(width)) {
            const id = await header.evaluate((el) => el.closest('[data-live-code]').id);
            await header.screenshot({ path: `${process.env.OOPY_SCREENSHOT_DIR}/instructions-${id}-${width}.png` });
        }
    }
};

try {
    await page.addInitScript(() => {
        const NativeWorker = window.Worker;
        window.pythonWorkerCount = 0;
        window.Worker = class extends NativeWorker {
            constructor(url, options) {
                super(url, options);
                if (String(url).includes('/python-worker.js')) window.pythonWorkerCount++;
            }
        };
    });
    await page.goto(base, { waitUntil: 'domcontentloaded' });
    await page.getByRole('link', { name: /Mulai Belajar/i }).click();
    await page.waitForURL(`${base}/materi`);
    assert.equal(await page.locator('.materi-card').count(), 6);
    assert.equal(await page.locator('.materi-card a').count(), 3);
    assert.equal(await page.getByText('Segera hadir', { exact: true }).count(), 3);
    await page.getByRole('link', { name: /Pelajari BAB 1/ }).click();
    await page.waitForURL(`${base}${chapterPath}`);
    await waitReady();
    assert.equal(await page.locator('[data-material-section]').count(), 12);
    assert.equal(await page.locator('[data-live-code]').count(), 1);
    assert.equal(await page.locator('.material-toc nav a').count(), 12);
    await inspectInstructions();
    assert.ok((await page.locator('#bab1-status-air .oopy-live-task code').allTextContents()).includes('status_air(tinggi)'));
    const sectionIds = ['tujuan', 'apersepsi', 'nilai-tipe-data-variabel', 'operator-ekspresi', 'input-output', 'percabangan', 'perulangan-list', 'fungsi', 'prosedural-ke-oop', 'rangkuman', 'refleksi', 'kuis'];
    assert.deepEqual(await page.locator('[data-material-section]').evaluateAll((nodes) => nodes.map((node) => node.id)), sectionIds);
    assert.deepEqual(await page.locator('.material-toc nav a').evaluateAll((nodes) => nodes.map((node) => node.hash.slice(1))), sectionIds);
    assert.equal(await page.locator('.material-table').count(), 3);
    assert.equal(await page.locator('.material-practice li').count(), 3);
    assert.equal(await page.locator('#apersepsi .material-code').count(), 0);
    assert.match(await page.locator('#nilai-tipe-data-variabel .material-output').textContent(), /<class 'str'>/);
    assert.equal(await page.evaluate(() => window.pythonWorkerCount), 1);
    assert.equal(await page.evaluate(() => window.monaco.editor.getModels().length), 1);

    for (const width of [320, 390, 768, 1024, 1440]) {
        await page.setViewportSize({ width, height: 1000 });
        await page.waitForFunction((width) => document.querySelector('.material-toc').open === (width >= 992), width);
        assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1), `Page overflow at ${width}`);
        await inspectInstructionLayout(width);
        assert.ok(await page.locator('.material-table-wrapper').evaluateAll((nodes) => nodes.every((node) => node.getBoundingClientRect().right <= innerWidth + 1)));
        assert.ok(await page.locator('#nilai-tipe-data-variabel tbody td:first-child').evaluateAll((nodes) => nodes.every((node) => {
            const range = document.createRange();
            range.selectNodeContents(node);
            return range.getClientRects().length === 1;
        })), `Data type names split across lines at ${width}px`);
        if (width < 992) await page.locator('.material-toc summary').click();
        await page.locator('.material-toc a[href="#fungsi"]').click();
        await page.waitForFunction(() => document.querySelector('.material-toc a[href="#fungsi"]').getAttribute('aria-current') === 'location');
        assert.equal(await page.evaluate(() => document.activeElement.id), 'fungsi');
        assert.equal(await page.locator('.material-toc').evaluate((el) => el.open), width >= 992);
        assert.ok(await page.locator('#fungsi').evaluate((el) => el.getBoundingClientRect().top >= 0), `Anchor hidden at ${width}px`);
        if (width >= 992) {
            const overlap = await page.evaluate(() => {
                const sidebar = document.querySelector('.material-sidebar').getBoundingClientRect();
                const article = document.querySelector('.material-article').getBoundingClientRect();
                return sidebar.right > article.left;
            });
            assert.equal(overlap, false);
        }
    }
    console.log('PASS: new BAB 1 module, tables/output/practice/reflection, matching sidebar; 320/390/768/1024/1440px layout, focus and anchors');

    // A keyboard user can reach the horizontally scrollable code example.
    await page.locator('#fungsi .material-code:not(.material-output) pre').focus();
    assert.equal(await page.locator('#fungsi .material-code:not(.material-output) pre').evaluate((el) => el === document.activeElement), true);
    const role = (name) => page.locator(`#bab1-status-air [data-role="${name}"]`);
    const chapterOneStarter = await page.evaluate(() => window.monaco.editor.getModel(window.monaco.Uri.parse('file:///workspaces/bab1-status-air/main.py')).getValue());
    const editChapterOne = (source) => page.evaluate((source) => window.monaco.editor.getModel(window.monaco.Uri.parse('file:///workspaces/bab1-status-air/main.py')).setValue(source), source);
    const submitChapterOne = async () => { await role('check-code').click(); await waitReady(); };
    await role('run-code').click();
    await waitReady();
    assert.match(await role('code-output').textContent(), /None/);
    await submitChapterOne();
    assert.equal(await role('practice-percentage').textContent(), '13%');
    assert.equal(await role('check-list').locator('li').count(), 8);
    assert.equal(await role('check-list').locator('.is-failed').count(), 7);
    for (const incorrect of [
        'def status_air(tinggi):\n    if tinggi >= 100:\n        return "Dipantau"\n    elif tinggi >= 150:\n        return "Waspada"\n    return "Normal"',
        'def status_air(tinggi):\n    if tinggi > 150:\n        return "Waspada"\n    elif tinggi > 100:\n        return "Dipantau"\n    return "Normal"',
    ]) {
        await editChapterOne(incorrect);
        await submitChapterOne();
        assert.equal(await role('practice-percentage').textContent(), '75%');
        assert.equal(await role('check-list').locator('.is-failed').count(), 2);
    }
    await editChapterOne('def status_air(tinggi):\n    if tinggi >= 150:\n        return "Waspada"\n    elif tinggi >= 100:\n        return "Dipantau"\n    return "Normal"\n\nfor tinggi in [80, 120, 170]:\n    print(status_air(tinggi))');
    await role('run-code').click();
    await waitReady();
    assert.match(await role('code-output').textContent(), /Normal\s+Dipantau\s+Waspada/);
    await submitChapterOne();
    assert.equal(await role('practice-percentage').textContent(), '100%');
    // A different implementation with the same behavior must also be accepted.
    await editChapterOne('def status_air(tinggi):\n    if tinggi < 100:\n        return "Normal"\n    if tinggi < 150:\n        return "Dipantau"\n    return "Waspada"');
    await submitChapterOne();
    assert.equal(await role('practice-percentage').textContent(), '100%');
    assert.equal(await page.locator('.material-progress progress').getAttribute('value'), '0');
    assert.equal(await page.locator('#kuis [data-quiz="form"]').isVisible(), true);
    await role('reset-code').click();
    assert.equal(await page.evaluate(() => window.monaco.editor.getModel(window.monaco.Uri.parse('file:///workspaces/bab1-status-air/main.py')).getValue()), chapterOneStarter);
    assert.equal(await role('practice-percentage').textContent(), '0%');
    console.log('PASS: BAB 1 starter incomplete; eight behavior/boundary checks reject reordered/strict comparisons; both correct implementations 100%; Reset restores starter and 0%');

    // Direct anchors and no-JS reading work independently of Monaco/Pyodide.
    const mobile = await browser.newPage({ viewport: { width: 390, height: 900 } });
    await mobile.goto(`${base}${chapterPath}#nilai-tipe-data-variabel`, { waitUntil: 'domcontentloaded' });
    await mobile.waitForFunction(() => !document.querySelector('.material-toc').open);
    await mobile.waitForFunction(() => Math.abs(document.querySelector('#nilai-tipe-data-variabel').getBoundingClientRect().top - 24) < 5);
    await mobile.close();
    const noJs = await browser.newPage({ javaScriptEnabled: false, viewport: { width: 390, height: 900 } });
    await noJs.goto(`${base}${chapterPath}`, { waitUntil: 'domcontentloaded' });
    assert.equal(await noJs.locator('#kuis').count(), 1);
    await noJs.locator('.material-toc summary').click();
    assert.equal(await noJs.locator('.material-toc').evaluate((el) => el.open), false);
    await noJs.locator('.material-toc summary').click();
    await noJs.locator('.material-toc a[href="#kuis"]').click();
    assert.match(noJs.url(), /#kuis$/);
    await noJs.close();
    assert.deepEqual(errors, []);
    console.log('PASS: direct mobile anchors, native no-JS navigation; no page errors');

    assert.equal(await page.locator('.material-bottom-nav').count(), 0);
    assert.equal(await page.locator('.material-navigation').count(), 1);
    assert.equal(await page.locator('.material-toc a[href="#refleksi"]').count(), 1);
    await page.locator('.material-navigation a[rel="next"]').click();
    await page.waitForURL(`${base}/materi/kelas-dan-objek`);
    await waitReady();
    assert.equal(await page.locator('.material-navigation').count(), 1);
    assert.equal(await page.locator('.material-navigation a[rel="next"]').getAttribute('href'), `${base}/materi/enkapsulasi`);
    assert.equal(await page.locator('[data-live-code]').count(), 2);
    await inspectInstructions();
    assert.equal(await page.evaluate(() => window.pythonWorkerCount), 1);
    assert.equal(await page.locator('script[src*="vs/loader.js"]').count(), 1);
    assert.equal(await page.locator('script[src$="js/live-code/live-code.js"]').count(), 1);
    assert.equal(await page.locator('link[href$="css/oopy/live-code/live-code.css"]').count(), 1);
    const ids = await page.locator('[id]').evaluateAll((elements) => elements.map((el) => el.id));
    assert.equal(new Set(ids).size, ids.length);
    assert.ok(await page.locator('.material-code .token.keyword').count() > 0);
    for (const width of [320, 390, 768, 1024, 1440]) {
        await page.setViewportSize({ width, height: 1000 });
        await page.waitForFunction((width) => document.querySelector('.material-toc').open === (width >= 992), width);
        if (width < 992) await page.locator('.material-toc summary').click();
        await page.locator('.material-toc a[href="#refleksi"]').click();
        await page.waitForFunction(() => document.querySelector('.material-toc a[href="#refleksi"]').getAttribute('aria-current') === 'location');
        assert.equal(await page.evaluate(() => document.activeElement.id), 'refleksi');
        assert.equal(await page.locator('.material-toc [aria-current="location"]').count(), 1);
        assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1), `BAB 2 overflow at ${width}px`);
        await inspectInstructionLayout(width);
        const editorsFit = await page.locator('.oopy-monaco-editor').evaluateAll((elements) => elements.every((el) => el.getBoundingClientRect().right <= innerWidth + 1));
        assert.equal(editorsFit, true);
    }
    await page.emulateMedia({ reducedMotion: 'reduce' });
    assert.equal(await page.locator('.material-nav-button').evaluateAll((buttons) => buttons.every((el) => getComputedStyle(el).transitionDuration === '0s')), true);
    await page.keyboard.press('Tab');
    await page.locator('.material-navigation a[rel="prev"]').focus();
    assert.notEqual(await page.locator('.material-navigation a[rel="prev"]').evaluate((el) => getComputedStyle(el).outlineStyle), 'none');

    for (const id of ['bab2-spesies', 'bab2-sensor-air']) {
        const exercise = page.locator(`#${id}`);
        const part = (name) => exercise.locator(`[data-role="${name}"]`);
        await part('check-code').click();
        await waitReady();
        assert.equal(await part('practice-percentage').textContent(), '0%');
        assert.match(await part('code-output').textContent(), /Error/);
        const solution = id === 'bab2-spesies' ? `class Spesies:
    def __init__(self, nama, habitat):
        self.nama = nama
        self.habitat = habitat
    def deskripsi(self):
        return f"{self.nama} hidup di {self.habitat}"
spesies1 = Spesies("Bekantan", "Hutan riparian")
spesies2 = Spesies("Ikan lokal", "Perairan rawa")
print(spesies1.deskripsi())
print(spesies2.deskripsi())` : `class SensorAir:
    def __init__(self, lokasi, tinggi_air):
        self.lokasi = lokasi
        self.tinggi_air = tinggi_air
    def tampilkan(self):
        return f"{self.lokasi}: {self.tinggi_air} cm"
sensor = [SensorAir("Sungai Barito", 120), SensorAir("Rawa Bangkau", 85), SensorAir("Pesisir", 60)]
for objek in sensor:
    print(objek.tampilkan())`;
        const edit = (source) => page.evaluate(({ id, source }) => {
            window.monaco.editor.getModel(window.monaco.Uri.parse(`file:///workspaces/${id}/main.py`)).setValue(source);
        }, { id, source });
        await edit(solution.replace(id === 'bab2-spesies' ? 'self.habitat = habitat' : 'self.tinggi_air = tinggi_air', id === 'bab2-spesies' ? 'self.habitat = "Salah"' : 'self.tinggi_air = 0'));
        await part('check-code').click();
        await waitReady();
        assert.equal(await part('practice-percentage').textContent(), '0%');
        assert.equal(await part('check-list').locator('.is-failed').count(), 1);
        await edit(solution);
        await part('run-code').click();
        await waitReady();
        assert.match(await part('code-output').textContent(), id === 'bab2-spesies' ? /Bekantan.*Hutan riparian/s : /Sungai Barito.*120/s);
        await part('check-code').click();
        await waitReady();
        assert.equal(await part('practice-percentage').textContent(), '100%');
        await part('reset-code').click();
        assert.equal(await part('practice-percentage').textContent(), '0%');
    }
    assert.equal(await page.locator('.material-progress progress').getAttribute('value'), '0');
    assert.deepEqual(errors, []);
    await page.locator('.material-navigation a[rel="next"]').click();
    await page.waitForURL(`${base}/materi/enkapsulasi`);
    await waitReady();
    assert.equal(await page.locator('.material-navigation a[rel="next"]').count(), 0);
    assert.equal(await page.locator('.material-navigation a[rel="prev"]').getAttribute('href'), `${base}/materi/kelas-dan-objek`);
    assert.equal(await page.locator('[data-material-section]').count(), 13);
    assert.deepEqual(await page.locator('.material-toc nav a').evaluateAll((links) => links.map((link) => link.hash.slice(1))),
        await page.locator('[data-material-section]').evaluateAll((sections) => sections.map((section) => section.id)));
    await inspectInstructions();
    assert.equal(await page.evaluate(() => window.pythonWorkerCount), 1);
    assert.equal(await page.evaluate(() => window.monaco.editor.getModels().length), 1);
    assert.equal(await page.locator('script[src*="vs/loader.js"]').count(), 1);
    assert.equal(await page.locator('script[src$="js/live-code/live-code.js"]').count(), 1);
    assert.equal(await page.locator('link[href$="css/oopy/live-code/live-code.css"]').count(), 1);
    const chapterThreeIds = await page.locator('[id]').evaluateAll((elements) => elements.map((el) => el.id));
    assert.equal(new Set(chapterThreeIds).size, chapterThreeIds.length);
    assert.equal(await page.locator('#refleksi li').count(), 3);
    assert.equal(await page.locator('#bab3-enkapsulasi-sensor [data-role="workspace-title"]').evaluate((el) => el.tagName), 'H3');
    for (const width of [320, 390, 768, 1024, 1440]) {
        await page.setViewportSize({ width, height: 1000 });
        await page.waitForFunction((width) => document.querySelector('.material-toc').open === (width >= 992), width);
        if (width < 992) await page.locator('.material-toc summary').click();
        await page.locator('.material-toc a[href="#aktivitas-enkapsulasi"]').click();
        await page.waitForFunction(() => document.querySelector('.material-toc a[href="#aktivitas-enkapsulasi"]').getAttribute('aria-current') === 'location');
        assert.equal(await page.evaluate(() => document.activeElement.id), 'aktivitas-enkapsulasi');
        assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1), `BAB 3 overflow at ${width}px`);
        await inspectInstructionLayout(width);
        assert.equal(await page.locator('.oopy-monaco-editor').evaluate((el) => {
            const rect = el.getBoundingClientRect();
            return rect.left >= 0 && rect.right <= innerWidth + 1;
        }), true, `BAB 3 editor outside viewport at ${width}px`);
        assert.equal(await page.locator('.material-sidebar').evaluate((el) => el.getBoundingClientRect().right <= innerWidth + 1), true);
        assert.equal(await page.locator('.material-navigation').evaluate((el) => el.scrollWidth <= el.clientWidth + 1), true);
    }
    const exercise = page.locator('#bab3-enkapsulasi-sensor');
    const part = (name) => exercise.locator(`[data-role="${name}"]`);
    const starter = JSON.parse(await part('config').textContent()).files['main.py'];
    const editSensor = (source) => page.evaluate((source) => {
        window.monaco.editor.getModel(window.monaco.Uri.parse('file:///workspaces/bab3-enkapsulasi-sensor/main.py')).setValue(source);
    }, source);
    const submitSensor = async () => { await part('check-code').click(); await waitReady(); };
    await submitSensor();
    assert.notEqual(await part('practice-percentage').textContent(), '100%');
    assert.ok(await part('check-list').locator('.is-failed').count() > 0);
    const solution = `class SensorAir:
    def __init__(self, lokasi, tinggi_air):
        self.lokasi = lokasi
        self.tinggi_air = tinggi_air
    @property
    def tinggi_air(self):
        return self.__tinggi_air
    @tinggi_air.setter
    def tinggi_air(self, nilai):
        if nilai < 0:
            raise ValueError("Tinggi air tidak boleh negatif.")
        self.__tinggi_air = nilai

alat = SensorAir("Rawa Bangkau", 85)
print(alat.tinggi_air)
alat.tinggi_air = 90
print(alat.tinggi_air)`;
    await editSensor(solution.replace('if nilai < 0:', 'if False:'));
    await submitSensor();
    assert.notEqual(await part('practice-percentage').textContent(), '100%');
    assert.match((await part('check-list').locator('.is-failed').allTextContents()).join('\n'), /Validasi nilai negatif.*Data setelah penolakan/s);
    // Raising after storing must also fail: the last valid value must survive.
    await editSensor(solution.replace('if nilai < 0:', 'self.__tinggi_air = nilai\n        if nilai < 0:'));
    await submitSensor();
    assert.notEqual(await part('practice-percentage').textContent(), '100%');
    assert.match(await part('check-list').locator('.is-failed').textContent(), /Data setelah penolakan/);
    await editSensor(solution);
    await part('run-code').click();
    await waitReady();
    assert.match(await part('code-output').textContent(), /85\s+90/);
    await submitSensor();
    assert.equal(await part('practice-percentage').textContent(), '100%');
    assert.equal(await part('check-list').locator('li').count(), 9);
    assert.equal(await part('check-list').locator('.is-failed').count(), 0);
    assert.equal(await page.locator('.material-progress progress').getAttribute('value'), '0');
    await part('reset-code').click();
    assert.equal(await part('practice-percentage').textContent(), '0%');
    assert.equal(await part('check-results').isVisible(), false);
    assert.equal(await page.evaluate(() => window.monaco.editor.getModels()[0].getValue()), starter);
    await page.keyboard.press('Tab');
    await page.locator('.material-navigation a[rel="prev"]').focus();
    assert.notEqual(await page.locator('.material-navigation a[rel="prev"]').evaluate((el) => getComputedStyle(el).outlineStyle), 'none');
    assert.equal(await page.locator('.material-nav-button').evaluate((el) => getComputedStyle(el).transitionDuration), '0s');
    const noJsThree = await browser.newPage({ javaScriptEnabled: false, viewport: { width: 320, height: 900 } });
    await noJsThree.goto(`${base}/materi/enkapsulasi`, { waitUntil: 'domcontentloaded' });
    assert.equal(await noJsThree.locator('[data-material-section]').count(), 13);
    await noJsThree.locator('.material-toc a[href="#property"]').click();
    assert.match(noJsThree.url(), /#property$/);
    await noJsThree.close();
    assert.deepEqual(errors, []);
    console.log('PASS: BAB 3 navigation, sidebar/sections, responsive layout at 320/390/768/1024/1440px, headings/focus/reduced motion, no-JS reading; nine behavior checks reject starter/negative setter/corrupted state, correct solution 100%, Reset restores starter and 0%; one worker/loader and no JS errors');
    await page.locator('.material-navigation a[rel="prev"]').click();
    await page.waitForURL(`${base}/materi/kelas-dan-objek`);
    await page.locator('.material-navigation a[rel="prev"]').click();
    await page.waitForURL(`${base}${chapterPath}`);
    console.log('PASS: BAB 1/2 navigation; conditional reflection; instruction titles/tasks/tokens and responsive hierarchy; BAB 2 layout at 320/390/768/1024/1440px; unique IDs, Prism, focus/reduced motion, one worker/loader; incomplete/wrong/correct/reset exercises');

    if (process.env.OOPY_SCREENSHOT_DIR) {
        await page.evaluate(() => scrollTo(0, 0));
        await page.screenshot({ path: `${process.env.OOPY_SCREENSHOT_DIR}/material-desktop.png` });
        await page.setViewportSize({ width: 390, height: 900 });
        await page.evaluate(() => scrollTo(0, 0));
        await page.screenshot({ path: `${process.env.OOPY_SCREENSHOT_DIR}/material-mobile.png` });
    }
} finally {
    await browser.close();
}
