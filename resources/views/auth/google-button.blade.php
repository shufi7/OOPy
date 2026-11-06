<div class="oopy-auth-alternative">
    <p class="oopy-auth-separator">atau</p>
    @error('google')<p class="oopy-auth-error" role="alert">{{ $message }}</p>@enderror
    <a class="btn oopy-auth-google" href="{{ route('auth.google.redirect') }}">
        <img src="{{ asset('images/google-g.png') }}" width="20" height="20" alt="" aria-hidden="true">
        <span>Masuk dengan Google</span>
    </a>
</div>
