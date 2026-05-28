<section class="camera-page" aria-labelledby="camera-title">
    <div class="camera-intro">
        <p class="eyebrow">Webcam preview</p>
        <h1 id="camera-title">Capture your Camagru moment</h1>
        <p class="intro-copy">Start the camera to preview your shot before adding filters or saving it.</p>
    </div>

    <div class="camera-layout">
        <section class="camera-panel" aria-label="Camera preview">
            <div class="preview">
                <video id="webcam" autoplay playsinline muted></video>
            </div>

            <div class="controls">
                <button id="start-camera" type="button">Start camera</button>
                <button id="stop-camera" class="secondary" type="button" disabled>Stop camera</button>
            </div>

            <p id="status" class="status" role="status">Camera is off.</p>
        </section>

        <aside class="side-panel" aria-labelledby="gallery-title">
            <h2 id="gallery-title">Next steps</h2>
            <ul>
                <li>Preview your webcam feed.</li>
                <li>Add photo tools and overlays here later.</li>
                <li>Save images to the gallery endpoint.</li>
            </ul>
        </aside>
    </div>

    <section class="form-section" aria-labelledby="register-title">
        <h2 id="register-title">Create your account</h2>

        <?php if (!empty($success)): ?>
            <p class="form-success"><?= e($success) ?></p>
        <?php endif; ?>

        <?= errorFor('verification', $errors ?? []) ?>

        <form action="/register" method="post">
            <?= Csrf::field() ?>

            <div class="form-grid">
                <label>
                    <span>Username</span>
                    <input
                        type="text"
                        name="username"
                        value="<?= old('username', $old ?? []) ?>"
                        minlength="3"
                        maxlength="30"
                        pattern="[A-Za-z0-9_]+"
                        autocomplete="username"
                        required
                    >
                    <?= errorFor('username', $errors ?? []) ?>
                </label>

                <label>
                    <span>Email</span>
                    <input
                        type="email"
                        name="email"
                        value="<?= old('email', $old ?? []) ?>"
                        autocomplete="email"
                        required
                    >
                    <?= errorFor('email', $errors ?? []) ?>
                </label>

                <label>
                    <span>Password</span>
                    <input
                        type="password"
                        name="password"
                        minlength="8"
                        maxlength="72"
                        pattern="(?=.*[A-Za-z])(?=.*\d).{8,72}"
                        autocomplete="new-password"
                        required
                    >
                    <?= errorFor('password', $errors ?? []) ?>
                </label>

                <label>
                    <span>Confirm password</span>
                    <input
                        type="password"
                        name="password_confirm"
                        minlength="8"
                        maxlength="72"
                        pattern="(?=.*[A-Za-z])(?=.*\d).{8,72}"
                        autocomplete="new-password"
                        required
                    >
                    <?= errorFor('password_confirm', $errors ?? []) ?>
                </label>
            </div>

            <button type="submit">Create account</button>
        </form>
    </section>

    <section id="gallery" class="gallery-section" aria-labelledby="saved-title">
        <h2 id="saved-title">Gallery</h2>
        <p>Saved photos will appear here when the image workflow is connected.</p>
    </section>
</section>
