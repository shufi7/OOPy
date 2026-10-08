@props(['name', 'label', 'type' => 'text', 'autocomplete', 'hint' => null, 'autofocus' => false])

<div class="oopy-auth-field">
    <label for="{{ $name }}" class="oopy-auth-label">{{ $label }}</label>
    <div class="oopy-auth-input-wrap">
        <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}"
            class="oopy-auth-input {{ $type === 'password' ? 'oopy-auth-input-password' : '' }}"
            @if($type !== 'password') value="{{ old($name) }}" maxlength="255" @endif
            autocomplete="{{ $autocomplete }}" required @if($autofocus) autofocus @endif
            @if($type === 'email') autocapitalize="none" spellcheck="false" @endif
            @error($name) aria-invalid="true" @enderror
            @if($errors->has($name) || $hint) aria-describedby="{{ $errors->has($name) ? $name.'-error' : '' }} {{ $hint ? $name.'-hint' : '' }}" @endif>
        @if($type === 'password')
            <button class="oopy-auth-password-toggle" type="button" data-password-toggle="{{ $name }}"
                aria-controls="{{ $name }}" aria-pressed="false" aria-label="Tampilkan {{ strtolower($label) }}" hidden>Tampilkan</button>
        @endif
    </div>
    @if($hint)<p id="{{ $name }}-hint" class="oopy-auth-hint">{{ $hint }}</p>@endif
    @error($name)<p id="{{ $name }}-error" class="oopy-auth-error" role="alert">{{ $message }}</p>@enderror
</div>
