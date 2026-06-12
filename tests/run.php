<?php

declare(strict_types=1);

require_once __DIR__ . '/TestCase.php';
require_once dirname(__DIR__) . '/src/bootstrap.php';

$tests = new TestCase();

$tests->test('validator rejects invalid registration data', function (TestCase $test): void {
    $validator = (new Validator([
        'username' => 'bad name!',
        'email' => 'not-an-email',
        'password' => 'password',
        'password_confirm' => 'different',
    ]))
        ->required('username', 'Username')
        ->length('username', 'Username', 3, 30)
        ->username('username', 'Username')
        ->required('email', 'Email')
        ->email('email', 'Email')
        ->required('password', 'Password')
        ->password('password', 'Password')
        ->matches('password_confirm', 'password', 'Password confirmation');

    $test->assertFalse($validator->passes());
    $test->assertTrue(isset($validator->errors()['username']));
    $test->assertTrue(isset($validator->errors()['email']));
    $test->assertTrue(isset($validator->errors()['password']));
    $test->assertTrue(isset($validator->errors()['password_confirm']));
});

$tests->test('validator accepts valid registration data', function (TestCase $test): void {
    $validator = (new Validator([
        'username' => 'valid_user',
        'email' => 'valid@example.com',
        'password' => 'abc12345',
        'password_confirm' => 'abc12345',
    ]))
        ->required('username', 'Username')
        ->length('username', 'Username', 3, 30)
        ->username('username', 'Username')
        ->required('email', 'Email')
        ->email('email', 'Email')
        ->required('password', 'Password')
        ->password('password', 'Password')
        ->matches('password_confirm', 'password', 'Password confirmation');

    $test->assertTrue($validator->passes());
});

$tests->test('csrf token verifies only the session token', function (TestCase $test): void {
    unset($_SESSION['csrf_token']);

    $token = Csrf::token();

    $test->assertSame(64, strlen($token));
    $test->assertTrue(Csrf::verify($token));
    $test->assertFalse(Csrf::verify('fake-token'));
    $test->assertFalse(Csrf::verify(null));
});

$tests->test('email verification token is random and hashable', function (TestCase $test): void {
    $token = EmailVerificationToken::generate();
    $hash = EmailVerificationToken::hash($token);

    $test->assertMatches('/^[a-f0-9]{64}$/', $token);
    $test->assertMatches('/^[a-f0-9]{64}$/', $hash);
    $test->assertTrue(strtotime(EmailVerificationToken::expiresAt()) > time());
});

$tests->test('password reset token is random and expires soon', function (TestCase $test): void {
    $token = PasswordResetToken::generate();
    $hash = PasswordResetToken::hash($token);
    $expiresAt = strtotime(PasswordResetToken::expiresAt());

    $test->assertMatches('/^[a-f0-9]{64}$/', $token);
    $test->assertMatches('/^[a-f0-9]{64}$/', $hash);
    $test->assertTrue($expiresAt > time());
    $test->assertTrue($expiresAt <= time() + 60 * 60 + 5);
});

$tests->test('app url builds absolute links', function (TestCase $test): void {
    putenv('APP_URL=http://localhost:8080');

    $test->assertSame(
        'http://localhost:8080/verify-email?token=abc',
        AppUrl::to('/verify-email?token=abc')
    );
});

$tests->test('view helpers escape unsafe output', function (TestCase $test): void {
    $test->assertSame('&lt;script&gt;alert(&#039;x&#039;)&lt;/script&gt;', e("<script>alert('x')</script>"));
    $test->assertSame('&quot;quoted&quot;', old('name', ['name' => '"quoted"']));
});

$tests->test('mailer writes confirmation email to log driver', function (TestCase $test): void {
    putenv('MAIL_DRIVER=log');

    $mailLog = STORAGE_PATH . '/mail.log';

    if (is_file($mailLog)) {
        unlink($mailLog);
    }

    (new Mailer())->sendEmailConfirmation(
        'user@example.com',
        'tester',
        'http://localhost:8080/verify-email?token=abc'
    );

    $contents = file_get_contents($mailLog);

    $test->assertTrue(is_string($contents));
    $test->assertTrue(str_contains($contents, 'To: user@example.com'));
    $test->assertTrue(str_contains($contents, 'http://localhost:8080/verify-email?token=abc'));

    unlink($mailLog);
});

$tests->test('mailer writes password reset email to log driver', function (TestCase $test): void {
    putenv('MAIL_DRIVER=log');

    $mailLog = STORAGE_PATH . '/mail.log';

    if (is_file($mailLog)) {
        unlink($mailLog);
    }

    (new Mailer())->sendPasswordReset(
        'user@example.com',
        'tester',
        'http://localhost:8080/reset-password?token=abc'
    );

    $contents = file_get_contents($mailLog);

    $test->assertTrue(is_string($contents));
    $test->assertTrue(str_contains($contents, 'To: user@example.com'));
    $test->assertTrue(str_contains($contents, 'Reset your Camagru password'));
    $test->assertTrue(str_contains($contents, 'http://localhost:8080/reset-password?token=abc'));

    unlink($mailLog);
});

$tests->test('mailer writes comment notification email to log driver', function (TestCase $test): void {
    putenv('MAIL_DRIVER=log');

    $mailLog = STORAGE_PATH . '/mail.log';

    if (is_file($mailLog)) {
        unlink($mailLog);
    }

    (new Mailer())->sendCommentNotification(
        'author@example.com',
        'author',
        'commenter',
        'http://localhost:8080/gallery#image-12'
    );

    $contents = file_get_contents($mailLog);

    $test->assertTrue(is_string($contents));
    $test->assertTrue(str_contains($contents, 'To: author@example.com'));
    $test->assertTrue(str_contains($contents, 'New comment on your Camagru image'));
    $test->assertTrue(str_contains($contents, 'commenter commented on your Camagru image.'));
    $test->assertTrue(str_contains($contents, 'http://localhost:8080/gallery#image-12'));

    unlink($mailLog);
});

exit($tests->finish());
