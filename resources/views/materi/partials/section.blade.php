<section class="material-section" id="{{ $section['id'] }}" aria-labelledby="{{ $section['id'] }}-title" tabindex="-1" data-material-section>
    <span class="material-eyebrow">BAGIAN {{ str_pad($number, 2, '0', STR_PAD_LEFT) }}</span>
    <h2 id="{{ $section['id'] }}-title">{{ $number }}. {{ $section['title'] }}</h2>
    @foreach ($section['paragraphs'] as $paragraph)
        <p>{{ $paragraph }}</p>
    @endforeach
    @isset($section['code'])
        <figure class="material-code">
            <figcaption>Contoh Python · {{ $section['title'] }}</figcaption>
            <pre tabindex="0" aria-label="Contoh kode {{ $section['title'] }}"><code>{{ $section['code'] }}</code></pre>
        </figure>
    @endisset
    @isset($section['tip'])
        <aside class="material-tip" aria-label="Catatan {{ $section['title'] }}">
            <strong><i class="bi bi-lightbulb" aria-hidden="true"></i> Catatan belajar</strong>
            <p>{{ $section['tip'] }}</p>
        </aside>
    @endisset
    @foreach ($section['live_codes'] ?? [] as $exercise)
        <x-live-code :config="$exercise" />
    @endforeach
</section>
