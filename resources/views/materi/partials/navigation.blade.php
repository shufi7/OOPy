<aside class="material-sidebar" aria-label="Navigasi {{ $chapter['bab'] }}">
    <details class="material-toc" open>
        <summary aria-controls="chapter-navigation">Daftar isi {{ $chapter['bab'] }} <i class="bi bi-chevron-down" aria-hidden="true"></i></summary>
        <div id="chapter-navigation">
            <p class="material-eyebrow">{{ $chapter['bab'] }}</p>
            <p class="material-sidebar-title">{{ $chapter['judul'] }}</p>
            <nav aria-label="Daftar isi BAB">
                <ol>
                    <li><a href="#tujuan" aria-current="location">Tujuan Pembelajaran</a></li>
                    @foreach ($content['sections'] as $section)
                        <li><a href="#{{ $section['id'] }}">{{ $section['title'] }}</a></li>
                    @endforeach
                    <li><a href="#rangkuman">Rangkuman</a></li>
                    <li><a href="#kuis">Kuis BAB</a></li>
                </ol>
            </nav>
            <div class="material-progress">
                <div><span id="chapter-progress-label">Progres {{ $chapter['bab'] }}</span><strong>0%</strong></div>
                <progress value="0" max="100" aria-labelledby="chapter-progress-label">0%</progress>
                <p>Pencatatan progres belajar belum tersedia.</p>
            </div>
        </div>
    </details>
</aside>
