/* Python runs only in this browser worker; Laravel never executes learner code. */
const PYODIDE_URL = 'https://cdn.jsdelivr.net/pyodide/v0.27.7/full/';
const FILE_NAMES = ['ekosistem.py', 'sungai.py', 'rawa.py', 'main.py'];
const OUTPUT_LIMIT = 100000;
let runtime;
let checksSource;
let currentRequest = null;
let outputLength = 0;
let outputLimited = false;
let outputChunks = [];
let chunkLength = 0;
let busy = false;

function flushOutput() {
    if (outputChunks.length) {
        self.postMessage({ type: 'output', id: currentRequest, chunks: outputChunks });
        outputChunks = [];
        chunkLength = 0;
    }
}

function outputWriter(stream) {
    const decoder = new TextDecoder();
    return {
        write(buffer) {
            const text = decoder.decode(buffer, { stream: true });
            if (currentRequest === null || outputLimited) return buffer.length;
            const remaining = OUTPUT_LIMIT - outputLength;
            const clipped = text.slice(0, remaining);
            const last = outputChunks[outputChunks.length - 1];
            if (last?.stream === stream) last.text += clipped;
            else outputChunks.push({ stream, text: clipped });
            outputLength += clipped.length;
            chunkLength += clipped.length;
            if (text.length > remaining) {
                outputChunks.push({ stream: 'stderr', text: '\n[Output dibatasi hingga 100.000 karakter.]\n' });
                outputLimited = true;
            }
            // Batch messages so print loops cannot overwhelm the main UI thread.
            if (chunkLength >= 2048 || outputLimited) flushOutput();
            return buffer.length;
        },
        isatty: false,
    };
}

const ready = (async () => {
    importScripts(`${PYODIDE_URL}pyodide.js`);
    const response = await fetch(new URL('checks.py', self.location.href));
    if (!response.ok) throw new Error('Tidak dapat memuat pemeriksaan latihan.');
    checksSource = await response.text();
    runtime = await loadPyodide({ indexURL: PYODIDE_URL, stdout: () => {}, stderr: () => {} });
    runtime.FS.mkdirTree('/workspace');
    runtime.setStdout(outputWriter('stdout'));
    runtime.setStderr(outputWriter('stderr'));
    runtime.setStdin({ stdin: () => null });
    self.postMessage({ type: 'ready' });
})().catch((error) => {
    self.postMessage({ type: 'init-error', message: String(error.message || error) });
});

async function syncProject(files) {
    for (const name of FILE_NAMES) {
        if (typeof files?.[name] !== 'string') throw new Error(`File ${name} tidak ditemukan.`);
        runtime.FS.writeFile(`/workspace/${name}`, files[name], { encoding: 'utf8' });
    }

    // Remove only workspace modules. Disable/remove bytecode as equal-length edits
    // made within the same second could otherwise reuse a stale .pyc file.
    await runtime.runPythonAsync(`
import sys as _sys
import os as _os
import importlib as _importlib
import shutil as _shutil
_sys.dont_write_bytecode = True
_os.chdir('/workspace')
if '/workspace' not in _sys.path:
    _sys.path.insert(0, '/workspace')
for _name, _module in list(_sys.modules.items()):
    _file = getattr(_module, '__file__', '') or ''
    if _name in ('ekosistem', 'sungai', 'rawa', 'main') or _file.startswith('/workspace/'):
        _sys.modules.pop(_name, None)
_shutil.rmtree('/workspace/__pycache__', ignore_errors=True)
_importlib.invalidate_caches()
`);
}

self.onmessage = async ({ data }) => {
    if (!['run', 'check'].includes(data.type) || busy) return;
    busy = true;
    await ready;
    if (!runtime) { busy = false; return; }
    currentRequest = data.id;
    outputLength = 0;
    outputLimited = false;
    outputChunks = [];
    chunkLength = 0;
    let namespace;

    try {
        await syncProject(data.files);
        // Each operation gets fresh globals; imports still use the real filesystem.
        namespace = runtime.toPy({ __name__: '__main__', __file__: '/workspace/main.py' });
        if (data.type === 'check') {
            const result = await runtime.runPythonAsync(checksSource, { globals: namespace, filename: '<pemeriksaan>' });
            flushOutput();
            self.postMessage({ type: 'results', id: data.id, results: JSON.parse(result) });
        } else {
            const result = await runtime.runPythonAsync(
                "exec(compile(open('/workspace/main.py', encoding='utf-8').read(), '/workspace/main.py', 'exec'))",
                { globals: namespace },
            );
            result?.destroy?.();
        }
        await runtime.runPythonAsync('import sys; sys.stdout.flush(); sys.stderr.flush()');
        flushOutput();
        self.postMessage({ type: 'done', id: data.id });
    } catch (error) {
        flushOutput();
        self.postMessage({ type: 'error', id: data.id, message: String(error.message || error) });
    } finally {
        namespace?.destroy();
        currentRequest = null;
        busy = false;
    }
};
