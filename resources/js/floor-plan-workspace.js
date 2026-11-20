const config = window.floorPlanConfig;

if (config) {
    const $ = (selector) => document.querySelector(selector);
    const canvas = $('#fpCanvas');
    const image = $('#fpImage');
    const pdfFrame = $('#fpPdf');
    const calibrationLine = $('#calibrationLine');
    const message = $('#message');

    let selectedPlan = null;
    let calibrationPoints = [];
    let scale = 1;
    let boundaryMode = false;
    let boundaryPoints = [];

    const apiRequest = async (url, options = {}) => {
        const response = await fetch(url, {
            ...options,
            headers: {
                'X-CSRF-TOKEN': config.csrf,
                Accept: 'application/json',
                ...(options.headers || {}),
            },
        });

        const raw = response.status === 204 ? '' : await response.text();
        let data = null;

        if (raw) {
            try {
                data = JSON.parse(raw);
            } catch {
                data = null;
            }
        }

        if (!response.ok) {
            const validationMessage = data?.errors
                ? Object.values(data.errors).flat()[0]
                : null;

            throw new Error(validationMessage || data?.message || 'The floor plan could not be uploaded.');
        }

        return data;
    };

    const setMessage = (text, isError = false) => {
        message.textContent = text;
        message.classList.toggle('error', isError);
    };

    const renderCalibrationPoints = () => {
        if (calibrationPoints.length === 0) {
            calibrationLine.hidden = true;
            return;
        }

        calibrationLine.hidden = false;
        const pointA = $('#pointA');
        const pointB = $('#pointB');

        pointA.style.left = `${calibrationPoints[0].x * 100}%`;
        pointA.style.top = `${calibrationPoints[0].y * 100}%`;

        if (calibrationPoints[1]) {
            pointB.hidden = false;
            pointB.style.left = `${calibrationPoints[1].x * 100}%`;
            pointB.style.top = `${calibrationPoints[1].y * 100}%`;
        } else {
            pointB.hidden = true;
        }
    };

    const clearBoundaryDots = () => {
        document.querySelectorAll('.boundary-dot').forEach((dot) => dot.remove());
    };

    document.querySelectorAll('.fp-version').forEach((button) => {
        button.addEventListener('click', () => {
            document.querySelectorAll('.fp-version').forEach((item) => {
                item.classList.remove('selected');
            });

            button.classList.add('selected');
            selectedPlan = JSON.parse(button.dataset.plan);
            const isPdf = String(selectedPlan.mime_type || '').includes('pdf');
            image.hidden = isPdf;
            pdfFrame.hidden = !isPdf;
            if (isPdf) {
                pdfFrame.src = button.dataset.file;
            } else {
                image.src = button.dataset.file;
            }
            $('.fp-placeholder').hidden = true;
            $('#ppmValue').textContent = selectedPlan.pixels_per_metre
                ? Number(selectedPlan.pixels_per_metre).toFixed(2)
                : 'Not calibrated';

            calibrationPoints = [];
            boundaryPoints = [];
            calibrationLine.hidden = true;
            clearBoundaryDots();
            setMessage(`Version ${selectedPlan.version} selected.`);
        });
    });

    $('#uploadForm').addEventListener('submit', async (event) => {
        event.preventDefault();

        try {
            setMessage('Uploading floor plan...');
            await apiRequest(config.uploadUrl, {
                method: 'POST',
                body: new FormData(event.currentTarget),
            });
            window.location.reload();
        } catch (error) {
            setMessage(error.message, true);
        }
    });

    canvas.addEventListener('click', (event) => {
        if (!selectedPlan || image.hidden) return;

        const imageBounds = image.getBoundingClientRect();
        const x = (event.clientX - imageBounds.left) / imageBounds.width;
        const y = (event.clientY - imageBounds.top) / imageBounds.height;

        if (x < 0 || x > 1 || y < 0 || y > 1) return;

        const point = { x, y };

        if (boundaryMode) {
            boundaryPoints.push(point);
            const dot = document.createElement('span');
            dot.className = 'boundary-dot';
            dot.style.left = `${x * 100}%`;
            dot.style.top = `${y * 100}%`;
            canvas.appendChild(dot);
            $('#saveBoundary').disabled = boundaryPoints.length < 3;
            setMessage(`${boundaryPoints.length} boundary point(s) selected.`);
            return;
        }

        if (calibrationPoints.length === 2) calibrationPoints = [];
        calibrationPoints.push(point);
        renderCalibrationPoints();
        setMessage(`${calibrationPoints.length} of 2 calibration points selected.`);
    });

    $('#saveCalibration').addEventListener('click', async () => {
        const distance = Number($('#distanceMetres').value);

        if (!selectedPlan || calibrationPoints.length !== 2) {
            setMessage('Select two calibration points first.', true);
            return;
        }

        if (!Number.isFinite(distance) || distance < 0.1) {
            setMessage('Enter a valid known distance of at least 0.1 metre.', true);
            return;
        }

        try {
            const savedPlan = await apiRequest(
                `/api/floor-plans/${selectedPlan.id}/calibrate`,
                {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        x1: calibrationPoints[0].x,
                        y1: calibrationPoints[0].y,
                        x2: calibrationPoints[1].x,
                        y2: calibrationPoints[1].y,
                        distance_metres: distance,
                    }),
                },
            );

            selectedPlan = savedPlan;
            $('#ppmValue').textContent = Number(savedPlan.pixels_per_metre).toFixed(2);
            setMessage('Calibration saved successfully.');
        } catch (error) {
            setMessage(error.message, true);
        }
    });

    $('#boundaryMode').addEventListener('click', () => {
        if (!selectedPlan) {
            setMessage('Select a floor-plan version first.', true);
            return;
        }

        boundaryMode = !boundaryMode;
        boundaryPoints = [];
        clearBoundaryDots();
        $('#boundaryMode').textContent = boundaryMode
            ? 'Boundary mode active'
            : 'Start boundary drawing';
        $('#saveBoundary').disabled = true;
        setMessage(boundaryMode
            ? 'Click at least three points around the usable floor area.'
            : 'Boundary drawing cancelled.');
    });

    $('#saveBoundary').addEventListener('click', async () => {
        if (!selectedPlan || boundaryPoints.length < 3) {
            setMessage('Select at least three boundary points.', true);
            return;
        }

        try {
            await apiRequest(`/api/floor-plans/${selectedPlan.id}/boundary`, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ points: boundaryPoints }),
            });

            boundaryMode = false;
            $('#boundaryMode').textContent = 'Start boundary drawing';
            setMessage('Usable boundary saved successfully.');
        } catch (error) {
            setMessage(error.message, true);
        }
    });

    const applyZoom = (change) => {
        scale = Math.max(0.5, Math.min(2.5, scale + change));
        image.style.transform = `scale(${scale})`;
        pdfFrame.style.transform = `scale(${scale})`;
        $('#zoomLabel').textContent = `${Math.round(scale * 100)}%`;
    };

    $('#zoomIn').addEventListener('click', () => applyZoom(0.1));
    $('#zoomOut').addEventListener('click', () => applyZoom(-0.1));
    $('#resetView').addEventListener('click', () => {
        scale = 1;
        image.style.transform = 'scale(1)';
        pdfFrame.style.transform = 'scale(1)';
        $('#zoomLabel').textContent = '100%';
    });
}
