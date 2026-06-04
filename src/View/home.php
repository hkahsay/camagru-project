<?php if (empty($user)): ?>
    <section class="auth-page" aria-labelledby="auth-title">
        <div class="auth-intro">
            <p class="eyebrow">Camagru</p>
            <h1 id="auth-title">Sign in or create your account</h1>
            <p class="intro-copy">You need an account and a confirmed email address before using the camera tools.</p>
        </div>

        <?php if (!empty($success)): ?>
            <p class="form-success"><?= e($success) ?></p>
        <?php endif; ?>

        <?= errorFor('verification', $errors ?? []) ?>

        <div class="auth-layout">
            <section class="form-section" aria-labelledby="login-title">
                <h2 id="login-title">Login</h2>

                <form action="/login" method="post">
                    <?= Csrf::field() ?>

                    <label>
                        <span>Username or email</span>
                        <input
                            type="text"
                            name="login"
                            value="<?= old('login', $old ?? []) ?>"
                            autocomplete="username"
                            required
                        >
                        <?= errorFor('login', $errors ?? []) ?>
                    </label>

                    <label>
                        <span>Password</span>
                        <input
                            type="password"
                            name="login_password"
                            autocomplete="current-password"
                            required
                        >
                        <?= errorFor('login_password', $errors ?? []) ?>
                    </label>

                    <button type="submit">Login</button>
                </form>
            </section>

            <section class="form-section" aria-labelledby="register-title">
                <h2 id="register-title">Create account</h2>

                <form action="/register" method="post">
                    <?= Csrf::field() ?>

                    <div class="form-grid single-column">
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
        </div>
    </section>
<?php else: ?>
    <section id="camera" class="camera-page" aria-labelledby="camera-title">
        <div class="camera-intro">
            <p class="eyebrow">Welcome, <?= e($user['username']) ?></p>
            <h1 id="camera-title">Capture your Camagru moment</h1>
            <p class="intro-copy">Start the camera to preview your shot before adding filters or saving it.</p>
        </div>

        <?php if (!empty($success)): ?>
            <p class="form-success"><?= e($success) ?></p>
        <?php endif; ?>

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

        <section id="gallery" class="gallery-section" aria-labelledby="saved-title">
            <h2 id="saved-title">Gallery</h2>
            <p>Saved photos will appear here when the image workflow is connected.</p>
        </section>
    </section>
<?php endif; ?>
