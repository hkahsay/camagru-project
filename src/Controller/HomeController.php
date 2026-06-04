<?php

declare(strict_types=1);

final class HomeController
{
    public function index(): void
    {
        $user = $_SESSION['user'] ?? null;

        render('home', [
            'title' => $user === null ? 'Camagru Login' : 'Camagru Camera',
            'navItems' => $user === null ? [] : [
                ['label' => 'Camera', 'href' => '#camera'],
                ['label' => 'Gallery', 'href' => '#gallery'],
                ['label' => 'Logout', 'href' => '/logout'],
            ],
            'scripts' => ['/js/app.js'],
            'old' => $_SESSION['old'] ?? [],
            'errors' => $_SESSION['errors'] ?? [],
            'success' => $_SESSION['success'] ?? '',
            'user' => $user,
        ]);

        unset($_SESSION['old'], $_SESSION['errors'], $_SESSION['success']);
    }

    public function verifyEmail(): void
    {
        $token = $_GET['token'] ?? '';

        if (!is_string($token) || !preg_match('/^[a-f0-9]{64}$/', $token)) {
            $_SESSION['errors'] = [
                'verification' => ['The confirmation link is invalid or expired.'],
            ];

            Response::redirect('/');
        }

        if (!(new UserRepository())->verifyEmail($token)) {
            $_SESSION['errors'] = [
                'verification' => ['The confirmation link is invalid or expired.'],
            ];

            Response::redirect('/');
        }

        $_SESSION['success'] = 'Your account has been confirmed. You can now log in.';

        Response::redirect('/');
    }

    public function login(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::redirect('/');
        }

        if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
            http_response_code(419);
            echo 'Invalid security token.';
            return;
        }

        $validator = (new Validator($_POST))
            ->required('login', 'Username or email')
            ->length('login', 'Username or email', 3, 190)
            ->required('login_password', 'Password');

        if (!$validator->passes()) {
            $_SESSION['errors'] = $validator->errors();
            $_SESSION['old'] = [
                'login' => $validator->value('login'),
            ];

            Response::redirect('/');
        }

        $user = (new UserRepository())->findByLogin($validator->value('login'));

        if ($user === null || !password_verify($validator->value('login_password'), (string) $user['password_hash'])) {
            $_SESSION['errors'] = [
                'login' => ['These credentials do not match our records.'],
            ];
            $_SESSION['old'] = [
                'login' => $validator->value('login'),
            ];

            Response::redirect('/');
        }

        if ($user['email_verified_at'] === null) {
            $_SESSION['errors'] = [
                'login' => ['Please confirm your email before signing in.'],
            ];
            $_SESSION['old'] = [
                'login' => $validator->value('login'),
            ];

            Response::redirect('/');
        }

        session_regenerate_id(true);

        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'username' => (string) $user['username'],
            'email' => (string) $user['email'],
        ];

        Response::redirect('/');
    }

    public function logout(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::redirect('/');
        }

        if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
            http_response_code(419);
            echo 'Invalid security token.';
            return;
        }

        unset($_SESSION['user']);
        session_regenerate_id(true);

        Response::redirect('/');
    }

    public function register(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::redirect('/');
        }

        if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
            http_response_code(419);
            echo 'Invalid security token.';
            return;
        }

        $validator = (new Validator($_POST))
            ->required('username', 'Username')
            ->length('username', 'Username', 3, 30)
            ->username('username', 'Username')
            ->required('email', 'Email')
            ->email('email', 'Email')
            ->required('password', 'Password')
            ->password('password', 'Password')
            ->required('password_confirm', 'Password confirmation')
            ->matches('password_confirm', 'password', 'Password confirmation');

        if (!$validator->passes()) {
            $_SESSION['errors'] = $validator->errors();
            $_SESSION['old'] = [
                'username' => $validator->value('username'),
                'email' => $validator->value('email'),
            ];

            Response::redirect('/');
        }

        $users = new UserRepository();
        $username = $validator->value('username');
        $email = $validator->value('email');

        if ($users->existsByUsername($username)) {
            $validator->addError('username', 'This username is already taken.');
        }

        if ($users->existsByEmail($email)) {
            $validator->addError('email', 'This email address is already registered.');
        }

        if (!$validator->passes()) {
            $_SESSION['errors'] = $validator->errors();
            $_SESSION['old'] = [
                'username' => $username,
                'email' => $email,
            ];

            Response::redirect('/');
        }

        $passwordHash = password_hash($validator->value('password'), PASSWORD_DEFAULT);

        if (!is_string($passwordHash)) {
            $_SESSION['errors'] = [
                'password' => ['Could not secure the password. Please try again.'],
            ];

            Response::redirect('/');
        }

        $verificationToken = EmailVerificationToken::generate();
        $verificationUrl = AppUrl::to('/verify-email?token=' . $verificationToken);

        try {
            $users->create(
                $username,
                $email,
                $passwordHash,
                EmailVerificationToken::hash($verificationToken),
                EmailVerificationToken::expiresAt()
            );
        } catch (PDOException) {
            $_SESSION['errors'] = [
                'email' => ['Could not create the account with these details.'],
            ];
            $_SESSION['old'] = [
                'username' => $username,
                'email' => $email,
            ];

            Response::redirect('/');
        }

        (new Mailer())->sendEmailConfirmation($email, $username, $verificationUrl);

        $_SESSION['success'] = 'Account created. We sent a confirmation link to your email address. Confirm it before signing in.';

        Response::redirect('/');
    }
}
