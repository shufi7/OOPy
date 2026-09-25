<nav class="navbar navbar-expand-lg navbar-light site-navbar py-3">
    <div class="container-fluid px-lg-5 px-3">
        <a class="navbar-brand d-flex align-items-center gap-2 me-4" href="{{ route('home') }}">
            <img src="{{ asset('images/oopy-logo.png') }}" alt="" class="navbar-logo" width="64" height="64">
            <span class="brand-name fw-bold fs-3">OOPy</span>
        </a>

        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarOOPy" aria-controls="navbarOOPy" aria-expanded="false" aria-label="Buka menu navigasi">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarOOPy">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-4 gap-2 pt-2 pt-lg-0 fw-bold">
                <li class="nav-item">
                    <a class="nav-link px-2 {{ request()->routeIs('home') ? 'active' : '' }}"
                       @if (request()->routeIs('home')) aria-current="page" @endif
                       href="{{ route('home') }}">
                        Beranda
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-2 {{ request()->routeIs('materi.*') ? 'active' : '' }}"
                       @if (request()->routeIs('materi.*')) aria-current="page" @endif
                       href="{{ route('materi.index') }}">
                        Materi
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-2 {{ request()->routeIs('editor.*') ? 'active' : '' }}"
                       @if (request()->routeIs('editor.*')) aria-current="page" @endif
                       href="{{ route('editor.index') }}">Editor</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-2" href="#">Dokumentasi</a>
                </li>
                <li class="nav-item ms-lg-2">
                    <a href="#" class="btn btn-brand fw-bold px-4 py-2">Masuk</a>
                </li>
            </ul>
        </div>
    </div>
</nav>
