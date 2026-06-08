<section class="auth-page auth-page-narrow" aria-labelledby="forgot-title">
    <div class="auth-intro">
        <p class="eyebrow">Password help</p>
        <h1 id="forgot-title">Reset your password</h1>
        <p class="intro-copy">Enter your account email address and we will send you a reset link.</p>
    </div>

    <?php if (!empty($success)): ?>
        <p class="form-success"><?= e($success) ?></p>
    <?php endif; ?>

    <?= errorFor('reset', $errors ?? []) ?>

    <section class="form-section" aria-label="Request password reset">
        <form action="/forgot" method="post">
            <?= Csrf::field() ?>

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

            <button type="submit">Send reset link</button>
            <a class="form-link" href="/">Back to login</a>
        </form>
    </section>
</section>
