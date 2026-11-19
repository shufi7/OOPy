const sidebar = document.getElementById('oopyDashboardSidebar');
if (sidebar) {
    const body = document.body;
    const mobile = window.matchMedia('(max-width: 991.98px)');
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    const menuLinks = [...sidebar.querySelectorAll('.oopy-dashboard-menu-link')];
    const mobileLinks = [...document.querySelectorAll('[data-dashboard-section]')];
    const dashboardLink = sidebar.querySelector('[aria-current="page"]');
    const information = document.getElementById('informasi');
    const informationLinks = [...menuLinks, ...mobileLinks].filter(link => link.hash === '#informasi');
    const topbar = document.querySelector('.oopy-dashboard-topbar');
    const navbarCollapse = document.getElementById('navbarOOPy');

    function updateTopbarHeight() {
        body.style.setProperty('--dashboard-topbar-height', `${Math.ceil(topbar.getBoundingClientRect().height)}px`);
    }
    new ResizeObserver(updateTopbarHeight).observe(topbar);
    updateTopbarHeight();

    function updateSidebar() {
        sidebar.inert = mobile.matches;
        sidebar.setAttribute('aria-hidden', String(mobile.matches));
    }

    function updateInformationState() {
        informationLinks.forEach(link => link.setAttribute('aria-expanded', String(information.open)));
    }

    function activateSection(hash) {
        const sectionLink = menuLinks.find(link => link.hash && link.hash === hash);
        const selected = sectionLink || dashboardLink;
        document.querySelectorAll('.oopy-dashboard #profil, .oopy-dashboard #informasi').forEach(section => {
            section.classList.toggle('is-section-active', Boolean(sectionLink) && section.id === hash.slice(1));
        });
        menuLinks.forEach(link => {
            link.classList.toggle('is-active', link === selected);
            if (link === selected) link.setAttribute('aria-current', sectionLink ? 'location' : 'page');
            else link.removeAttribute('aria-current');
        });
        if (!sectionLink) return;
        const section = document.getElementById(hash.slice(1));
        if (!section) return;
        if (section instanceof HTMLDetailsElement) section.open = true;
        updateInformationState();
        const focusSection = () => {
            updateTopbarHeight();
            section.querySelector('summary, h2')?.focus({ preventScroll: true });
            section.scrollIntoView({ behavior: reducedMotion.matches ? 'instant' : 'smooth', block: 'start' });
        };
        if (mobile.matches && navbarCollapse?.classList.contains('show') && window.bootstrap?.Collapse) {
            navbarCollapse.addEventListener('hidden.bs.collapse', focusSection, { once: true });
            window.bootstrap.Collapse.getOrCreateInstance(navbarCollapse).hide();
        } else focusSection();
    }

    [...menuLinks, ...mobileLinks].forEach(link => link.addEventListener('click', event => {
        if (event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        if (link.hash && link.pathname === window.location.pathname && link.origin === window.location.origin) {
            event.preventDefault();
            if (window.location.hash !== link.hash) window.history.pushState(null, '', link.hash);
            activateSection(link.hash);
            return;
        }
    }));
    information?.addEventListener('toggle', updateInformationState);
    window.addEventListener('hashchange', () => activateSection(window.location.hash));
    window.addEventListener('popstate', () => activateSection(window.location.hash));
    mobile.addEventListener('change', updateSidebar);
    updateSidebar();
    activateSection(window.location.hash);
}
