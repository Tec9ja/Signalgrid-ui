const globalNavigation = document.querySelector('[data-global-nav]');
if (globalNavigation) {
    const toggle = globalNavigation.querySelector('[data-nav-toggle]');
    const links = globalNavigation.querySelector('[data-nav-links]');
    toggle?.addEventListener('click', () => {
        const open = links.classList.toggle('open');
        toggle.setAttribute('aria-expanded', String(open));
    });
    links?.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => {
            links.classList.remove('open');
            toggle?.setAttribute('aria-expanded', 'false');
        });
    });
}
