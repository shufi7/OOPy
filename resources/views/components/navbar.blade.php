<nav class="navbar navbar-expand-lg navbar-light bg-white py-3">
    <div class="container-fluid px-lg-5 px-3">
        {{-- Logo OOPy --}}
        <a class="navbar-brand fw-bold fs-3 d-flex align-items-center me-4" href="{{ route('home') }}" style="letter-spacing: -0.5px;">
            <span style="color: #173b5e;">OOP</span><span style="color: #e59b20;">y</span>
        </a>

        {{-- Hamburger Toggler untuk Mobile --}}
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarOOPy" aria-controls="navbarOOPy" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        {{-- Menu Navigasi --}}
        <div class="collapse navbar-collapse" id="navbarOOPy">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-4 gap-2 pt-2 pt-lg-0 fw-bold" style="font-size: 0.95rem;">
                <li class="nav-item">
                    <a class="nav-link px-2" 
                       style="{{ request()->routeIs('home') || request()->is('/') ? 'color: #e59b20 !important;' : 'color: #111827 !important;' }}" 
                       href="{{ route('home') }}">
                        Beranda
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-2" 
                       style="{{ request()->routeIs('materi.*') ? 'color: #e59b20 !important;' : 'color: #111827 !important;' }}" 
                       href="{{ route('materi.index') }}">
                        Materi
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-2" style="color: #111827 !important;" href="#">
                        Editor
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-2" style="color: #111827 !important;" href="#">
                        Dokumentasi
                    </a>
                </li>
                <li class="nav-item ms-lg-2">
                    <a href="#" class="btn text-white fw-bold px-4 py-2" style="background-color: #16b777; border-radius: 8px; font-size: 0.95rem;">
                        Masuk
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>
