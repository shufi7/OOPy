@php
    $navGroups = [];
    foreach ($content['sections'] ?? [] as $section) {
        $group = $section['nav_group'] ?? 'materi';
        $navGroups[$group][] = [
            'id' => $section['id'],
            'title' => $section['nav_title'] ?? $section['title'],
        ];
    }
    if (!empty($content['summary'])) {
        $navGroups['penutup'][] = ['id' => 'rangkuman', 'title' => 'Rangkuman'];
    }
    if (!empty($content['reflection'])) {
        $navGroups['penutup'][] = ['id' => 'refleksi', 'title' => 'Refleksi'];
    }
@endphp
<aside class="material-sidebar" aria-label="Navigasi {{ $chapter['bab'] }}">
    <details class="material-toc" open>
        <summary aria-controls="chapter-navigation">Daftar Isi {{ $chapter['bab'] }} <i class="bi bi-chevron-down" aria-hidden="true"></i></summary>
        <div id="chapter-navigation">
            <p class="material-eyebrow">{{ $chapter['bab'] }}</p>
            <p class="material-sidebar-title">{{ $chapter['judul'] }}</p>
            <nav aria-label="Daftar isi BAB">
                <ol>
                    <li><a href="#tujuan">Tujuan Pembelajaran</a></li>
                    @foreach ($navGroups as $group => $items)
                    <li>
                        @if ($group === 'pendahuluan')
                        <div class="material-toc-introduction">
                            <p class="material-toc-group-label">Pendahuluan</p>
                            <ol class="material-toc-submenu">
                                @foreach ($items as $item)
                                <li><a href="#{{ $item['id'] }}">{{ $item['title'] }}</a></li>
                                @endforeach
                            </ol>
                        </div>
                        @else
                        <details class="material-toc-group" data-toc-group="{{ $group }}">
                            <summary>
                                {{ $group === 'penutup' ? 'Penutup' : 'Materi '.$chapter['bab'] }}
                                <i class="bi bi-chevron-down" aria-hidden="true"></i>
                            </summary>
                            <ol class="material-toc-submenu">
                                @foreach ($items as $item)
                                <li><a href="#{{ $item['id'] }}">{{ $item['title'] }}</a></li>
                                @endforeach
                            </ol>
                        </details>
                        @endif
                    </li>
                    @endforeach
                    @if (!empty($content['quiz']))
                    <li class="material-toc-quiz"><a href="#kuis">Kuis {{ $chapter['bab'] }}</a></li>
                    @endif
                </ol>
            </nav>
            <div class="material-progress">
                <div class="material-progress-heading">
                    <i class="bi bi-bar-chart" aria-hidden="true"></i>
                    <strong id="chapter-progress-label">Progres {{ $chapter['bab'] }}</strong>
                </div>
                <progress value="0" max="100" aria-labelledby="chapter-progress-label">0%</progress>
            </div>
        </div>
    </details>
</aside>
