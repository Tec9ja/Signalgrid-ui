const ICON_LABELS = {
    menu: 'Open navigation menu',
    x: 'Close',
    search: 'Search',
    bell: 'Open notifications',
    settings: 'Open settings',
    filter: 'Open filters',
    download: 'Download',
    upload: 'Upload',
    refreshcw: 'Refresh data',
    refreshccw: 'Refresh data',
    morehorizontal: 'More options',
    morevertical: 'More options',
    chevrondown: 'Expand options',
    chevronup: 'Collapse options',
    chevronleft: 'Previous',
    chevronright: 'Next',
    eye: 'View details',
    pencil: 'Edit',
    trash2: 'Delete',
    plus: 'Add item',
    maximize2: 'Expand view',
    minimize2: 'Collapse view',
    circlehelp: 'Help',
    info: 'More information',
    calendar: 'Open calendar',
    calendarrange: 'Select date range',
};

const normalize = (value = '') => value.toLowerCase().replace(/[^a-z0-9]/g, '');

function inferControlLabel(control, index) {
    const icon = control.querySelector('[data-lucide]');
    const iconName = normalize(icon?.getAttribute('data-lucide'));

    return control.dataset.actionLabel
        || control.dataset.tooltip
        || ICON_LABELS[iconName]
        || control.getAttribute('name')
        || control.getAttribute('id')?.replace(/[-_]+/g, ' ')
        || `Application action ${index + 1}`;
}

export function applyAccessibleControlNames(root = document) {
    const controls = [];

    if (root.matches?.('button, [role="button"]')) controls.push(root);
    root.querySelectorAll?.('button, [role="button"]').forEach((control) => controls.push(control));

    controls.forEach((control, index) => {
        const visibleText = control.textContent?.trim();
        const existingName = control.getAttribute('aria-label') || control.getAttribute('title');
        if (visibleText || existingName) return;

        const label = inferControlLabel(control, index);
        control.setAttribute('aria-label', label);
        if (!control.getAttribute('title')) control.setAttribute('title', label);
    });
}

const initializeAccessibleControls = () => {
    applyAccessibleControlNames(document);

    const observer = new MutationObserver((mutations) => {
        for (const mutation of mutations) {
            for (const node of mutation.addedNodes) {
                if (node.nodeType === Node.ELEMENT_NODE) applyAccessibleControlNames(node);
            }
        }
    });

    observer.observe(document.body, { childList: true, subtree: true });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeAccessibleControls, { once: true });
} else {
    initializeAccessibleControls();
}
