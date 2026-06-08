function initWebcamPreview() {
    const video = document.getElementById('webcam');
    const startButton = document.getElementById('start-camera');
    const stopButton = document.getElementById('stop-camera');
    const captureButton = document.getElementById('capture-photo');
    const statusText = document.getElementById('status');
    const csrfToken = document.getElementById('capture-csrf-token');
    const overlayPreview = document.getElementById('selected-overlay-preview');
    const overlayOptions = document.querySelectorAll('input[name="overlay"]');
    const previousPictures = document.getElementById('previous-pictures');

    if (!video || !startButton || !stopButton || !captureButton || !statusText || !csrfToken) {
        return;
    }

    let cameraStream = null;
    let selectedOverlay = document.querySelector('input[name="overlay"]:checked')?.value || '';

    async function startCamera() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            statusText.textContent = 'Your browser does not support webcam access.';
            return;
        }

        try {
            cameraStream = await navigator.mediaDevices.getUserMedia({
                video: true,
                audio: false
            });

            video.srcObject = cameraStream;
            startButton.disabled = true;
            stopButton.disabled = false;
            captureButton.disabled = false;
            statusText.textContent = 'Camera preview is active.';
        } catch (error) {
            statusText.textContent = 'Camera access was blocked or no camera was found.';
            console.error('Unable to start camera:', error);
        }
    }

    function stopCamera() {
        if (!cameraStream) {
            return;
        }

        cameraStream.getTracks().forEach((track) => track.stop());
        cameraStream = null;
        video.srcObject = null;
        startButton.disabled = false;
        stopButton.disabled = true;
        captureButton.disabled = true;
        statusText.textContent = 'Camera is off.';
    }

    function loadOverlay(src) {
        return new Promise((resolve, reject) => {
            if (!src) {
                resolve(null);
                return;
            }

            const image = new Image();
            image.onload = () => resolve(image);
            image.onerror = reject;
            image.src = src;
        });
    }

    async function capturePhoto() {
        if (!cameraStream || video.videoWidth === 0 || video.videoHeight === 0) {
            statusText.textContent = 'Start the camera before capturing.';
            return;
        }

        captureButton.disabled = true;
        statusText.textContent = 'Saving picture...';

        const canvas = document.createElement('canvas');
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;

        const context = canvas.getContext('2d');

        context.save();
        context.translate(canvas.width, 0);
        context.scale(-1, 1);
        context.drawImage(video, 0, 0, canvas.width, canvas.height);
        context.restore();

        try {
            const overlay = await loadOverlay(selectedOverlay);

            if (overlay) {
                context.drawImage(overlay, 0, 0, canvas.width, canvas.height);
            }

            const body = new URLSearchParams();
            body.set('csrf_token', csrfToken.value);
            body.set('image', canvas.toDataURL('image/jpeg', 0.92));

            const response = await fetch('/save-image', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body
            });
            const result = await response.json();

            if (!response.ok) {
                statusText.textContent = result.error || 'Could not save the picture.';
                return;
            }

            addThumbnail(result.id, result.file);
            statusText.textContent = 'Picture captured and saved.';
        } catch (error) {
            statusText.textContent = 'Could not capture the picture.';
            console.error('Unable to capture picture:', error);
        } finally {
            captureButton.disabled = cameraStream === null;
        }
    }

    function addThumbnail(id, fileName) {
        if (!previousPictures || !fileName) {
            return;
        }

        previousPictures.querySelector('.empty-thumbnails')?.remove();

        const link = document.createElement('a');
        link.className = 'thumbnail-link';
        link.href = `/gallery#image-${id}`;

        const image = document.createElement('img');
        image.src = `/uploads/${fileName}`;
        image.alt = 'Previous Camagru picture';

        link.append(image);
        previousPictures.prepend(link);
    }

    overlayOptions.forEach((option) => {
        option.addEventListener('change', () => {
            selectedOverlay = option.value;

            if (overlayPreview) {
                overlayPreview.src = selectedOverlay;
            }
        });
    });

    startButton.addEventListener('click', startCamera);
    stopButton.addEventListener('click', stopCamera);
    captureButton.addEventListener('click', capturePhoto);
    window.addEventListener('beforeunload', stopCamera);
}

initWebcamPreview();
