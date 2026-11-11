const routes = window.signalGridUiRoutes;

if (routes) {
    const navigate = (url) => {
        if (url) window.location.href = url;
    };

    const buttonText = (button) => button.textContent
        .replace(/\s+/g, ' ')
        .trim()
        .toLowerCase();

    const routeMap = {
        'upload floor plan': 'floorPlans',
        'new survey': 'surveys',
        'calibrate scale': 'floorPlans',
        'configure': 'sources',
        'test device': 'sources',
        'view setup': 'sources',
        'add connector': 'sources',
        'export': 'reports',
        'configure rules': 'insights',
        'assign owner': 'insights',
        'create action item': 'insights',
        'share feedback form': 'feedback',
        'generate report': 'reports',
        'compare': 'comparisons',
        'export pdf': 'reports',
        'export csv': 'reports',
        'preview report': 'reports',
        'add location': 'locations',
        'open floor plan': 'locations',
        'start survey': 'surveys',
        'download template': 'template',
        'continue to column mapping': 'surveys',
    };

    const localChoices = new Set([
        'signal',
        'speed',
        'latency',
        'today',
        '7 days',
        'survey period',
        '1',
        '2',
        '3',
        '4',
        '5',
    ]);

    document.querySelectorAll('button').forEach((button) => {
        if (
            button.dataset.page
            || button.dataset.open
            || button.dataset.tool
            || button.dataset.state
            || button.type === 'submit'
        ) {
            return;
        }

        const label = buttonText(button);

        if (routeMap[label]) {
            button.addEventListener('click', () => navigate(routes[routeMap[label]]));
            button.dataset.connected = 'route';
            return;
        }

        if (localChoices.has(label)) {
            button.addEventListener('click', () => {
                button.parentElement?.querySelectorAll('button').forEach((item) => {
                    item.classList.remove('active');
                });
                button.classList.add('active');
            });
            button.dataset.connected = 'local';
            return;
        }

        if (/^-[0-9]+$/.test(label)) {
            button.addEventListener('click', () => {
                const toast = document.querySelector('#toast');
                if (!toast) return;
                const title = toast.querySelector('b');
                if (title) title.textContent = `RSSI ${button.textContent.trim()} dBm`;
                toast.classList.add('show');
                window.setTimeout(() => toast.classList.remove('show'), 2200);
            });
            button.dataset.connected = 'local';
            return;
        }

        if (button.matches('.tree-node') || button.closest('.tree-node')) {
            button.addEventListener('click', () => {
                button.classList.toggle('open');
                button.classList.toggle('active');
            });
            button.dataset.connected = 'local';
            return;
        }

        if (button.dataset.go && routes[button.dataset.go]) {
            button.addEventListener('click', () => navigate(routes[button.dataset.go]));
            button.dataset.connected = 'route';
        }
    });

    document.querySelectorAll(
        '.map-tabs button, .range button, .rating-input button, .insight-list button',
    ).forEach((button) => {
        if (button.dataset.connected) return;
        button.addEventListener('click', () => {
            button.parentElement?.querySelectorAll('button').forEach((item) => {
                item.classList.remove('active');
            });
            button.classList.add('active');
        });
    });
}


/* SignalGrid accessible control names */
const accessibleControlLabels = {
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
};

function normalizeIconName(value = '') {
    return value.toLowerCase().replace(/[^a-z0-9]/g, '');
}

function applyAccessibleControlNames(root = document) {
    root.querySelectorAll('button, [role="button"]').forEach((control, index) => {
        const visibleText = control.textContent?.trim();
        const existingName = control.getAttribute('aria-label') || control.getAttribute('title');
        if (visibleText || existingName) return;

        const icon = control.querySelector('[data-lucide]');
        const iconName = normalizeIconName(icon?.getAttribute('data-lucide'));
        const label = accessibleControlLabels[iconName]
            || control.dataset.actionLabel
            || control.dataset.tooltip
            || `Dashboard action ${index + 1}`;

        control.setAttribute('aria-label', label);
        if (!control.getAttribute('title')) control.setAttribute('title', label);
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => applyAccessibleControlNames(), { once: true });
} else {
    applyAccessibleControlNames();
}

const accessibleNameObserver = new MutationObserver((mutations) => {
    for (const mutation of mutations) {
        for (const node of mutation.addedNodes) {
            if (node.nodeType === Node.ELEMENT_NODE) applyAccessibleControlNames(node);
        }
    }
});
accessibleNameObserver.observe(document.documentElement, { childList: true, subtree: true });
