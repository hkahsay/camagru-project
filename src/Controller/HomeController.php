<?php

declare(strict_types=1);

final class HomeController
{
    public function index(): void
    {
        render('home', [
            'title' => 'Camagru Webcam Preview',
            'navItems' => [
                ['label' => 'Camera', 'href' => '/'],
                ['label' => 'Gallery', 'href' => '#gallery'],
            ],
            'scripts' => ['/js/app.js'],
            'old' => $_SESSION['old'] ?? [],
            'errors' => $_SESSION['errors'] ?? [],
            'success' => $_SESSION['success'] ?? '',
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

        $_SESSION['success'] = 'Your account has been confirmed. You can sign in when the login page is added.';

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

        $_SESSION['success'] = 'Account created. Check your email to confirm your account before signing in.';

        Response::redirect('/');
    }
}
