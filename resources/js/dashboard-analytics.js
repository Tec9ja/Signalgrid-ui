const ac = window.signalGridAnalytics;
if (ac) {
    const $ = (s) => document.querySelector(s);
    const set = (selector, value, suffix = '') => {
        const el = $(selector);
        if (!el) return;
        const empty = value === null || value === undefined || value === '';
        const main = el.childNodes[0];
        const metric = el.matches('.metric h2');
        if (main) main.textContent = empty ? (metric ? 'No data' : '—') : value;
        const span = el.querySelector('span');
        if (span) {
            span.textContent = suffix;
            if (metric) span.hidden = empty;
        }
        if (metric) el.classList.toggle('is-empty', empty);
    };
    const isoDaysAgo = (days) => {
        const date = new Date();
        date.setDate(date.getDate() - days);
        return date.toISOString();
    };
    const load = async () => {
        const params = new URLSearchParams();
        const source = $('#sourceFilter')?.value?.toLowerCase().split(' ')[0] || 'all';
        params.set('source', source === 'all' ? 'all' : source);
        const range = $('#dateFilter')?.value || '7';
        if (range === 'custom') {
            if ($('#customFrom')?.value) params.set('from', new Date($('#customFrom').value).toISOString());
            if ($('#customTo')?.value) params.set('to', new Date(`${$('#customTo').value}T23:59:59`).toISOString());
        } else {
            params.set('from', isoDaysAgo(Number(range) || 7));
            params.set('to', new Date().toISOString());
        }
        const floor = $('#locationFilter')?.value;
        if (floor && floor !== 'all') params.set('floor_id', floor);
        const response = await fetch(`${ac.url}?${params}`, { headers: { Accept: 'application/json' } });
        const data = await response.json();
        if (!response.ok) return;
        set('.metrics .metric:nth-child(1) h2', data.coverage.value !== null ? Number(data.coverage.value).toFixed(0) : null, '%');
        set('.metrics .metric:nth-child(2) h2', data.performance.median_download_mbps !== null ? Number(data.performance.median_download_mbps).toFixed(1) : null, ' Mbps');
        set('.metrics .metric:nth-child(3) h2', data.performance.median_latency_ms !== null ? Number(data.performance.median_latency_ms).toFixed(0) : null, ' ms');
        set('.metrics .metric:nth-child(4) h2', data.counts?.real ?? null, '');
        set('.health .gauge strong', data.health.value !== null ? Number(data.health.value).toFixed(0) : null);
        const sideHealth = document.querySelector('#sideHealthValue');
        if (sideHealth) sideHealth.textContent = data.health.value !== null ? Number(data.health.value).toFixed(0) : '—';
        const liveLabel = document.querySelector('#healthLiveLabel');
        if (liveLabel) {
            const ready = data.health.value !== null;
            liveLabel.textContent = ready ? 'Live' : 'Waiting';
            liveLabel.closest('.live')?.classList.toggle('is-waiting', !ready);
        }
        const recordsNote = document.querySelector('.metrics .metric:nth-child(4) p');
        if (recordsNote) {
            const measured = data.provenance?.measured ?? 0;
            const imported = data.provenance?.imported ?? 0;
            recordsNote.textContent = `${measured} measured, ${imported} imported. ${data.counts?.simulated ?? 0} simulated records are excluded from scores.`;
        }
        const trendNotes = document.querySelectorAll('.trend-cards small');
        const format = (value, suffix) => (value === null || value === undefined ? 'No samples in this period' : `${Number(value).toFixed(1)} ${suffix}`);
        if (trendNotes[0]) trendNotes[0].textContent = format(data.performance.median_download_mbps, 'Mbps median');
        if (trendNotes[1]) trendNotes[1].textContent = format(data.performance.median_latency_ms, 'ms median');
        if (trendNotes[2]) trendNotes[2].textContent = format(data.performance.median_packet_loss_percent, '% median');
        const label = (score) => (score === null || score === undefined ? '—' : score >= 80 ? 'Strong' : score >= 50 ? 'Fair' : 'Weak');
        const healthValues = document.querySelectorAll('.health-list b');
        if (healthValues[0]) healthValues[0].textContent = label(data.health.components?.coverage);
        if (healthValues[1]) healthValues[1].textContent = label(data.health.components?.stability);
        if (healthValues[2]) healthValues[2].textContent = label(data.health.components?.ap_load);
        const coverageBar = document.querySelector('.metrics .metric:nth-child(1) .bar i');
        if (coverageBar && data.coverage.value !== null) coverageBar.style.width = `${Math.max(0, Math.min(100, Number(data.coverage.value)))}%`;
        const coverageNote = document.querySelector('.metrics .metric:nth-child(1) p');
        if (coverageNote) {
            if (data.coverage?.definition) {
                const estimated = data.coverage.estimated_area_percent ?? '—';
                const unmeasured = data.coverage.unmeasured_area_percent ?? '—';
                coverageNote.textContent = `Good-signal coverage of the usable floor. Estimated ${estimated}%. Unmeasured ${unmeasured}%.`;
            } else if (data.coverage?.value !== null && data.coverage?.value !== undefined) {
                coverageNote.textContent = 'This figure is from an earlier heat map. Generate it again to separate good-signal coverage from unmeasured area.';
            } else {
                coverageNote.textContent = `${data.counts.real} measured records in this period`;
            }
        }
        const downloadNote = document.querySelector('.metrics .metric:nth-child(2) p');
        if (downloadNote && data.performance.median_upload_mbps !== null) downloadNote.textContent = `Median upload ${Number(data.performance.median_upload_mbps).toFixed(1)} Mbps`;
        const note = $('.health .center');
        if (note && data.health.limitation) note.textContent = data.health.limitation;
        else if (note && data.health.value !== null) note.textContent = 'Health score weights coverage 30%, throughput 20%, latency 15%, packet loss 15%, stability 10% and AP load 10%. Missing parts are not reweighted.';
    };
    const syncCustomRange = () => {
        const custom = $('#dateFilter')?.value === 'custom';
        ['customFrom', 'customTo'].forEach((id) => {
            const field = document.getElementById(id);
            if (field) field.hidden = !custom;
        });
    };
    $('#dateFilter')?.addEventListener('change', syncCustomRange);
    $('#applyFilters')?.addEventListener('click', load);
    syncCustomRange();
    load();
}
