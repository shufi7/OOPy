// Enhance native anchors/details; all chapter content remains readable without JS.
const material = document.querySelector('.oopy-material');
if (material) {
    const menu = material.querySelector('.material-toc');
    const links = [...menu.querySelectorAll('nav a')];
    const sections = [...material.querySelectorAll('[data-material-section]')];
    const desktop = window.matchMedia('(min-width: 992px)');
    const openGroup = (link) => {
        const group = link?.closest('.material-toc-group');
        if (group) group.open = true;
    };
    const syncMenu = () => { menu.open = desktop.matches; };
    syncMenu();
    desktop.addEventListener('change', syncMenu);

    let framePending = false;
    function markActiveSection() {
        framePending = false;
        let active = sections[0];
        for (const section of sections) {
            if (section.getBoundingClientRect().top <= 160) active = section;
        }
        links.forEach((link) => {
            if (link.hash === `#${active.id}`) {
                link.setAttribute('aria-current', 'location');
            } else link.removeAttribute('aria-current');
        });
    }
    function scheduleUpdate() {
        if (framePending) return;
        framePending = true;
        requestAnimationFrame(markActiveSection);
    }

    // Only explicit navigation reveals a group. Native details retain the user's
    // collapse choice while scroll/resize merely update the active section.
    function revealHashGroup() {
        openGroup(links.find((link) => link.hash === location.hash));
        const target = sections.find((section) => `#${section.id}` === location.hash);
        // Revealing a mobile submenu shifts the anchor after the native jump.
        if (target) requestAnimationFrame(() => target.scrollIntoView({ block: 'start', behavior: 'instant' }));
        scheduleUpdate();
    }

    material.addEventListener('click', (event) => {
        const link = event.target.closest('a[href^="#"]');
        if (!link || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        const section = sections.find((candidate) => `#${candidate.id}` === link.hash);
        if (!section) return;
        event.preventDefault();
        if (!desktop.matches) menu.open = false;
        openGroup(links.find((candidate) => candidate.hash === link.hash));
        if (location.hash !== link.hash) history.pushState(null, '', link.hash);
        section.focus({ preventScroll: true });
        // The global Bootstrap stylesheet enables smooth scrolling. Jump after
        // collapsing the TOC so the destination does not drift with its old height.
        section.scrollIntoView({ block: 'start', behavior: 'instant' });
        scheduleUpdate();
    });

    window.addEventListener('scroll', scheduleUpdate, { passive: true });
    window.addEventListener('resize', scheduleUpdate);
    window.addEventListener('hashchange', revealHashGroup);
    window.addEventListener('popstate', revealHashGroup);
    // Collapsing the mobile TOC changes layout; restore an initial deep link.
    const initial = sections.find((section) => `#${section.id}` === location.hash);
    if (initial) {
        openGroup(links.find((link) => link.hash === location.hash));
        requestAnimationFrame(() => initial.scrollIntoView({ block: 'start', behavior: 'instant' }));
    }
    scheduleUpdate();
}
