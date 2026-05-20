const video = document.getElementById('webcam');
const startButton = document.getElementById('start-camera');
const stopButton = document.getElementById('stop-camera');
const statusText = document.getElementById('status');

let cameraStream = null;

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
    statusText.textContent = 'Camera is off.';
}

startButton.addEventListener('click', startCamera);
stopButton.addEventListener('click', stopCamera);
window.addEventListener('beforeunload', stopCamera);
