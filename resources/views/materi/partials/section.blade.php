<section class="material-section" id="{{ $section['id'] }}" aria-labelledby="{{ $section['id'] }}-title" tabindex="-1" data-material-section>
    <span class="material-eyebrow">BAGIAN {{ str_pad($number, 2, '0', STR_PAD_LEFT) }}</span>
    <h2 id="{{ $section['id'] }}-title">{{ $section['title'] }}</h2>
    @foreach ($section['paragraphs'] ?? [] as $paragraph)
        <p>{{ $paragraph }}</p>
    @endforeach
    @foreach ($section['tables'] ?? [] as $table)
        <div class="material-table-wrapper" role="region" aria-label="{{ $table['caption'] }}" tabindex="0">
            <table class="table material-table align-middle">
                <caption class="caption-top">{{ $table['caption'] }}</caption>
                <thead>
                    <tr>
                        @foreach ($table['headers'] as $heading)
                            <th scope="col">{{ $heading }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($table['rows'] as $row)
                        <tr>
                            @foreach ($row as $cell)
                                <td>@if (is_array($cell))<code>{{ $cell['code'] }}</code>@else{{ $cell }}@endif</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endforeach
    @if (!empty($section['code']))
        <figure class="material-code">
            <figcaption>Contoh Python · {{ $section['title'] }}</figcaption>
            <pre tabindex="0" aria-label="Contoh kode {{ $section['title'] }}"><code class="language-python">{{ $section['code'] }}</code></pre>
        </figure>
    @endif
    @if (isset($section['output']))
        <figure class="material-code material-output">
            <figcaption>Output</figcaption>
            <pre tabindex="0" aria-label="Output contoh {{ $section['title'] }}"><code>{{ $section['output'] }}</code></pre>
        </figure>
    @endif
    @if (!empty($section['breakdown']))
        <div class="material-practice" role="group" aria-labelledby="{{ $section['id'] }}-breakdown-title">
            <h3 id="{{ $section['id'] }}-breakdown-title">Bedah Kode</h3>
            <ul class="material-list">
                @foreach ($section['breakdown'] as $point)
                    <li>{{ $point }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    @if (!empty($section['hierarchy']))
        <figure class="material-tip" aria-labelledby="{{ $section['id'] }}-hierarchy-caption">
            <figcaption id="{{ $section['id'] }}-hierarchy-caption"><strong>{{ $section['hierarchy']['caption'] }}</strong></figcaption>
            <ul class="material-list">
                <li>
                    <strong>{{ $section['hierarchy']['label'] }}</strong>
                    <code>{{ $section['hierarchy']['contract'] }}</code>
                    <ul>
                        @foreach ($section['hierarchy']['children'] as $child)
                            <li><strong>{{ $child['label'] }}</strong>{{ $child['contract'] }}</li>
                        @endforeach
                    </ul>
                </li>
            </ul>
        </figure>
    @endif
    @foreach ($section['examples'] ?? [] as $example)
        <div class="material-practice" role="group" aria-labelledby="{{ $section['id'] }}-example-{{ $loop->index }}-title">
            <h3 id="{{ $section['id'] }}-example-{{ $loop->index }}-title">{{ $example['title'] }}</h3>
            @foreach ($example['paragraphs'] ?? [] as $paragraph)
                <p>{{ $paragraph }}</p>
            @endforeach
            <figure class="material-code">
                <figcaption>Contoh Python · {{ $example['title'] }}</figcaption>
                <pre tabindex="0" aria-label="Contoh kode {{ $example['title'] }}"><code class="language-python">{{ $example['code'] }}</code></pre>
            </figure>
            @if (isset($example['output']))
                <figure class="material-code material-output">
                    <figcaption>Output</figcaption>
                    <pre tabindex="0" aria-label="Output contoh {{ $example['title'] }}"><code>{{ $example['output'] }}</code></pre>
                </figure>
            @endif
        </div>
    @endforeach
    @if (!empty($section['tip']))
        <aside class="material-tip" aria-label="Catatan {{ $section['title'] }}">
            <strong><i class="bi bi-lightbulb" aria-hidden="true"></i> Catatan belajar</strong>
            <p>{{ $section['tip'] }}</p>
        </aside>
    @endif
    @if (!empty($section['instructions']))
        <div class="material-practice" role="group" aria-labelledby="{{ $section['id'] }}-instructions-title">
            <h3 id="{{ $section['id'] }}-instructions-title">Langkah Pengerjaan</h3>
            <ol class="material-list">
                @foreach ($section['instructions'] as $instruction)
                    <li>{{ $instruction }}</li>
                @endforeach
            </ol>
        </div>
    @endif
    @foreach ($section['live_codes'] ?? [] as $exercise)
        <x-live-code :config="$exercise" :heading-level="3" />
    @endforeach
    @if (!empty($section['exploration']))
        <aside class="material-tip" aria-label="Eksplorasi setelah latihan">
            <strong>Eksplorasi setelah latihan</strong>
            @foreach ($section['exploration'] as $paragraph)
                <p>{{ $paragraph }}</p>
            @endforeach
        </aside>
    @endif
    @if (!empty($section['practice']))
        <div class="material-practice" role="group" aria-labelledby="{{ $section['id'] }}-practice-title">
            <h3 id="{{ $section['id'] }}-practice-title">Ayo Berlatih</h3>
            <ol class="material-list">
                @foreach ($section['practice'] as $activity)
                    <li>{{ $activity }}</li>
                @endforeach
            </ol>
        </div>
    @endif
</section>
