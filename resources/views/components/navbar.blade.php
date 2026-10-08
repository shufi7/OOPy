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
                @guest('web')
                    <li class="nav-item">
                        <a href="{{ route('login') }}" class="nav-link px-2 {{ request()->routeIs('login') ? 'active' : '' }}" @if(request()->routeIs('login')) aria-current="page" @endif>Masuk</a>
                    </li>
                    <li class="nav-item ms-lg-2">
                        <a href="{{ route('register') }}" class="btn btn-brand fw-bold px-4 py-2" @if(request()->routeIs('register')) aria-current="page" @endif>Daftar</a>
                    </li>
                @else
                    <li class="nav-item dropdown oopy-account">
                        <button class="nav-link dropdown-toggle px-2 oopy-account-toggle" type="button" id="oopyAccountMenu" data-bs-toggle="dropdown" aria-expanded="false">
                            {{ auth('web')->user()->name }}
                        </button>
                        <ul class="dropdown-menu dropdown-menu-lg-end oopy-account-menu" aria-labelledby="oopyAccountMenu">
                            <li><span class="dropdown-item-text fw-bold">{{ auth('web')->user()->name }}</span></li>
                            <li><span class="dropdown-item-text">Role akun: {{ auth('web')->user()->role === 'admin' ? 'Admin' : 'User' }}</span></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button class="dropdown-item" type="submit">Logout</button>
                                </form>
                            </li>
                        </ul>
                    </li>
                @endguest
            </ul>
        </div>
    </div>
</nav>
