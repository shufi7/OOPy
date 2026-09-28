import { loadMonaco } from './monaco-loader.js';
import { runtimeManager } from './runtime-manager.js';

export function createLiveCode(root, config) {
    const byId = (role) => root.querySelector(`[data-role="${role}"]`);
    const starterFiles = Object.freeze({ ...config.files });
    const fileNames = Object.keys(starterFiles);
    const models = new Map();
    const viewStates = new Map();
    const output = byId('code-output');
    const runButton = byId('run-code');
    const checkButton = byId('check-code');
    const resetButton = byId('reset-code');
    const stopButton = byId('stop-code');
    let editor;
    let activeFile = config.entry_file;
    let running = false;

    function updateButtons() {
        runButton.disabled = !editor || runtimeManager.state !== 'ready' || running;
        checkButton.disabled = !editor || runtimeManager.state !== 'ready' || running || !config.checker.trim();
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
        root.querySelectorAll('[data-file]').forEach((button) => {
            const active = button.dataset.file === name;
            button.classList.toggle('is-active', active);
            if (button.getAttribute('role') === 'tab') {
                button.setAttribute('aria-selected', String(active));
                button.tabIndex = active ? 0 : -1;
            } else button.setAttribute('aria-pressed', String(active));
        });
        byId('editor-panel').setAttribute('aria-labelledby', `${config.id}-tab-${fileNames.indexOf(name)}`);
        byId('active-file-path').textContent = `workspace / ${name}`;
    }

    function showRuntimeState() {
        const state = runtimeManager.state;
        byId('retry-runtime').hidden = state !== 'error';
        if (!running) setStatus(state, state === 'ready' ? 'Python + Pyodide Ready' : state === 'error' ? 'Python gagal dimuat. Periksa koneksi, lalu Coba lagi.' : 'Menyiapkan Python...');
        updateButtons();
    }

    function execute(type) {
        if (!editor || runtimeManager.state !== 'ready' || running) return;
        if (type === 'check' && !config.checker.trim()) return;
        running = true;
        clearResults();
        output.replaceChildren();
        appendOutput(type === 'check' ? `[Memeriksa ${fileNames.length} file ...]\n` : `[Menjalankan ${config.entry_file} ...]\n`, 'muted');
        updateButtons();
        runtimeManager.submit(config.id, {
            type, files: Object.fromEntries([...models].map(([name, model]) => [name, model.getValue()])),
            entryFile: config.entry_file, checker: config.checker,
        }, (data) => {
            if (data.type === 'queued') setStatus('busy', 'Menunggu antrean Python...');
            else if (data.type === 'started') setStatus('busy', type === 'check' ? 'Memeriksa jawaban...' : 'Menjalankan program...');
            else if (data.type === 'output') data.chunks.forEach((chunk) => appendOutput(chunk.text, chunk.stream === 'stderr' ? 'error' : ''));
            else if (data.type === 'results') renderResults(data.results);
            else if (['done', 'error', 'cancelled'].includes(data.type)) {
                appendOutput(data.type === 'done' ? '\n✓ Selesai.\n' : `\n${data.message}\n`, data.type === 'done' ? 'success' : 'error');
                running = false;
                showRuntimeState();
            }
        });
    }

    function renderResults(results) {
        const passed = results.filter((result) => result.passed).length;
        const score = results.length ? Math.round(passed / results.length * 100) : 0;
        byId('check-score').textContent = `Skor: ${score}`;
        byId('check-summary').textContent = `${passed} dari ${results.length} pemeriksaan berhasil. ${score === 100 ? 'Semua pemeriksaan latihan berhasil.' : 'Ikuti petunjuk di bawah, perbaiki kode, lalu submit kembali.'}`;
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
            const monaco = await loadMonaco();
            fileNames.forEach((name) => {
                const model = monaco.editor.createModel(starterFiles[name], 'python', monaco.Uri.parse(`file:///workspaces/${config.id}/${name}`));
                models.set(name, model);
                model.onDidChangeContent(() => {
                    const dirty = model.getValue() !== starterFiles[name];
                    root.querySelectorAll('[data-file]').forEach((button) => {
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
                ariaLabel: `${config.title}. Tekan Ctrl+Enter untuk menjalankan ${config.entry_file}.`,
            });
            editor.addAction({
                id: `oopy-run-${config.id}`,
                label: 'Run Code',
                keybindings: [monaco.KeyMod.CtrlCmd | monaco.KeyCode.Enter],
                precondition: 'editorTextFocus',
                run: () => execute('run'),
            });
            byId('editor-loading').hidden = true;
            updateButtons();
        } catch (error) {
            byId('editor-loading').textContent = error.message || 'Editor gagal dimuat. Periksa koneksi lalu muat ulang halaman.';
        }
    }

    root.querySelectorAll('[data-file]').forEach((button) => {
        button.addEventListener('click', () => selectFile(button.dataset.file));
        if (button.getAttribute('role') !== 'tab') return;
        button.addEventListener('keydown', (event) => {
            if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
            event.preventDefault();
            const index = fileNames.indexOf(button.dataset.file);
            const next = event.key === 'Home' ? 0 : event.key === 'End' ? fileNames.length - 1 : (index + (event.key === 'ArrowRight' ? 1 : -1) + fileNames.length) % fileNames.length;
            selectFile(fileNames[next]);
            byId(`tab-${next}`).focus();
        });
    });

    runButton.addEventListener('click', () => execute('run'));
    checkButton.addEventListener('click', () => execute('check'));
    stopButton.addEventListener('click', () => runtimeManager.cancel(config.id));
    byId('retry-runtime').addEventListener('click', () => runtimeManager.start());
    byId('clear-output').addEventListener('click', () => output.replaceChildren());
    resetButton.addEventListener('click', () => {
        const changed = [...models].some(([name, model]) => model.getValue() !== starterFiles[name]);
        if (changed && !window.confirm('Kembalikan semua file ke kode awal? Perubahan kode kamu akan dihapus.')) return;
        // Switch first so resetting the active model does not immediately cancel
        // the language work it just scheduled.
        selectFile(config.entry_file);
        models.forEach((model, name) => model.setValue(starterFiles[name]));
        viewStates.clear();
        editor.setPosition({ lineNumber: 1, column: 1 });
        editor.revealLine(1);
        output.replaceChildren();
        clearResults();
    });

    const unsubscribe = runtimeManager.subscribe(showRuntimeState);
    startEditor();
    return {
        isDirty: () => [...models].some(([name, model]) => model.getValue() !== starterFiles[name]),
        unsubscribe,
    };
}
