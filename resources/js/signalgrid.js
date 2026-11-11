lucide.createIcons();
const $ = (s) => document.querySelector(s);
const $$ = (s) => document.querySelectorAll(s);

const crumb = $('#pageCrumb');
const sidebar = $('#sidebar');
const scrim = $('#scrim');
const navTo = (id) => {
    $$('.page').forEach((p) => p.classList.toggle('active', p.id === id));
    $$('.nav').forEach((n) => n.classList.toggle('active', n.dataset.page === id));
    if (crumb) {
        crumb.textContent = ({
            overview: 'Network overview',
            heatmap: 'Coverage map',
            measurements: 'Measurements',
            feedback: 'User feedback',
            reports: 'Reports',
            locations: 'Locations',
            settings: 'Settings',
            surveys: 'RF survey studio',
            sources: 'Data sources',
            load: 'AP load analysis',
            insights: 'Explainable insights',
        })[id] || crumb.textContent;
    }
    window.scrollTo({ top: 0, behavior: 'smooth' });
    sidebar?.classList.remove('open');
    scrim?.classList.remove('show');
};
$$('[data-page]').forEach((b) => b.addEventListener('click', () => navTo(b.dataset.page)));
const menu = $('#menu');
if (menu && sidebar && scrim) {
    menu.setAttribute('aria-controls', 'sidebar');
    const setDashboardNav = (open) => {
        sidebar.classList.toggle('open', open);
        scrim.classList.toggle('show', open);
        menu.setAttribute('aria-expanded', String(open));
        menu.setAttribute('aria-label', open ? 'Close navigation' : 'Open navigation');
    };
    menu.onclick = () => setDashboardNav(!sidebar.classList.contains('open'));
    scrim.onclick = () => setDashboardNav(false);
}

const ctx = $('#performanceChart');
if (ctx && !ctx.hidden && window.Chart) {
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [
                { label: 'Download', data: [], borderColor: '#18bfa9', backgroundColor: 'rgba(24,191,169,.08)', fill: true, tension: .42, borderWidth: 2, pointRadius: 0 },
                { label: 'Upload', data: [], borderColor: '#4f83e7', tension: .42, borderWidth: 2, pointRadius: 0 },
                { label: 'Latency', data: [], borderColor: '#9069e8', tension: .42, borderWidth: 2, pointRadius: 0 },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false }, ticks: { color: '#91a0ae', font: { size: 9 } } },
                y: { grid: { color: '#eef2f5' }, border: { display: false }, ticks: { color: '#91a0ae', font: { size: 9 } } },
            },
        },
    });
}

const measurementRows = $('#measurementRows');
if (measurementRows) {
    const rows = [
        ['Main Library', 'Main Reading Hall', 'CampusNet-5G', '-48 dBm', '62.4 Mbps', '24 ms', '0.2%', 'Excellent', '2 min ago'],
        ['Student Centre', 'East Wing', 'CampusNet', '-78 dBm', '8.7 Mbps', '91 ms', '7.0%', 'Poor', '11 min ago'],
        ['Science Complex', 'Chemistry Lab', 'Eduroam', '-61 dBm', '44.1 Mbps', '35 ms', '1.1%', 'Good', '18 min ago'],
        ['Lecture Theatre A', 'Back row', 'CampusNet-5G', '-69 dBm', '25.8 Mbps', '52 ms', '2.8%', 'Fair', '26 min ago'],
        ['Engineering Block', 'Floor 2 corridor', 'Eduroam', '-57 dBm', '51.3 Mbps', '29 ms', '0.5%', 'Excellent', '41 min ago'],
    ];
    measurementRows.innerHTML = rows.map((r) => `<tr><td><strong>${r[0]}</strong><small>${r[1]}</small></td><td>${r[2]}</td><td>${r[3]}</td><td>${r[4]}</td><td>${r[5]}</td><td>${r[6]}</td><td><span class="pill ${r[7] === 'Poor' ? 'poor' : r[7] === 'Fair' ? 'fair' : 'good'}">${r[7]}</span></td><td>${r[8]}</td></tr>`).join('');
}

const responseHost = $('#responses');
if (responseHost) {
    const feedback = [
        ['MO', 'Maya O.', 'Main Library', '5', 'The connection was fast and stable throughout my research session.', '6 min ago'],
        ['TA', 'Tunde A.', 'Student Centre', '2', 'Video calls kept freezing near the east-wing seating area.', '32 min ago'],
        ['CI', 'Chidi I.', 'Science Complex', '4', 'Good speed in the lab. One short disconnection during upload.', '1 hour ago'],
    ];
    responseHost.innerHTML = feedback.map((x) => `<div class="response"><div class="avatar">${x[0]}</div><div><strong>${x[1]}</strong> <small>· ${x[2]} · ${x[5]}</small><div class="stars">${'★'.repeat(+x[3])}${'☆'.repeat(5 - x[3])}</div><p>${x[4]}</p></div></div>`).join('');
}

const locationCards = $('#locationCards');
if (locationCards) {
    const locationsUrl = window.signalGridUiRoutes?.locations || '/locations';
    const locs = [
        ['library', 'Main Library', '4 floors · 12 zones', '89% coverage'],
        ['flask-conical', 'Science Complex', '3 floors · 9 zones', '82% coverage'],
        ['graduation-cap', 'Lecture Theatre A', '1 floor · 4 zones', '76% coverage'],
        ['utensils', 'Student Centre', '2 floors · 7 zones', '64% coverage'],
        ['cpu', 'Engineering Block', '4 floors · 14 zones', '85% coverage'],
        ['building-2', 'Administration', '3 floors · 8 zones', '91% coverage'],
    ];
    locationCards.innerHTML = locs.map((x) => `<article class="location-card"><div><span class="loc-icon"><i data-lucide="${x[0]}"></i></span></div><h3>${x[1]}</h3><p>${x[2]}</p><footer><span>${x[3]}</span><a href="${locationsUrl}">View details →</a></footer></article>`).join('');
}

const modal = $('#testModal');
$$('[data-open="test"]').forEach((b) => { b.onclick = () => modal?.classList.add('open'); });
$$('.modal-close').forEach((button) => {
    if (!button.getAttribute('aria-label')) button.setAttribute('aria-label', 'Close');
    button.onclick = () => button.closest('.modal')?.classList.remove('open');
});
if (modal) {
    modal.onclick = (e) => { if (e.target === modal) modal.classList.remove('open'); };
}
const runTest = $('.run-test');
if (runTest && modal) {
    runTest.onclick = () => {
        $('.test-fields')?.classList.add('hidden');
        $('.test-progress')?.classList.remove('hidden');
        runTest.classList.add('hidden');
        setTimeout(() => {
            modal.classList.remove('open');
            $('.test-fields')?.classList.remove('hidden');
            $('.test-progress')?.classList.add('hidden');
            runTest.classList.remove('hidden');
            const toast = $('#toast');
            const title = toast?.querySelector('b');
            const detail = toast?.querySelector('span');
            if (title) title.textContent = 'Browser check finished';
            if (detail) detail.textContent = 'This preview is not saved. Open a survey to record a measurement.';
            toast?.classList.add('show');
            setTimeout(() => toast?.classList.remove('show'), 3000);
        }, 1800);
    };
}

$$('.range button,.map-tabs button').forEach((b) => {
    b.onclick = () => {
        [...b.parentNode.children].forEach((x) => x.classList.remove('active'));
        b.classList.add('active');
    };
});

const bannerClose = $('#bannerClose');
if (bannerClose) bannerClose.onclick = () => bannerClose.closest('.prototype-banner')?.remove();

const uploadFloor = $('#uploadFloor');
const floorInput = $('#floorInput');
if (uploadFloor && floorInput) {
    uploadFloor.onclick = () => floorInput.click();
    floorInput.onchange = () => {
        if (floorInput.files.length) {
            const title = uploadFloor.querySelector('b');
            const detail = uploadFloor.querySelector('span');
            if (title) title.textContent = floorInput.files[0].name;
            if (detail) detail.textContent = 'Ready for calibration';
        }
    };
}

const samplePlan = $('#samplePlan');
if (samplePlan) {
    samplePlan.onclick = () => {
        const canvas = $('#surveyCanvas');
        if (canvas) canvas.innerHTML = '<div class="sample-floor"><i class="survey-point"></i><i class="survey-point imported"></i><i class="survey-point"></i><i class="survey-point imported"></i></div>';
    };
}
$$('.canvas-tools [data-tool]').forEach((b) => {
    b.onclick = () => {
        $$('.canvas-tools [data-tool]').forEach((x) => x.classList.remove('active'));
        b.classList.add('active');
    };
});
$$('.rating-input button').forEach((b) => {
    b.onclick = (e) => {
        e.preventDefault();
        $$('.rating-input button').forEach((x) => x.classList.toggle('active', +x.textContent <= +b.textContent));
    };
});

const importModal = $('#importModal');
const importData = $('#importData');
if (importData && importModal) {
    importData.onclick = () => importModal.classList.add('open');
    importModal.onclick = (e) => { if (e.target === importModal) importModal.classList.remove('open'); };
}

$$('.measurement-state-tabs button').forEach((btn) => {
    btn.onclick = () => {
        $$('.measurement-state-tabs button').forEach((x) => x.classList.remove('active'));
        btn.classList.add('active');
        const state = btn.dataset.state;
        const preview = $('#measurementStatePreview');
        const table = $('#measurementTable');
        if (!preview || !table) return;
        if (state === 'records') {
            preview.classList.remove('show');
            table.style.display = 'block';
            return;
        }
        table.style.display = 'none';
        preview.classList.add('show');
        const states = {
            loading: ['loader-circle', 'Loading measurements', 'Retrieving records for the selected location and period.'],
            empty: ['inbox', 'No measurements found', 'Change the date range or create a new survey.'],
            error: ['circle-alert', 'Could not load measurements', 'Check the connection and try again.'],
        };
        const s = states[state];
        preview.innerHTML = `<i data-lucide="${s[0]}" class="state-preview-icon ${state === 'error' ? 'error' : ''}"></i><b>${s[1]}</b><span>${s[2]}</span>${state === 'error' ? '<button class="secondary" type="button" data-state="records" style="margin-top:10px">Retry</button>' : ''}`;
        lucide.createIcons();
        preview.querySelector('button')?.addEventListener('click', () => $$('.measurement-state-tabs button')[0]?.click());
    };
});

const applyFilters = $('#applyFilters');
if (applyFilters) {
    applyFilters.addEventListener('click', () => {
        const toast = $('#toast');
        const title = toast?.querySelector('b');
        const detail = toast?.querySelector('span');
        if (title) title.textContent = 'Filters applied';
        if (detail) detail.textContent = 'Dashboard metrics now use the selected period, floor and source.';
        toast?.classList.add('show');
        setTimeout(() => toast?.classList.remove('show'), 2500);
    });
}

let canvasScale = 1;
const surveyCanvas = $('#surveyCanvas');
if (surveyCanvas) {
    const zoomBtns = $$('.canvas-tools div:last-child button');
    const setCanvasZoom = (delta) => {
        canvasScale = Math.max(.6, Math.min(2, canvasScale + delta));
        const floor = surveyCanvas.querySelector('.sample-floor');
        if (floor) floor.style.transform = `scale(${canvasScale})`;
        const label = $('.canvas-tools div:last-child span');
        if (label) label.textContent = `${Math.round(canvasScale * 100)}%`;
    };
    if (zoomBtns.length === 2) {
        zoomBtns[0].onclick = () => setCanvasZoom(-.1);
        zoomBtns[1].onclick = () => setCanvasZoom(.1);
    }
}
$$('.tip').forEach((x) => x.setAttribute('aria-label', x.dataset.tip || 'Metric explanation'));
lucide.createIcons();
