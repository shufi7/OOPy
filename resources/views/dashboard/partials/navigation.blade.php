<header class="oopy-dashboard-topbar">
    @include('components.navbar')
</header>

<aside class="oopy-dashboard-sidebar" id="oopyDashboardSidebar" aria-label="Menu dashboard">
    <div class="oopy-dashboard-welcome">
        <p>Selamat datang,</p>
        <strong class="oopy-dashboard-greeting">{{ auth()->user()->name }}</strong>
    </div>
    <nav class="oopy-dashboard-menu" aria-label="Navigasi dashboard">
        <a class="oopy-dashboard-menu-link" href="{{ route('materi.index') }}"><x-dashboard-icon name="book" /><span>Belajar</span></a>
        <a href="{{ route('dashboard') }}" class="oopy-dashboard-menu-link is-active" aria-current="page"><x-dashboard-icon name="home" /><span>Dashboard</span></a>
        <a class="oopy-dashboard-menu-link" href="#profil" aria-controls="profil"><x-dashboard-icon name="user" /><span>Profil Saya</span></a>
        <a class="oopy-dashboard-menu-link" href="#informasi" aria-controls="informasi" aria-expanded="false"><x-dashboard-icon name="info" /><span>Informasi</span></a>
    </nav>
    <div class="oopy-dashboard-sidebar-bottom">
        <form method="POST" action="{{ route('logout') }}" data-dashboard-logout data-logout-confirmation>
            @csrf
            <button class="oopy-dashboard-logout" type="submit"><x-dashboard-icon name="logout" /><span>Keluar</span></button>
        </form>
    </div>
</aside>
