(() => {
    'use strict';
    const menu = document.getElementById('admin-menu-toggle');
    const sidebar = document.getElementById('admin-sidebar');
    if (!menu || !sidebar) return;
    const narrow = window.matchMedia('(max-width: 991px)');
    const setOpen = open => {
        menu.setAttribute('aria-expanded', String(open));
        sidebar.hidden = narrow.matches && !open;
    };
    setOpen(!narrow.matches);
    narrow.addEventListener('change', () => setOpen(!narrow.matches));
    menu.addEventListener('click', () => setOpen(menu.getAttribute('aria-expanded') !== 'true'));
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && narrow.matches && !sidebar.hidden) {
            setOpen(false);
            menu.focus();
        }
    });
})();
