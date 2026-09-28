@pushOnce('styles', 'oopy-live-code-styles')
    <link rel="stylesheet" href="{{ asset('css/oopy-live-code.css') }}">
@endPushOnce
@pushOnce('scripts', 'oopy-live-code-scripts')
    <script type="module" src="{{ asset('js/live-code/live-code.js') }}"></script>
@endPushOnce

<section {{ $attributes->class(['oopy-live-code'])->merge(['id' => $config['id']]) }} data-live-code aria-label="{{ $config['title'] }}">
    <script type="application/json" data-role="config">{!! \Illuminate\Support\Js::encode($config) !!}</script>
    <section class="oopy-workspace" aria-labelledby="{{ $config['id'] }}-workspace-title">
        <div class="oopy-workspace-heading">
            <h2 id="{{ $config['id'] }}-workspace-title" data-role="workspace-title"><i class="bi bi-code-slash" aria-hidden="true"></i> {{ $config['title'] }}</h2>
            <span>{{ $config['description'] }}</span>
        </div>
        <div class="oopy-workspace-body">
            <nav class="oopy-file-explorer" aria-label="File project">
                <h3><i class="bi bi-folder2" aria-hidden="true"></i> File Project</h3>
                @foreach (array_keys($config['files']) as $file)
                    <button type="button" class="oopy-file-button {{ $file === $config['entry_file'] ? 'is-active' : '' }}" data-file="{{ $file }}" aria-pressed="{{ $file === $config['entry_file'] ? 'true' : 'false' }}"><i class="bi bi-file-earmark-code" aria-hidden="true"></i>{{ $file }}<span class="oopy-file-dirty" aria-label="Diubah" hidden></span></button>
                @endforeach
                <div class="oopy-explorer-hint"><span class="oopy-python-dot"></span> Python <span>{{ count($config['files']) }} file</span></div>
            </nav>
            <div class="oopy-code-pane">
                <div class="oopy-file-tabs" role="tablist" aria-label="File Python">
                    @foreach (array_keys($config['files']) as $file)
                        <button type="button" role="tab" id="{{ $config['id'] }}-tab-{{ $loop->index }}" data-role="tab-{{ $loop->index }}" class="oopy-file-tab {{ $file === $config['entry_file'] ? 'is-active' : '' }}" data-file="{{ $file }}" aria-selected="{{ $file === $config['entry_file'] ? 'true' : 'false' }}" aria-controls="{{ $config['id'] }}-editor-panel" tabindex="{{ $file === $config['entry_file'] ? '0' : '-1' }}"><i class="bi bi-file-earmark-code" aria-hidden="true"></i>{{ $file }}<span class="oopy-file-dirty" aria-label="Diubah" hidden></span></button>
                    @endforeach
                </div>
                <div class="oopy-editor-panel" id="{{ $config['id'] }}-editor-panel" data-role="editor-panel" role="tabpanel" aria-labelledby="{{ $config['id'] }}-tab-{{ array_search($config['entry_file'], array_keys($config['files']), true) }}">
                    <div class="oopy-monaco-editor" id="{{ $config['id'] }}-monaco-editor" data-role="monaco-editor" aria-label="Editor kode Python"></div>
                    <div class="oopy-editor-loading" id="{{ $config['id'] }}-editor-loading" data-role="editor-loading" role="status"><span class="spinner-border spinner-border-sm" aria-hidden="true"></span><span>Menyiapkan editor...</span></div>
                </div>
                <div class="oopy-editor-statusbar"><span id="{{ $config['id'] }}-active-file-path" data-role="active-file-path">workspace / {{ $config['entry_file'] }}</span><span>UTF-8 <span>Python</span></span></div>
            </div>
        </div>
        <div class="oopy-editor-actions">
            <div class="oopy-action-group">
                <button type="button" class="oopy-button oopy-button-primary" id="{{ $config['id'] }}-run-code" data-role="run-code" disabled title="Jalankan {{ $config['entry_file'] }} (Ctrl+Enter)"><i class="bi bi-play" aria-hidden="true"></i> Run Code</button>
                <button type="button" class="oopy-button oopy-button-danger" id="{{ $config['id'] }}-stop-code" data-role="stop-code" hidden><i class="bi bi-stop" aria-hidden="true"></i> Hentikan</button>
                <button type="button" class="oopy-button oopy-button-muted" id="{{ $config['id'] }}-reset-code" data-role="reset-code" disabled><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> Reset</button>
                <span class="oopy-run-hint">Jalankan <code>{{ $config['entry_file'] }}</code><kbd>Ctrl ↵</kbd></span>
            </div>
            <button type="button" class="oopy-button oopy-button-success" id="{{ $config['id'] }}-check-code" data-role="check-code" disabled title="Periksa jawaban dari semua file latihan"><i class="bi bi-check2" aria-hidden="true"></i> Submit</button>
        </div>
    </section>

    <section class="oopy-terminal" aria-labelledby="{{ $config['id'] }}-output-title">
        <div class="oopy-terminal-heading">
            <h2 id="{{ $config['id'] }}-output-title" data-role="output-title"><i class="bi bi-terminal" aria-hidden="true"></i> Output</h2>
            <div class="oopy-terminal-tools">
                <span class="oopy-runtime-status" id="{{ $config['id'] }}-python-status" data-role="python-status" data-state="loading" role="status"><span></span><span id="{{ $config['id'] }}-python-status-text" data-role="python-status-text">Menyiapkan Python...</span></span>
                <button type="button" class="oopy-terminal-retry" id="{{ $config['id'] }}-retry-runtime" data-role="retry-runtime" hidden>Coba lagi</button>
                <button type="button" class="oopy-icon-button" id="{{ $config['id'] }}-clear-output" data-role="clear-output" aria-label="Bersihkan output" title="Bersihkan output"><i class="bi bi-trash3" aria-hidden="true"></i></button>
            </div>
        </div>
        <pre class="oopy-output" id="{{ $config['id'] }}-code-output" data-role="code-output" aria-label="Output program" tabindex="0"><span class="oopy-output-muted">Klik Run Code untuk menjalankan {{ $config['entry_file'] }}.
Hasil program dan pesan error akan muncul di sini.</span></pre>
    </section>

    <section class="oopy-check-results" id="{{ $config['id'] }}-check-results" data-role="check-results" aria-labelledby="{{ $config['id'] }}-results-title" tabindex="-1" hidden>
        <div class="oopy-results-heading"><h2 id="{{ $config['id'] }}-results-title" data-role="results-title">Hasil Pemeriksaan</h2><strong id="{{ $config['id'] }}-check-score" data-role="check-score"></strong></div>
        <p id="{{ $config['id'] }}-check-summary" data-role="check-summary" role="status"></p>
        <ul id="{{ $config['id'] }}-check-list" data-role="check-list"></ul>
    </section>

    <div class="oopy-exercise-progress">
        <span>Progres latihan coding: <strong data-role="practice-percentage">0%</strong></span>
        <div class="oopy-progress-track" role="progressbar" aria-label="Progres latihan coding" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" data-role="practice-progress"><span data-role="practice-progress-fill"></span></div>
    </div>
    <noscript>Aktifkan JavaScript untuk menggunakan Live Coding.</noscript>
</section>
