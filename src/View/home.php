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
                <div class="form-section-header">
                    <h2 id="login-title">Login</h2>
                    <a class="form-link" href="/forgot">Forgot password?</a>
                </div>

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
            <section class="camera-panel" aria-labelledby="capture-title">
                <h2 id="capture-title">Camera preview</h2>

                <input type="hidden" id="capture-csrf-token" value="<?= e(Csrf::token()) ?>">

                <div class="preview capture-preview">
                    <video id="webcam" autoplay playsinline muted></video>
                    <img id="selected-overlay-preview" class="selected-overlay-preview" src="" alt="" hidden>
                </div>

                <fieldset class="overlay-picker" aria-label="Superposable images">
                    <legend>Superposable images</legend>

                    <label class="overlay-option">
                        <input type="radio" name="overlay" value="/overlays/camera-frame.svg">
                        <img src="/overlays/camera-frame.svg" alt="">
                        <span>Frame</span>
                    </label>

                    <label class="overlay-option">
                        <input type="radio" name="overlay" value="/overlays/sunglasses.svg">
                        <img src="/overlays/sunglasses.svg" alt="">
                        <span>Glasses</span>
                    </label>

                    <label class="overlay-option">
                        <input type="radio" name="overlay" value="/overlays/stars.svg">
                        <img src="/overlays/stars.svg" alt="">
                        <span>Stars</span>
                    </label>
                </fieldset>

                <div class="controls">
                    <button id="start-camera" type="button">Start camera</button>
                    <button id="capture-photo" type="button" disabled>Capture picture</button>
                    <button id="stop-camera" class="secondary" type="button" disabled>Stop camera</button>
                </div>

                <p id="status" class="status" role="status">Camera is off.</p>
            </section>

            <aside class="side-panel previous-panel" aria-labelledby="previous-title">
                <div class="form-section-header">
                    <h2 id="previous-title">Previous pictures</h2>
                    <a class="form-link" href="/gallery">Public gallery</a>
                </div>

                <div id="previous-pictures" class="thumbnail-grid">
                    <?php if (empty($userImages)): ?>
                        <p class="empty-thumbnails">Captured pictures will appear here.</p>
                    <?php else: ?>
                        <?php foreach ($userImages as $image): ?>
                            <a href="/gallery#image-<?= (int) $image['id'] ?>" class="thumbnail-link">
                                <img
                                    src="/uploads/<?= e($image['file_name']) ?>"
                                    alt="Previous Camagru picture"
                                    loading="lazy"
                                >
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </aside>
        </div>

        <section id="gallery" class="gallery-section" aria-labelledby="saved-title">
            <h2 id="saved-title">Gallery</h2>
            <p>See the public gallery with images shared by all Camagru users.</p>
            <a class="form-link" href="/gallery">Open gallery</a>
        </section>
    </section>
<?php endif; ?>
