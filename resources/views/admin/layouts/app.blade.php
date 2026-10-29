<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Dashboard') · Admin OOPy</title>
    <link rel="icon" href="{{ asset('images/oopy-logo.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/oopy/base.css') }}">
    <link rel="stylesheet" href="{{ asset('css/oopy/admin/admin.css') }}">
    @stack('styles')
</head>
<body class="oopy-admin">
    <a class="admin-skip" href="#admin-content">Langsung ke konten</a>
    <header class="admin-mobile-header">
        <a href="{{ route('admin.dashboard') }}" class="admin-brand"><img src="{{ asset('images/oopy-logo.png') }}" width="44" height="44" alt="">OOPy <span>Admin</span></a>
        <button type="button" class="btn btn-outline-secondary" id="admin-menu-toggle" aria-expanded="false" aria-controls="admin-sidebar">Menu</button>
    </header>
    <div class="admin-shell">
        @include('admin.partials.sidebar')
        <main id="admin-content" class="admin-main" tabindex="-1">
            <div class="admin-context"><span>Ruang pengelolaan pembelajaran</span><span>{{ auth()->user()->name }}</span></div>
            @if(session('status'))<div class="alert alert-success" role="status">{{ session('status') }}</div>@endif
            @if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
            @if($errors->any())
                <div class="alert alert-danger" role="alert"><strong>Periksa kembali formulir.</strong><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif
            @yield('content')
        </main>
    </div>
    <script src="{{ asset('js/admin/admin.js') }}" defer></script>
    @stack('scripts')
</body>
</html>
