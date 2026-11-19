<nav class="navbar navbar-expand-lg navbar-light site-navbar" aria-label="Navigasi utama">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center" href="{{ route('home') }}">
            <img src="{{ asset('images/oopy-logo.png') }}" alt="" class="navbar-logo" width="64" height="64">
            <span class="brand-name fw-bold">OOPy</span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarOOPy" aria-controls="navbarOOPy" aria-expanded="false" aria-label="Buka menu navigasi">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarOOPy">
            <ul class="navbar-nav ms-auto align-items-lg-center">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}"
                       @if (request()->routeIs('home')) aria-current="page" @endif
                       href="{{ route('home') }}">
                        Beranda
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('materi.*', 'evaluasi.*') ? 'active' : '' }}"
                       @if (request()->routeIs('materi.*', 'evaluasi.*')) aria-current="page" @endif
                       href="{{ route('materi.index') }}">
                        Materi
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('coding.*') ? 'active' : '' }}"
                       @if (request()->routeIs('coding.*')) aria-current="page" @endif
                       href="{{ route('coding.index') }}">Coding</a>
                </li>
                @guest('web')
                    <li class="nav-item">
                        <a href="{{ route('login') }}" class="nav-link {{ request()->routeIs('login') ? 'active' : '' }}" @if(request()->routeIs('login')) aria-current="page" @endif>Masuk</a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('register') }}" class="btn btn-brand" @if(request()->routeIs('register')) aria-current="page" @endif>Daftar</a>
                    </li>
                @else
                    <li class="nav-item">
                        <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" @if(request()->routeIs('dashboard')) aria-current="page" @endif>Dashboard</a>
                    </li>
                    @if (request()->routeIs('dashboard'))
                        <li class="nav-item d-lg-none"><a class="nav-link" href="#profil" data-dashboard-section aria-controls="profil">Profil Saya</a></li>
                        <li class="nav-item d-lg-none"><a class="nav-link" href="#informasi" data-dashboard-section aria-controls="informasi" aria-expanded="false">Informasi</a></li>
                    @endif
                    <li class="nav-item dropdown oopy-account">
                        <button class="nav-link dropdown-toggle oopy-account-toggle" type="button" id="oopyAccountMenu" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="oopy-account-name">{{ auth('web')->user()->name }}</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-lg-end oopy-account-menu" aria-labelledby="oopyAccountMenu">
                            <li><span class="dropdown-item-text fw-bold">{{ auth('web')->user()->name }}</span></li>
                            <li><span class="dropdown-item-text">Role akun: {{ auth('web')->user()->role === 'admin' ? 'Admin' : 'User' }}</span></li>
                            @if(auth('web')->user()->role === 'admin')
                                <li><a class="dropdown-item" href="{{ route('admin.dashboard') }}">Panel Admin</a></li>
                            @endif
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}" data-logout-confirmation>
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
