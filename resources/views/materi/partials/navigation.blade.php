<aside class="material-sidebar" aria-label="Navigasi {{ $chapter['bab'] }}">
    <details class="material-toc" open>
        <summary aria-controls="chapter-navigation">Daftar isi {{ $chapter['bab'] }} <i class="bi bi-chevron-down" aria-hidden="true"></i></summary>
        <div id="chapter-navigation">
            <p class="material-eyebrow">{{ $chapter['bab'] }}</p>
            <p class="material-sidebar-title">{{ $chapter['judul'] }}</p>
            <nav aria-label="Daftar isi BAB">
                <ol>
                    <li><a href="#tujuan">Tujuan Pembelajaran</a></li>
                    @foreach ($content['sections'] ?? [] as $section)
                        <li><a href="#{{ $section['id'] }}">{{ $section['title'] }}</a></li>
                    @endforeach
                    @if (!empty($content['summary']))
                        <li><a href="#rangkuman">Rangkuman</a></li>
                    @endif
                    @if (!empty($content['reflection']))
                        <li><a href="#refleksi">Refleksi</a></li>
                    @endif
                    @if (!empty($content['quiz']))
                        <li><a href="#kuis">Kuis BAB</a></li>
                    @endif
                </ol>
            </nav>
            <div class="material-progress">
                <div class="material-progress-heading">
                    <i class="bi bi-bar-chart" aria-hidden="true"></i>
                    <strong id="chapter-progress-label">Progres {{ $chapter['bab'] }}</strong>
                </div>
                <progress value="0" max="100" aria-labelledby="chapter-progress-label" aria-describedby="chapter-progress-note">0%</progress>
                <p id="chapter-progress-note">Progres belajar belum dicatat atau disimpan.</p>
            </div>
        </div>
    </details>
</aside>
