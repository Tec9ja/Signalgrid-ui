const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || '';

async function send(url, method = 'POST', body = null, asForm = false) {
    const headers = { Accept: 'application/json', 'X-CSRF-TOKEN': csrf() };
    const options = { method, headers };
    if (body && method !== 'GET') {
        if (asForm) {
            options.body = body;
        } else {
            headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify(body);
        }
    }
    const response = await fetch(url, options);
    const data = await response.json().catch(() => ({}));
    if (!response.ok) {
        const detail = data.message || Object.values(data.errors || {}).flat()[0] || 'Request failed.';
        throw new Error(detail);
    }
    return data;
}

function formBody(form) {
    const body = {};
    new FormData(form).forEach((value, key) => {
        if (value !== '') body[key] = value;
    });
    return body;
}

document.querySelectorAll('form[data-json]').forEach((form) => {
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const message = form.querySelector('[data-form-message]');
        if (message) message.textContent = 'Saving…';
        try {
            await send(form.dataset.json, form.dataset.method || 'POST', formBody(form));
            if (form.dataset.reload === 'false') {
                if (message) message.textContent = 'Saved.';
                return;
            }
            window.location.reload();
        } catch (error) {
            if (message) message.textContent = error.message;
        }
    });
});

document.querySelectorAll('[data-review]').forEach((control) => {
    control.addEventListener('change', async () => {
        const message = document.querySelector(control.dataset.message || '');
        const allowed = ['reviewed', 'investigating', 'resolved', 'dismissed'];
        if (!allowed.includes(control.value)) return;
        try {
            await send(control.dataset.review, 'PATCH', { status: control.value });
            if (message) message.textContent = 'Insight status updated.';
            else window.location.reload();
        } catch (error) {
            if (message) message.textContent = error.message;
        }
    });
});

document.querySelectorAll('[data-correlate]').forEach((button) => {
    button.addEventListener('click', async () => {
        const message = document.querySelector('#feedbackMessage');
        button.disabled = true;
        try {
            const data = await send(button.dataset.correlate, 'POST', {});
            if (message) message.textContent = `Correlation finished. ${Array.isArray(data) ? data.length : ''} evidence link(s) recorded.`;
            window.location.reload();
        } catch (error) {
            if (message) message.textContent = error.message;
            button.disabled = false;
        }
    });
});

const cascade = document.querySelector('[data-location-cascade]');
if (cascade) {
    const campus = cascade.querySelector('[name="campus_id"]');
    const building = cascade.querySelector('[name="building_id"]');
    const floor = cascade.querySelector('[name="floor_id"]');
    const area = cascade.querySelector('[name="survey_area_id"]');
    const filter = (select, attribute, value) => {
        if (!select) return;
        [...select.options].forEach((option) => {
            if (!option.value) {
                option.hidden = false;
                return;
            }
            option.hidden = value ? option.dataset[attribute] !== String(value) : true;
        });
        if (select.selectedOptions[0]?.hidden) select.value = '';
    };
    const sync = () => {
        filter(building, 'campus', campus?.value);
        filter(floor, 'building', building?.value);
        filter(area, 'floor', floor?.value);
    };
    campus?.addEventListener('change', () => {
        if (building) building.value = '';
        if (floor) floor.value = '';
        if (area) area.value = '';
        sync();
    });
    building?.addEventListener('change', () => {
        if (floor) floor.value = '';
        if (area) area.value = '';
        sync();
    });
    floor?.addEventListener('change', () => {
        if (area) area.value = '';
        sync();
    });
    sync();
}

const notifyButton = document.querySelector('#openNotifications');
const notifyPanel = document.querySelector('#notifyPanel');
const notifyList = document.querySelector('#notifyList');
if (notifyButton && notifyPanel && window.signalGridUiRoutes?.notifications) {
    const render = (items) => {
        const dot = document.querySelector('#notifyDot');
        const unread = items.filter((item) => !item.read_at).length;
        if (dot) dot.hidden = unread === 0;
        if (!items.length) {
            notifyList.innerHTML = '<p>No notifications yet.</p>';
            return;
        }
        const esc = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[char]));
        notifyList.innerHTML = items.map((item) => {
            const title = esc(item.data?.title || 'SignalGrid update');
            const message = esc(item.data?.message || '');
            const href = esc(item.data?.url || '#');
            return `<a class="notify-item" href="${href}" data-id="${esc(item.id)}"><b>${title}</b><span>${message}</span></a>`;
        }).join('');
    };
    const load = async () => {
        try {
            const items = await send(window.signalGridUiRoutes.notifications, 'GET');
            render(Array.isArray(items) ? items : []);
        } catch (error) {
            notifyList.textContent = error.message;
        }
    };
    notifyButton.addEventListener('click', () => {
        const open = notifyPanel.hasAttribute('hidden');
        notifyPanel.toggleAttribute('hidden', !open);
        notifyButton.setAttribute('aria-expanded', String(open));
        if (open) load();
    });
    notifyList.addEventListener('click', async (event) => {
        const link = event.target.closest('[data-id]');
        if (!link) return;
        const id = link.dataset.id;
        try {
            await send(`${window.signalGridUiRoutes.notificationReadBase}/${id}/read`, 'PATCH', {});
        } catch {
            // Opening the linked record still proceeds.
        }
    });
    load();
}

document.querySelectorAll('[data-preference]').forEach((button) => {
    button.addEventListener('click', async () => {
        const row = button.closest('[data-event]');
        const message = document.querySelector('#settingsMessage');
        try {
            await send(button.dataset.preference, 'PUT', {
                event_key: row.dataset.event,
                database_enabled: row.querySelector('[data-database]').checked,
                mail_enabled: row.querySelector('[data-mail]').checked,
            });
            if (message) message.textContent = 'Notification preference saved.';
        } catch (error) {
            if (message) message.textContent = error.message;
        }
    });
});

const floorSelect = document.querySelector('#surveyFloor');
const planSelect = document.querySelector('#surveyPlan');
const areaSelect = document.querySelector('#surveyArea');
if (floorSelect && planSelect && window.surveyCreateFloors) {
    const fill = () => {
        const floor = window.surveyCreateFloors.find((item) => String(item.id) === floorSelect.value);
        planSelect.innerHTML = '<option value="">Select floor plan</option>';
        areaSelect.innerHTML = '<option value="">Whole floor</option>';
        (floor?.plans || []).forEach((plan) => {
            const option = document.createElement('option');
            option.value = String(plan.id);
            option.textContent = `Version ${plan.version} · ${plan.name || 'Floor plan'}`;
            planSelect.appendChild(option);
        });
        (floor?.areas || []).forEach((area) => {
            const option = document.createElement('option');
            option.value = String(area.id);
            option.textContent = area.name || 'Survey area';
            areaSelect.appendChild(option);
        });
        if (floor?.plans?.length === 1) planSelect.value = String(floor.plans[0].id);
    };
    floorSelect.addEventListener('change', fill);
    fill();
}
