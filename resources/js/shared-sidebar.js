const toggle = document.querySelector('[data-side-toggle]');
const sidebar = document.querySelector('#sgAppSidebar');
const scrim = document.querySelector('[data-side-scrim]');
if (toggle && sidebar) {
    const setOpen = (open) => {
        sidebar.classList.toggle('open', open);
        toggle.setAttribute('aria-expanded', String(open));
        toggle.setAttribute('aria-label', open ? 'Close navigation' : 'Open navigation');
        toggle.textContent = open ? 'Close' : 'Menu';
        if (scrim) scrim.hidden = !open;
    };
    toggle.addEventListener('click', () => setOpen(!sidebar.classList.contains('open')));
    scrim?.addEventListener('click', () => setOpen(false));
    sidebar.querySelectorAll('a').forEach((link) => link.addEventListener('click', () => setOpen(false)));
}