<section class="auth-page auth-page-narrow" aria-labelledby="reset-title">
    <div class="auth-intro">
        <p class="eyebrow">Password reset</p>
        <h1 id="reset-title">Choose a new password</h1>
        <p class="intro-copy">Use 8-72 characters with at least one letter and one number.</p>
    </div>

    <?= errorFor('reset', $errors ?? []) ?>

    <section class="form-section" aria-label="Choose new password">
        <form action="/reset-password" method="post">
            <?= Csrf::field() ?>
            <input type="hidden" name="token" value="<?= e($token ?? '') ?>">

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

            <button type="submit">Reset password</button>
        </form>
    </section>
</section>
