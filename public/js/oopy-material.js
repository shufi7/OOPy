// Keep native anchors and details usable without JavaScript. When enabled,
// each existing material section becomes one client-side reading page.
const material = document.querySelector('.oopy-material');
if (material) {
    const menu = material.querySelector('.material-toc');
    const links = [...menu.querySelectorAll('nav a')];
    const sections = [...material.querySelectorAll('[data-material-section]')];
    const readingPosition = material.querySelector('[data-reading-position]');
    const pagination = material.querySelector('[data-material-pagination]');
    const pageList = pagination?.querySelector('[data-pagination-pages]');
    const previousButton = pagination?.querySelector('[data-pagination-previous]');
    const nextButton = pagination?.querySelector('[data-pagination-next]');
    const chapterNavigation = material.querySelector('.material-navigation');
    const desktop = window.matchMedia('(min-width: 992px)');
    let activeIndex = 0;

    const openGroup = (link) => {
        const group = link?.closest('.material-toc-group');
        if (group) group.open = true;
    };
    const syncMenu = () => { menu.open = desktop.matches; };
    syncMenu();
    desktop.addEventListener('change', syncMenu);

    function makePageList() {
        const lastIndex = sections.length - 1;
        const pageIndexes = new Set();
        if (sections.length <= 7) {
            sections.forEach((_, index) => pageIndexes.add(index));
        } else {
            pageIndexes.add(0);
            pageIndexes.add(lastIndex);
            for (let index = Math.max(0, activeIndex - 1); index <= Math.min(lastIndex, activeIndex + 1); index += 1) {
                pageIndexes.add(index);
            }
        }

        const items = [];
        let previousIndex = -1;
        [...pageIndexes].sort((left, right) => left - right).forEach((index) => {
            if (index > previousIndex + 1) {
                const ellipsis = document.createElement('li');
                ellipsis.className = 'material-pagination-ellipsis';
                ellipsis.setAttribute('aria-hidden', 'true');
                ellipsis.textContent = '…';
                items.push(ellipsis);
            }

            const item = document.createElement('li');
            const button = document.createElement('button');
            button.className = 'material-pagination-page';
            button.type = 'button';
            button.textContent = String(index + 1);
            button.setAttribute('aria-label', `Halaman ${index + 1}`);
            if (index === activeIndex) button.setAttribute('aria-current', 'page');
            button.addEventListener('click', () => showPage(index));
            item.append(button);
            items.push(item);
            previousIndex = index;
        });
        pageList.replaceChildren(...items);
    }

    function showPage(index, { updateHistory = false, moveViewport = true } = {}) {
        if (!sections.length) return;
        activeIndex = Math.max(0, Math.min(index, sections.length - 1));
        const active = sections[activeIndex];

        sections.forEach((section, sectionIndex) => {
            section.hidden = sectionIndex !== activeIndex;
        });

        const activeLink = links.find((link) => link.hash === `#${active.id}`);
        links.forEach((link) => {
            if (link === activeLink) link.setAttribute('aria-current', 'location');
            else link.removeAttribute('aria-current');
        });
        openGroup(activeLink);

        const heading = active.querySelector('h2');
        const title = heading?.textContent.trim() || active.getAttribute('aria-label') || 'Bagian materi';
        if (readingPosition) {
            readingPosition.textContent = `Posisi membaca · Bagian ${activeIndex + 1} dari ${sections.length} · ${title}`;
        }

        previousButton.disabled = activeIndex === 0;
        nextButton.disabled = activeIndex === sections.length - 1;
        chapterNavigation.hidden = activeIndex !== sections.length - 1;
        makePageList();
        pagination.hidden = false;

        if (updateHistory && location.hash !== `#${active.id}`) {
            history.pushState(null, '', `#${active.id}`);
        }
        if (moveViewport) {
            active.focus({ preventScroll: true });
            active.scrollIntoView({ block: 'start', behavior: 'instant' });
        }
    }

    function showPageForHash({ moveViewport = true } = {}) {
        const index = sections.findIndex((section) => `#${section.id}` === location.hash);
        showPage(index < 0 ? 0 : index, { moveViewport });
    }

    previousButton.addEventListener('click', () => showPage(activeIndex - 1, { updateHistory: true }));
    nextButton.addEventListener('click', () => showPage(activeIndex + 1, { updateHistory: true }));

    // Explicit same-page anchors (the sidebar and chapter start link) select
    // the matching section instead of scrolling through the full document.
    material.addEventListener('click', (event) => {
        const link = event.target.closest('a[href^="#"]');
        if (!link || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        const index = sections.findIndex((section) => `#${section.id}` === link.hash);
        if (index < 0) return;
        event.preventDefault();
        if (!desktop.matches) menu.open = false;
        showPage(index, { updateHistory: true });
    });

    window.addEventListener('popstate', () => showPageForHash());
    window.addEventListener('hashchange', () => showPageForHash());

    // A valid deep link opens its section; empty or unknown hashes fall back
    // to the first page. Existing section DOM is only hidden, never recreated.
    const initialIndex = sections.findIndex((section) => `#${section.id}` === location.hash);
    showPage(initialIndex < 0 ? 0 : initialIndex, { moveViewport: initialIndex >= 0 });
}
