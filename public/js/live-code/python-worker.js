/* Python runs only in this browser worker; Laravel never executes learner code. */
const PYODIDE_URL = 'https://cdn.jsdelivr.net/pyodide/v0.27.7/full/';
const OUTPUT_LIMIT = 100000;
let runtime;
let executeProject;
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
    const response = await fetch(new URL('project-runner.py', self.location.href));
    if (!response.ok) throw new Error('Tidak dapat memuat runner Python.');
    const runnerSource = await response.text();
    runtime = await loadPyodide({ indexURL: PYODIDE_URL, stdout: () => {}, stderr: () => {} });
    runtime.setStdout(outputWriter('stdout'));
    runtime.setStderr(outputWriter('stderr'));
    runtime.setStdin({ stdin: () => null });
    const globals = runtime.toPy({});
    try {
        await runtime.runPythonAsync(runnerSource, { globals, filename: '<oopy-runner>' });
        executeProject = globals.get('execute_project');
    } finally {
        globals.destroy();
    }
    self.postMessage({ type: 'ready' });
})().catch((error) => {
    self.postMessage({ type: 'init-error', message: String(error.message || error) });
});

function validateProject(data) {
    if (!/^[A-Za-z][A-Za-z0-9_-]*$/.test(data.workspace)) throw new Error('ID workspace tidak valid.');
    if (!data.files || typeof data.files !== 'object' || !Object.keys(data.files).length) throw new Error('Project tidak memiliki file Python.');
    for (const [name, source] of Object.entries(data.files)) {
        if (!/^(?:[A-Za-z0-9_-]+\/)*[A-Za-z0-9_-]+\.py$/.test(name) || typeof source !== 'string') {
            throw new Error(`Path file tidak valid: ${name}`);
        }
    }
    if (!Object.hasOwn(data.files, data.entryFile)) throw new Error(`Entry file ${data.entryFile} tidak ditemukan.`);
    if (typeof data.checker !== 'string' || (data.type === 'check' && !data.checker.trim())) throw new Error('Checker latihan belum tersedia.');
}

self.onmessage = async ({ data }) => {
    if (!['run', 'check'].includes(data.type) || busy) return;
    busy = true;
    await ready;
    if (!runtime || !executeProject) { busy = false; return; }
    currentRequest = data.id;
    outputLength = 0;
    outputLimited = false;
    outputChunks = [];
    chunkLength = 0;
    let files;
    try {
        validateProject(data);
        files = runtime.toPy(data.files);
        const result = JSON.parse(executeProject(files, data.entryFile, data.checker, data.type === 'check', data.workspace));
        flushOutput();
        if (result.error) self.postMessage({ type: 'error', id: data.id, message: result.error });
        else {
            if (result.results) self.postMessage({ type: 'results', id: data.id, results: result.results });
            self.postMessage({ type: 'done', id: data.id });
        }
    } catch (error) {
        flushOutput();
        self.postMessage({ type: 'error', id: data.id, message: String(error.message || error).slice(-OUTPUT_LIMIT) });
    } finally {
        files?.destroy();
        currentRequest = null;
        busy = false;
    }
};
