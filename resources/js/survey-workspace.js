const sc = window.surveyConfig;
if (sc) {
    const $ = (s) => document.querySelector(s);
    const map = $('.survey-map');
    const image = $('#surveyFloorImage');
    const message = $('#surveyMessage');
    let pending = null;
    let selectedUrl = null;

    const api = async (url, method = 'POST', body = null) => {
        const headers = { 'X-CSRF-TOKEN': sc.csrf, Accept: 'application/json' };
        const options = { method, headers };
        if (body && method !== 'GET') {
            headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify(body);
        }
        const response = await fetch(url, options);
        const data = await response.json().catch(() => null);
        if (!response.ok) throw new Error(data?.message || Object.values(data?.errors || {}).flat()?.[0] || 'Request failed');
        return data;
    };

    const say = (text) => { if (message) message.textContent = text; };

    map?.addEventListener('click', (event) => {
        if (event.target.classList.contains('survey-point')) return;
        if (!image) return;
        const rect = image.getBoundingClientRect();
        const x = (event.clientX - rect.left) / rect.width;
        const y = (event.clientY - rect.top) / rect.height;
        if (x < 0 || x > 1 || y < 0 || y > 1) return;
        pending = { x_normalized: x, y_normalized: y };
        const pendingLabel = $('#pendingPoint');
        if (pendingLabel) pendingLabel.textContent = `X ${x.toFixed(4)}, Y ${y.toFixed(4)}`;
    });

    document.querySelectorAll('.survey-point').forEach((point) => {
        point.addEventListener('click', (event) => {
            event.stopPropagation();
            document.querySelectorAll('.survey-point').forEach((item) => item.classList.remove('is-selected'));
            point.classList.add('is-selected');
            selectedUrl = point.dataset.measurementUrl || null;
            const label = $('#selectedPointLabel');
            if (label) label.textContent = `Point ${point.dataset.pointId} selected`;
        });
    });

    $('#savePoint')?.addEventListener('click', async () => {
        if (!pending) {
            say('Select a point on the floor plan.');
            return;
        }
        try {
            const source = $('#pointSource')?.value || 'measured';
            await api(sc.pointUrl, 'POST', {
                ...pending,
                source_type: source,
                collection_method: source === 'measured' ? 'manual' : 'import',
                notes: $('#pointNotes')?.value || null,
            });
            window.location.reload();
        } catch (error) {
            say(error.message);
        }
    });

    $('#measurementForm')?.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (!selectedUrl) {
            say('Select a saved point before recording a measurement.');
            return;
        }
        const numberOrNull = (id) => {
            const value = $(id)?.value;
            return value === '' || value == null ? null : Number(value);
        };
        try {
            await api(selectedUrl, 'POST', {
                ssid: $('#measureSsid')?.value || null,
                rssi_dbm: numberOrNull('#measureRssi'),
                download_mbps: numberOrNull('#measureDownload'),
                upload_mbps: numberOrNull('#measureUpload'),
                latency_ms: numberOrNull('#measureLatency'),
                packet_loss_percent: numberOrNull('#measureLoss'),
                source_type: 'measured',
                collection_method: 'manual',
            });
            say('Measurement saved.');
            window.location.reload();
        } catch (error) {
            say(error.message);
        }
    });

    $('#importForm')?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const file = $('#importFile')?.files?.[0];
        if (!file) {
            say('Choose a CSV file first.');
            return;
        }
        const body = new FormData();
        body.append('file', file);
        try {
            const response = await fetch(sc.importUrl, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': sc.csrf, Accept: 'application/json' },
                body,
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(data.message || 'Import failed.');
            say(`Import ${data.status || 'finished'}: ${data.accepted_rows ?? 0} accepted, ${data.rejected_rows ?? 0} rejected.`);
            if ((data.accepted_rows ?? 0) > 0) window.location.reload();
        } catch (error) {
            say(error.message);
        }
    });

    $('#startSurvey')?.addEventListener('click', async () => {
        try {
            await api(sc.startUrl);
            window.location.reload();
        } catch (error) {
            say(error.message);
        }
    });
    $('#validateSurvey')?.addEventListener('click', async () => {
        try {
            const data = await api(sc.validateUrl, 'GET');
            say(data.issues?.length ? data.issues.join(' ') : `Survey is valid with ${data.valid} points.`);
        } catch (error) {
            say(error.message);
        }
    });
    $('#completeSurvey')?.addEventListener('click', async () => {
        try {
            await api(sc.completeUrl);
            window.location.reload();
        } catch (error) {
            say(error.message);
        }
    });
}
