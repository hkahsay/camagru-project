<section class="account-page" aria-labelledby="account-title">
    <div class="auth-intro">
        <p class="eyebrow">Account</p>
        <h1 id="account-title">Manage your account</h1>
        <p class="intro-copy">Update your profile details or choose a new password.</p>
    </div>

    <?php if (!empty($success)): ?>
        <p class="form-success"><?= e($success) ?></p>
    <?php endif; ?>

    <div class="account-layout">
        <section class="form-section" aria-labelledby="profile-title">
            <h2 id="profile-title">Profile</h2>

            <form action="/account/profile" method="post">
                <?= Csrf::field() ?>

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

                <?php if (($user['email_verified_at'] ?? null) === null): ?>
                    <p class="form-note">This email address is waiting for confirmation.</p>
                <?php endif; ?>

                <label class="checkbox-label">
                    <input
                        type="checkbox"
                        name="comment_notifications_enabled"
                        value="1"
                        <?= old('comment_notifications_enabled', $old ?? []) === '1' ? 'checked' : '' ?>
                    >
                    <span>Email me when someone comments on my images</span>
                </label>

                <button type="submit">Save profile</button>
            </form>
        </section>

        <section class="form-section" aria-labelledby="password-title">
            <h2 id="password-title">Password</h2>

            <form action="/account/password" method="post">
                <?= Csrf::field() ?>

                <label>
                    <span>Current password</span>
                    <input
                        type="password"
                        name="current_password"
                        autocomplete="current-password"
                        required
                    >
                    <?= errorFor('current_password', $errors ?? []) ?>
                </label>

                <label>
                    <span>New password</span>
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
                    <span>Confirm new password</span>
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

                <button type="submit">Update password</button>
            </form>
        </section>
    </div>
</section>
