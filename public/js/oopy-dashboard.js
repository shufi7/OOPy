const sidebar = document.getElementById('oopyDashboardSidebar');
const toggle = document.querySelector('.oopy-dashboard-menu-toggle');
if (sidebar && toggle) {
    const body = document.body;
    const backdrop = document.querySelector('.oopy-dashboard-backdrop');
    const main = document.querySelector('main');
    const mobile = window.matchMedia('(max-width: 991.98px)');
    body.classList.add('is-dashboard-interactive');
    toggle.hidden = false;

    function setOpen(open, focus = false) {
        if (!open && sidebar.contains(document.activeElement)) toggle.focus();
        body.classList.toggle('is-menu-open', mobile.matches && open);
        body.classList.toggle('is-sidebar-collapsed', !mobile.matches && !open);
        toggle.setAttribute('aria-expanded', String(open));
        toggle.setAttribute('aria-label', open ? 'Tutup menu dashboard' : 'Buka menu dashboard');
        sidebar.inert = !open;
        sidebar.setAttribute('aria-hidden', String(!open));
        main.inert = mobile.matches && open;
        backdrop.hidden = !(mobile.matches && open);
        if (focus && mobile.matches && open) sidebar.querySelector('[aria-current="page"]').focus();
    }

    toggle.addEventListener('click', () => setOpen(toggle.getAttribute('aria-expanded') !== 'true', true));
    backdrop.addEventListener('click', () => setOpen(false));
    document.addEventListener('keydown', event => { if (event.key === 'Escape' && mobile.matches) setOpen(false); });
    sidebar.querySelectorAll('a').forEach(link => link.addEventListener('click', () => { if (mobile.matches) setOpen(false); }));
    mobile.addEventListener('change', () => setOpen(!mobile.matches));
    setOpen(!mobile.matches);
}
