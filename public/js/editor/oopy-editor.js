import { starterFiles } from './starter-project.js';

const MONACO_URL = 'https://cdn.jsdelivr.net/npm/monaco-editor@0.52.2/min/';
const EXECUTION_TIMEOUT = 10000;
const byId = (id) => document.getElementById(id);
const fileNames = Object.keys(starterFiles);
const models = new Map();
const viewStates = new Map();
const output = byId('code-output');
const runButton = byId('run-code');
const checkButton = byId('check-code');
const resetButton = byId('reset-code');
const stopButton = byId('stop-code');
let editor;
let activeFile = 'rawa.py';
let worker;
let workerReady = false;
let running = false;
let requestId = 0;
let executionTimer;
let loadingTimer;
let monacoWorkerUrl;

function updateButtons() {
    runButton.disabled = !editor || !workerReady || running;
    checkButton.disabled = !editor || !workerReady || running;
    resetButton.disabled = !editor || running;
    runButton.hidden = running;
    stopButton.hidden = !running;
    byId('clear-output').disabled = running;
    editor?.updateOptions({ readOnly: running });
}

function setStatus(state, message) {
    byId('python-status').dataset.state = state;
    byId('python-status-text').textContent = message;
}

function appendOutput(text, kind = '') {
    const span = document.createElement('span');
    span.className = kind ? `oopy-output-${kind}` : '';
    span.textContent = text;
    output.append(span);
    output.scrollTop = output.scrollHeight;
}

function setProgress(score) {
    byId('practice-percentage').textContent = `${score}%`;
    byId('practice-progress').setAttribute('aria-valuenow', String(score));
    byId('practice-progress-fill').style.width = `${score}%`;
}

function clearResults() {
    byId('check-results').hidden = true;
    byId('check-list').replaceChildren();
    byId('check-score').textContent = '';
    byId('check-summary').textContent = '';
    setProgress(0);
}

function selectFile(name, focusEditor = false) {
    if (!fileNames.includes(name)) return;
    if (editor && models.has(name)) {
        viewStates.set(activeFile, editor.saveViewState());
        editor.setModel(models.get(name));
        if (viewStates.has(name)) editor.restoreViewState(viewStates.get(name));
        if (focusEditor) editor.focus();
    }
    activeFile = name;
    document.querySelectorAll('[data-file]').forEach((button) => {
        const active = button.dataset.file === name;
        button.classList.toggle('is-active', active);
        if (button.getAttribute('role') === 'tab') {
            button.setAttribute('aria-selected', String(active));
            button.tabIndex = active ? 0 : -1;
        } else button.setAttribute('aria-pressed', String(active));
    });
    byId('editor-panel').setAttribute('aria-labelledby', `tab-${name.replace('.', '-')}`);
    byId('active-file-path').textContent = `workspace / ${name}`;
}

function finishExecution() {
    clearTimeout(executionTimer);
    running = false;
    setStatus('ready', 'Python + Pyodide Ready');
    updateButtons();
}

function failRuntime(message) {
    clearTimeout(executionTimer);
    clearTimeout(loadingTimer);
    worker?.terminate();
    workerReady = false;
    running = false;
    setStatus('error', 'Python belum tersedia');
    byId('retry-runtime').hidden = false;
    appendOutput(`\n${message}\nPeriksa koneksi internet, lalu klik Coba lagi.\n`, 'error');
    updateButtons();
}

function startWorker() {
    worker?.terminate();
    clearTimeout(loadingTimer);
    workerReady = false;
    byId('retry-runtime').hidden = true;
    setStatus('loading', 'Menyiapkan Python...');
    updateButtons();
    try {
        worker = new Worker(new URL('./python-worker.js', import.meta.url));
    } catch (error) {
        failRuntime(`Gagal memulai Python: ${error.message}`);
        return;
    }
    const currentWorker = worker;
    loadingTimer = setTimeout(() => failRuntime('Python terlalu lama dimuat.'), 90000);
    worker.onmessage = ({ data }) => {
        if (currentWorker !== worker) return;
        if (data.type === 'ready') {
            clearTimeout(loadingTimer);
            workerReady = true;
            setStatus('ready', 'Python + Pyodide Ready');
            byId('python-status').title = 'Python siap digunakan';
            updateButtons();
            return;
        }
        if (data.type === 'init-error') { failRuntime(data.message); return; }
        if (data.id !== requestId || !running) return;
        if (data.type === 'output') {
            data.chunks.forEach((chunk) => appendOutput(chunk.text, chunk.stream === 'stderr' ? 'error' : ''));
        } else if (data.type === 'results') {
            renderResults(data.results);
        } else if (data.type === 'done') {
            appendOutput('\n✓ Selesai.\n', 'success');
            finishExecution();
        } else if (data.type === 'error') {
            appendOutput(`\nPython Error\n${data.message}\n`, 'error');
            finishExecution();
        }
    };
    worker.onerror = (event) => {
        event.preventDefault();
        if (currentWorker === worker) failRuntime('Python gagal dimuat atau berhenti secara tidak terduga.');
    };
}

function stopExecution(message) {
    if (!running) return;
    clearTimeout(executionTimer);
    worker?.terminate();
    requestId += 1;
    running = false;
    appendOutput(`\n${message}\n`, 'error');
    startWorker();
}

function execute(type) {
    if (!editor || !workerReady || running) return;
    running = true;
    requestId += 1;
    clearResults();
    output.replaceChildren();
    appendOutput(type === 'check' ? '[Memeriksa jawaban dari 4 file ...]\n' : '[Menjalankan main.py ...]\n', 'muted');
    setStatus('busy', type === 'check' ? 'Memeriksa jawaban...' : 'Menjalankan program...');
    updateButtons();
    const files = Object.fromEntries([...models].map(([name, model]) => [name, model.getValue()]));
    executionTimer = setTimeout(() => stopExecution('Program dihentikan karena waktu eksekusi terlalu lama (maksimal 10 detik).'), EXECUTION_TIMEOUT);
    worker.postMessage({ type, id: requestId, files });
}

function renderResults(results) {
    const passed = results.filter((result) => result.passed).length;
    const score = Math.round(passed / results.length * 100);
    byId('check-score').textContent = `Skor: ${score}`;
    byId('check-summary').textContent = `${passed} dari ${results.length} pemeriksaan berhasil. ${score === 100 ? 'Konsep pewarisan dan polimorfisme sudah diterapkan dengan benar.' : 'Ikuti petunjuk di bawah, perbaiki kode, lalu submit kembali.'}`;
    const list = byId('check-list');
    list.replaceChildren();
    results.forEach((result) => {
        const item = document.createElement('li');
        if (!result.passed) item.classList.add('is-failed');
        const icon = document.createElement('i');
        icon.className = result.passed ? 'bi bi-check-circle-fill' : 'bi bi-x-circle-fill';
        icon.setAttribute('aria-hidden', 'true');
        const description = document.createElement('div');
        description.textContent = result.label;
        if (!result.passed) {
            const hint = document.createElement('small');
            hint.textContent = result.feedback;
            description.append(hint);
        }
        item.append(icon, description);
        list.append(item);
    });
    byId('check-results').hidden = false;
    setProgress(score);
    byId('check-results').scrollIntoView({ behavior: 'auto', block: 'nearest' });
}

async function startEditor() {
    try {
        await new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = `${MONACO_URL}vs/loader.js`;
            script.onload = resolve;
            script.onerror = () => reject(new Error('Monaco Editor tidak dapat diunduh. Periksa koneksi internet lalu muat ulang halaman.'));
            document.head.append(script);
        });
        monacoWorkerUrl = URL.createObjectURL(new Blob([
            `self.MonacoEnvironment = { baseUrl: '${MONACO_URL}' }; importScripts('${MONACO_URL}vs/base/worker/workerMain.js');`,
        ], { type: 'text/javascript' }));
        window.MonacoEnvironment = { getWorkerUrl: () => monacoWorkerUrl };
        window.require.config({ paths: { vs: `${MONACO_URL}vs` } });
        await new Promise((resolve, reject) => window.require(['vs/editor/editor.main'], resolve, reject));
        const monaco = window.monaco;
        monaco.editor.defineTheme('oopy-night', {
            base: 'vs-dark', inherit: true,
            rules: [
                { token: 'keyword', foreground: 'F185CE' },
                { token: 'string', foreground: 'E9D279' },
                { token: 'comment', foreground: '777990', fontStyle: 'italic' },
                { token: 'number', foreground: 'BEA0F2' },
                { token: 'identifier', foreground: 'CDD0E0' },
                { token: 'delimiter', foreground: 'A9AAC0' },
            ],
            colors: {
                'editor.background': '#1E1E2E', 'editor.foreground': '#CDD0E0',
                'editorLineNumber.foreground': '#696781', 'editorLineNumber.activeForeground': '#B3B0CC',
                'editor.lineHighlightBackground': '#232336', 'editor.selectionBackground': '#41415B',
                'editorCursor.foreground': '#B3CEFF', 'editorIndentGuide.background1': '#2B2B40',
                'editorWidget.background': '#242437', 'editorGutter.background': '#1E1E2E',
            },
        });
        fileNames.forEach((name) => {
            const model = monaco.editor.createModel(starterFiles[name], 'python', monaco.Uri.parse(`file:///workspace/${name}`));
            models.set(name, model);
            model.onDidChangeContent(() => {
                const dirty = model.getValue() !== starterFiles[name];
                document.querySelectorAll('[data-file]').forEach((button) => {
                    if (button.dataset.file === name) button.querySelector('.oopy-file-dirty').hidden = !dirty;
                });
                clearResults();
            });
        });
        editor = monaco.editor.create(byId('monaco-editor'), {
            model: models.get(activeFile), theme: 'oopy-night', automaticLayout: true,
            minimap: { enabled: false }, fontSize: 12, lineHeight: 22,
            fontFamily: "Consolas, 'Courier New', monospace", padding: { top: 16, bottom: 16 },
            lineNumbers: 'on', lineNumbersMinChars: 3, glyphMargin: false, folding: false,
            scrollBeyondLastLine: false, wordWrap: 'on', tabSize: 4, insertSpaces: true,
            autoIndent: 'full', matchBrackets: 'always', renderLineHighlight: 'none',
            overviewRulerLanes: 0, hideCursorInOverviewRuler: true, contextmenu: true,
            bracketPairColorization: { enabled: false },
            guides: { indentation: false }, scrollbar: { verticalScrollbarSize: 6, horizontalScrollbarSize: 6 },
            ariaLabel: 'Editor Python. Tekan Ctrl+Enter untuk menjalankan main.py.',
        });
        editor.addCommand(monaco.KeyMod.CtrlCmd | monaco.KeyCode.Enter, () => execute('run'));
        byId('editor-loading').hidden = true;
        updateButtons();
    } catch (error) {
        byId('editor-loading').textContent = error.message || 'Editor gagal dimuat. Periksa koneksi lalu muat ulang halaman.';
    }
}

document.querySelectorAll('[data-file]').forEach((button) => {
    button.addEventListener('click', () => selectFile(button.dataset.file));
    if (button.getAttribute('role') !== 'tab') return;
    button.addEventListener('keydown', (event) => {
        if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
        event.preventDefault();
        const index = fileNames.indexOf(button.dataset.file);
        const next = event.key === 'Home' ? 0 : event.key === 'End' ? fileNames.length - 1 : (index + (event.key === 'ArrowRight' ? 1 : -1) + fileNames.length) % fileNames.length;
        selectFile(fileNames[next]);
        byId(`tab-${fileNames[next].replace('.', '-')}`).focus();
    });
});

runButton.addEventListener('click', () => execute('run'));
checkButton.addEventListener('click', () => execute('check'));
stopButton.addEventListener('click', () => stopExecution('Program dihentikan. Kode kamu tetap tersimpan di editor.'));
byId('retry-runtime').addEventListener('click', startWorker);
byId('clear-output').addEventListener('click', () => output.replaceChildren());
resetButton.addEventListener('click', () => {
    const changed = [...models].some(([name, model]) => model.getValue() !== starterFiles[name]);
    if (changed && !window.confirm('Kembalikan semua file ke kode awal? Perubahan kode kamu akan dihapus.')) return;
    // Switch first so resetting the active model does not immediately cancel
    // the language work it just scheduled.
    selectFile('main.py');
    models.forEach((model, name) => model.setValue(starterFiles[name]));
    viewStates.clear();
    editor.setPosition({ lineNumber: 1, column: 1 });
    editor.revealLine(1);
    output.replaceChildren();
    clearResults();
});

const mobile = window.matchMedia('(max-width: 899.98px)');
const sidebar = byId('learning-sidebar');
function syncSidebar() {
    const visible = mobile.matches ? document.body.classList.contains('oopy-mobile-sidebar-open') : !document.body.classList.contains('oopy-sidebar-collapsed');
    byId('sidebar-open').setAttribute('aria-expanded', String(visible));
    byId('sidebar-close').setAttribute('aria-expanded', String(visible));
    byId('sidebar-backdrop').hidden = !(mobile.matches && visible);
    if (mobile.matches && visible) {
        sidebar.setAttribute('role', 'dialog');
        sidebar.setAttribute('aria-modal', 'true');
        document.querySelector('main').inert = true;
    } else {
        sidebar.removeAttribute('role');
        sidebar.removeAttribute('aria-modal');
        document.querySelector('main').inert = false;
    }
}
function closeSidebar() {
    if (mobile.matches) document.body.classList.remove('oopy-mobile-sidebar-open');
    else document.body.classList.add('oopy-sidebar-collapsed');
    syncSidebar();
    byId('sidebar-open').focus();
}
byId('sidebar-open').addEventListener('click', () => {
    document.body.classList.remove('oopy-sidebar-collapsed');
    if (mobile.matches) document.body.classList.add('oopy-mobile-sidebar-open');
    syncSidebar();
    byId('sidebar-close').focus();
});
byId('sidebar-close').addEventListener('click', closeSidebar);
byId('sidebar-backdrop').addEventListener('click', closeSidebar);
mobile.addEventListener('change', () => {
    document.body.classList.remove('oopy-mobile-sidebar-open', 'oopy-sidebar-collapsed');
    syncSidebar();
});
document.addEventListener('keydown', (event) => {
    if (!mobile.matches || !document.body.classList.contains('oopy-mobile-sidebar-open')) return;
    if (event.key === 'Escape') closeSidebar();
    if (event.key === 'Tab') {
        const items = [...sidebar.querySelectorAll('a, button, summary')].filter((el) => el.getClientRects().length);
        const first = items[0];
        const last = items.at(-1);
        if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
        else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
    }
});
syncSidebar();

const lessonTitles = { inheritance: 'Pengertian Pewarisan', syntax: 'Sintaks Pewarisan', overriding: 'Metode Overriding', exercise: 'Latihan Pewarisan & Polimorfisme' };
document.querySelectorAll('[data-lesson]').forEach((button) => button.addEventListener('click', () => {
    if (mobile.matches && document.body.classList.contains('oopy-mobile-sidebar-open')) closeSidebar();
    const lesson = button.dataset.lesson;
    byId('lesson-dialog-title').textContent = lessonTitles[lesson];
    byId('lesson-dialog-content').replaceChildren(byId(`lesson-${lesson}`).content.cloneNode(true));
    byId('lesson-dialog').showModal();
}));
byId('lesson-dialog-close').addEventListener('click', () => byId('lesson-dialog').close());
byId('show-results').addEventListener('click', () => {
    if (mobile.matches) closeSidebar();
    if (byId('check-results').hidden) {
        byId('check-summary').textContent = 'Klik Submit untuk memeriksa kode dan melihat hasil latihanmu.';
        byId('check-results').hidden = false;
    }
    byId('check-results').scrollIntoView({ block: 'center' });
    byId('check-results').focus({ preventScroll: true });
});

window.addEventListener('beforeunload', (event) => {
    if ([...models].some(([name, model]) => model.getValue() !== starterFiles[name])) {
        event.preventDefault();
        event.returnValue = '';
    }
});
window.addEventListener('pagehide', (event) => {
    if (event.persisted) return;
    clearTimeout(executionTimer);
    clearTimeout(loadingTimer);
    worker?.terminate();
    // The document owns Monaco's lifetime. Disposing models during pagehide can
    // reject pending language-worker requests while the page is navigating away.
    if (monacoWorkerUrl) URL.revokeObjectURL(monacoWorkerUrl);
});

startWorker();
startEditor();
