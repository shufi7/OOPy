<div class="oopy-auth-alternative">
    <p class="oopy-auth-separator">atau</p>
    @error('google')<p class="oopy-auth-error" role="alert">{{ $message }}</p>@enderror
    <a class="btn oopy-auth-google" href="{{ route('auth.google.redirect') }}">Masuk dengan Google</a>
</div>
