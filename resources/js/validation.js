const vc = window.validationConfig;
if (vc) {
    const $ = (selector) => document.querySelector(selector);
    const message = $('#validationMessage');
    const say = (text) => {
        if (message) message.textContent = text;
    };
    const runValidation = $('#runValidation');
    if (runValidation) {
        runValidation.onclick = async () => {
            const id = $('#validationSurvey')?.value;
            if (!id) {
                say('Select a survey.');
                return;
            }
            say('Running deterministic holdout validation...');
            const response = await fetch(`${vc.scientificBase}/${id}/validation`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': vc.csrf,
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    holdout_percent: Number($('#holdoutPercent')?.value),
                    power: Number($('#validationPower')?.value),
                }),
            });
            const data = await response.json();
            if (!response.ok) {
                say(data.message || Object.values(data.errors || {}).flat().join(' ') || 'Validation failed.');
                return;
            }
            location.reload();
        };
    }
    const runAcceptance = $('#runAcceptance');
    if (runAcceptance) {
        runAcceptance.onclick = async () => {
            say('Running operational acceptance audit...');
            const response = await fetch(vc.acceptanceUrl, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': vc.csrf, Accept: 'application/json' },
            });
            const data = await response.json();
            if (!response.ok) {
                say(data.message || 'Audit failed.');
                return;
            }
            location.reload();
        };
    }
}
