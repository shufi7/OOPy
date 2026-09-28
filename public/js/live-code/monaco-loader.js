const MONACO_URL = 'https://cdn.jsdelivr.net/npm/monaco-editor@0.52.2/min/';
let loading;
let monacoWorkerUrl;

export function loadMonaco() {
    if (!loading) {
        let timer;
        loading = Promise.race([
            initialize(),
            new Promise((_, reject) => {
                timer = setTimeout(() => reject(new Error('Monaco terlalu lama dimuat. Periksa koneksi lalu muat ulang halaman.')), 90000);
            }),
        ]).finally(() => clearTimeout(timer));
    }
    return loading;
}

async function initialize() {
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
    return window.monaco;
}

window.addEventListener('pagehide', (event) => {
    if (!event.persisted && monacoWorkerUrl) URL.revokeObjectURL(monacoWorkerUrl);
});
