function initWebcamPreview() {
    const video = document.getElementById('webcam');
    const startButton = document.getElementById('start-camera');
    const stopButton = document.getElementById('stop-camera');
    const captureButton = document.getElementById('capture-photo');
    const saveUploadButton = document.getElementById('save-uploaded-image');
    const statusText = document.getElementById('status');
    const csrfToken = document.getElementById('capture-csrf-token');
    const overlayPreview = document.getElementById('selected-overlay-preview');
    const uploadedInput = document.getElementById('uploaded-image');
    const uploadedPreview = document.getElementById('uploaded-image-preview');
    const overlayOptions = document.querySelectorAll('input[name="overlay"]');
    const previousPictures = document.getElementById('previous-pictures');

    if (!video || !startButton || !stopButton || !captureButton || !saveUploadButton || !statusText || !csrfToken) {
        return;
    }

    let cameraStream = null;
    let selectedOverlay = '';
    let selectedOverlayPreview = '';
    let uploadedImageData = '';

    function updateCaptureState() {
        captureButton.disabled = cameraStream === null || selectedOverlay === '';
        saveUploadButton.disabled = uploadedImageData === '' || selectedOverlay === '';
    }

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
            updateCaptureState();
            statusText.textContent = selectedOverlay === ''
                ? 'Camera preview is active. Select a superposable image before capturing.'
                : 'Camera preview is active.';
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
        updateCaptureState();
        statusText.textContent = 'Camera is off.';
    }

    async function saveImageData(imageData) {
        if (selectedOverlay === '') {
            statusText.textContent = 'Select a superposable image before saving.';
            updateCaptureState();
            return;
        }

        captureButton.disabled = true;
        saveUploadButton.disabled = true;
        statusText.textContent = 'Saving picture...';

        try {
            const body = new URLSearchParams();
            body.set('csrf_token', csrfToken.value);
            body.set('image', imageData);
            body.set('overlay', selectedOverlay);

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
            statusText.textContent = 'Could not save the picture.';
            console.error('Unable to save picture:', error);
        } finally {
            updateCaptureState();
        }
    }

    async function capturePhoto() {
        if (!cameraStream || video.videoWidth === 0 || video.videoHeight === 0) {
            statusText.textContent = 'Start the camera before capturing.';
            return;
        }

        const canvas = document.createElement('canvas');
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;

        const context = canvas.getContext('2d');

        context.save();
        context.translate(canvas.width, 0);
        context.scale(-1, 1);
        context.drawImage(video, 0, 0, canvas.width, canvas.height);
        context.restore();

        await saveImageData(canvas.toDataURL('image/jpeg', 0.92));
    }

    async function saveUploadedImage() {
        if (uploadedImageData === '') {
            statusText.textContent = 'Choose an image before saving.';
            return;
        }

        await saveImageData(uploadedImageData);
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
            selectedOverlayPreview = option.dataset.preview || '';

            if (overlayPreview) {
                overlayPreview.src = selectedOverlayPreview;
                overlayPreview.hidden = false;
            }

            updateCaptureState();

            if (cameraStream !== null) {
                statusText.textContent = 'Camera preview is active.';
            }
        });
    });

    if (uploadedInput) {
        uploadedInput.addEventListener('change', () => {
            const file = uploadedInput.files?.[0];
            uploadedImageData = '';

            if (!file) {
                if (uploadedPreview) {
                    uploadedPreview.hidden = true;
                    uploadedPreview.src = '';
                }

                updateCaptureState();
                return;
            }

            if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
                statusText.textContent = 'Choose a JPG, PNG, or WebP image.';
                uploadedInput.value = '';
                updateCaptureState();
                return;
            }

            if (file.size > 2_000_000) {
                statusText.textContent = 'Image must be 2MB or smaller.';
                uploadedInput.value = '';
                updateCaptureState();
                return;
            }

            const reader = new FileReader();

            reader.onload = () => {
                uploadedImageData = typeof reader.result === 'string' ? reader.result : '';

                if (uploadedPreview && uploadedImageData !== '') {
                    uploadedPreview.src = uploadedImageData;
                    uploadedPreview.hidden = false;
                }

                statusText.textContent = selectedOverlay === ''
                    ? 'Image selected. Select a superposable image before saving.'
                    : 'Image selected.';
                updateCaptureState();
            };

            reader.onerror = () => {
                uploadedImageData = '';
                statusText.textContent = 'Could not read the selected image.';
                updateCaptureState();
            };

            reader.readAsDataURL(file);
        });
    }

    updateCaptureState();
    startButton.addEventListener('click', startCamera);
    stopButton.addEventListener('click', stopCamera);
    captureButton.addEventListener('click', capturePhoto);
    saveUploadButton.addEventListener('click', saveUploadedImage);
    window.addEventListener('beforeunload', stopCamera);
}

initWebcamPreview();
