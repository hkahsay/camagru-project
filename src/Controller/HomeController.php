<?php

declare(strict_types=1);

final class HomeController
{
    private const GALLERY_IMAGES_PER_PAGE = 5;

    public function index(): void
    {
        $user = $_SESSION['user'] ?? null;
        $userImages = [];

        if (is_array($user) && !empty($user['id'])) {
            $userImages = (new ImageRepository())->forUser((int) $user['id']);
        }

        render('home', [
            'title' => $user === null ? 'Camagru Login' : 'Camagru Camera',
            'navItems' => $user === null ? [
                ['label' => 'Gallery', 'href' => '/gallery'],
            ] : [
                ['label' => 'Camera', 'href' => '#camera'],
                ['label' => 'Gallery', 'href' => '/gallery'],
                ['label' => 'Account', 'href' => '/account'],
                ['label' => 'Logout', 'href' => '/logout'],
            ],
            'scripts' => ['/js/app.js'],
            'old' => $_SESSION['old'] ?? [],
            'errors' => $_SESSION['errors'] ?? [],
            'success' => $_SESSION['success'] ?? '',
            'user' => $user,
            'userImages' => $userImages,
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

    public function gallery(): void
    {
        $sessionUser = $_SESSION['user'] ?? null;
        $viewerId = is_array($sessionUser) && !empty($sessionUser['id']) ? (int) $sessionUser['id'] : null;
        $imageRepository = new ImageRepository();
        $totalImages = $imageRepository->countAll();
        $totalPages = max(1, (int) ceil($totalImages / self::GALLERY_IMAGES_PER_PAGE));
        $currentPage = min($this->galleryPageFrom($_GET['page'] ?? null), $totalPages);
        $images = $imageRepository->all(
            $viewerId,
            self::GALLERY_IMAGES_PER_PAGE,
            ($currentPage - 1) * self::GALLERY_IMAGES_PER_PAGE
        );

        render('gallery', [
            'title' => 'Camagru Gallery',
            'navItems' => $sessionUser === null ? [
                ['label' => 'Gallery', 'href' => '/gallery'],
                ['label' => 'Login', 'href' => '/'],
            ] : [
                ['label' => 'Camera', 'href' => '/#camera'],
                ['label' => 'Gallery', 'href' => '/gallery'],
                ['label' => 'Account', 'href' => '/account'],
                ['label' => 'Logout', 'href' => '/logout'],
            ],
            'images' => array_map(
                static function (array $image) use ($imageRepository): array {
                    $image['comments'] = $imageRepository->commentsFor((int) $image['id']);
                    return $image;
                },
                $images
            ),
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
            'imagesPerPage' => self::GALLERY_IMAGES_PER_PAGE,
            'errors' => $_SESSION['errors'] ?? [],
            'success' => $_SESSION['success'] ?? '',
            'user' => $sessionUser,
        ]);

        unset($_SESSION['errors'], $_SESSION['success']);
    }

    public function likeImage(): void
    {
        $sessionUser = $this->requireUser();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::redirect($this->galleryUrl());
        }

        if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
            http_response_code(419);
            echo 'Invalid security token.';
            return;
        }

        $imageId = (int) ($_POST['image_id'] ?? 0);
        $images = new ImageRepository();

        if ($imageId < 1 || $images->find($imageId) === null) {
            $_SESSION['errors'] = [
                'gallery' => ['This image is no longer available.'],
            ];

            Response::redirect($this->galleryUrl());
        }

        $images->toggleLike($imageId, (int) $sessionUser['id']);

        Response::redirect($this->galleryUrl($imageId));
    }

    public function commentImage(): void
    {
        $sessionUser = $this->requireUser();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::redirect($this->galleryUrl());
        }

        if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
            http_response_code(419);
            echo 'Invalid security token.';
            return;
        }

        $imageId = (int) ($_POST['image_id'] ?? 0);
        $images = new ImageRepository();
        $image = $imageId > 0 ? $images->findWithAuthor($imageId) : null;

        if ($image === null) {
            $_SESSION['errors'] = [
                'gallery' => ['This image is no longer available.'],
            ];

            Response::redirect($this->galleryUrl());
        }

        $validator = (new Validator($_POST))
            ->required('comment', 'Comment')
            ->length('comment', 'Comment', 1, 1000);

        if (!$validator->passes()) {
            $_SESSION['errors'] = $validator->errors();

            Response::redirect($this->galleryUrl($imageId));
        }

        $images->addComment($imageId, (int) $sessionUser['id'], $validator->value('comment'));

        if (
            (int) $image['user_id'] !== (int) $sessionUser['id']
            && (int) $image['comment_notifications_enabled'] === 1
        ) {
            (new Mailer())->sendCommentNotification(
                (string) $image['email'],
                (string) $image['username'],
                (string) $sessionUser['username'],
                AppUrl::to($this->galleryUrl($imageId))
            );
        }

        Response::redirect($this->galleryUrl($imageId));
    }

    public function deleteImage(): void
    {
        $sessionUser = $this->requireUser();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::redirect($this->galleryUrl());
        }

        if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
            http_response_code(419);
            echo 'Invalid security token.';
            return;
        }

        $imageId = (int) ($_POST['image_id'] ?? 0);
        $fileName = (new ImageRepository())->deleteOwnedBy($imageId, (int) $sessionUser['id']);

        if ($fileName === null) {
            $_SESSION['errors'] = [
                'gallery' => ['You can delete only your own images.'],
            ];

            Response::redirect($this->galleryUrl());
        }

        $path = UPLOAD_PATH . '/' . $fileName;

        if (is_file($path)) {
            unlink($path);
        }

        $_SESSION['success'] = 'Image deleted.';

        Response::redirect($this->galleryUrl());
    }

    public function uploadImage(): void
    {
        $sessionUser = $this->requireUser();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::json(['error' => 'Method not allowed.'], 405);
        }

        if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
            Response::json(['error' => 'Invalid security token.'], 419);
        }

        $result = UploadedImage::store($_FILES['image'] ?? []);

        if (!$result['ok']) {
            Response::json(['error' => $result['error']], 422);
        }

        $imageId = (new ImageRepository())->create((int) $sessionUser['id'], (string) $result['fileName']);

        Response::json([
            'message' => 'Image uploaded successfully.',
            'id' => $imageId,
            'file' => $result['fileName'],
        ], 201);
    }

    public function saveImage(): void
    {
        $sessionUser = $this->requireUser();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::json(['error' => 'Method not allowed.'], 405);
        }

        if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
            Response::json(['error' => 'Invalid security token.'], 419);
        }

        $imageData = $_POST['image'] ?? '';
        $overlay = $_POST['overlay'] ?? '';

        if (!is_string($imageData) || !is_string($overlay)) {
            Response::json(['error' => 'Image data is invalid.'], 422);
        }
        if ($overlay === '') {
            Response::json(['error' => 'Select a superposable image.'], 422);
        }

        $result = UploadedImage::storeComposedDataUrl($imageData, $overlay);

        if (!$result['ok']) {
            Response::json(['error' => $result['error']], 422);
        }

        $imageId = (new ImageRepository())->create((int) $sessionUser['id'], (string) $result['fileName']);

        Response::json([
            'message' => 'Image saved successfully.',
            'id' => $imageId,
            'file' => $result['fileName'],
        ], 201);
    }

    public function serveUpload(string $fileName): void
    {
        if (!preg_match('/^[a-f0-9]{32}\.(?:jpg|png|webp)$/', $fileName)) {
            http_response_code(404);
            echo 'Image not found.';
            return;
        }

        $path = UPLOAD_PATH . '/' . $fileName;

        if (!is_file($path)) {
            http_response_code(404);
            echo 'Image not found.';
            return;
        }

        $mimeType = (new finfo(FILEINFO_MIME_TYPE))->file($path) ?: 'application/octet-stream';
        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . filesize($path));
        header('Cache-Control: public, max-age=31536000, immutable');
        readfile($path);
    }

    public function account(): void
    {
        $sessionUser = $this->requireUser();
        $user = (new UserRepository())->findById((int) $sessionUser['id']);

        if ($user === null) {
            unset($_SESSION['user']);
            Response::redirect('/');
        }

        render('account', [
            'title' => 'Account Settings',
            'navItems' => [
                ['label' => 'Camera', 'href' => '/#camera'],
                ['label' => 'Gallery', 'href' => '/gallery'],
                ['label' => 'Account', 'href' => '/account'],
                ['label' => 'Logout', 'href' => '/logout'],
            ],
            'old' => $_SESSION['old'] ?? [
                'username' => (string) $user['username'],
                'email' => (string) $user['email'],
                'comment_notifications_enabled' => ((int) $user['comment_notifications_enabled']) === 1 ? '1' : '0',
            ],
            'errors' => $_SESSION['errors'] ?? [],
            'success' => $_SESSION['success'] ?? '',
            'user' => $user,
        ]);

        unset($_SESSION['old'], $_SESSION['errors'], $_SESSION['success']);
    }

    public function updateAccount(): void
    {
        $sessionUser = $this->requireUser();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::redirect('/account');
        }

        if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
            http_response_code(419);
            echo 'Invalid security token.';
            return;
        }

        $users = new UserRepository();
        $currentUser = $users->findById((int) $sessionUser['id']);

        if ($currentUser === null) {
            unset($_SESSION['user']);
            Response::redirect('/');
        }

        $validator = (new Validator($_POST))
            ->required('username', 'Username')
            ->length('username', 'Username', 3, 30)
            ->username('username', 'Username')
            ->required('email', 'Email')
            ->email('email', 'Email');

        $username = $validator->value('username');
        $email = $validator->value('email');
        $commentNotificationsEnabled = isset($_POST['comment_notifications_enabled']);

        if ($users->existsByUsernameExcept($username, (int) $currentUser['id'])) {
            $validator->addError('username', 'This username is already taken.');
        }

        if ($users->existsByEmailExcept($email, (int) $currentUser['id'])) {
            $validator->addError('email', 'This email address is already registered.');
        }

        if (!$validator->passes()) {
            $_SESSION['errors'] = $validator->errors();
            $_SESSION['old'] = [
                'username' => $username,
                'email' => $email,
                'comment_notifications_enabled' => $commentNotificationsEnabled ? '1' : '0',
            ];

            Response::redirect('/account');
        }

        $emailChanged = mb_strtolower(trim($email)) !== mb_strtolower(trim((string) $currentUser['email']));
        $verificationTokenHash = null;
        $verificationExpiresAt = null;

        if ($emailChanged) {
            $verificationToken = EmailVerificationToken::generate();
            $verificationTokenHash = EmailVerificationToken::hash($verificationToken);
            $verificationExpiresAt = EmailVerificationToken::expiresAt();
        }

        $users->updateProfile(
            (int) $currentUser['id'],
            $username,
            $email,
            $commentNotificationsEnabled,
            $emailChanged,
            $verificationTokenHash,
            $verificationExpiresAt
        );

        $_SESSION['user'] = [
            'id' => (int) $currentUser['id'],
            'username' => $username,
            'email' => $email,
            'comment_notifications_enabled' => $commentNotificationsEnabled ? 1 : 0,
        ];

        if ($emailChanged) {
            (new Mailer())->sendEmailConfirmation(
                $email,
                $username,
                AppUrl::to('/verify-email?token=' . $verificationToken)
            );

            $_SESSION['success'] = 'Account updated. We sent a confirmation link to your new email address.';
        } else {
            $_SESSION['success'] = 'Account updated.';
        }

        Response::redirect('/account');
    }

    public function updatePassword(): void
    {
        $sessionUser = $this->requireUser();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::redirect('/account');
        }

        if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
            http_response_code(419);
            echo 'Invalid security token.';
            return;
        }

        $users = new UserRepository();
        $currentUser = $users->findById((int) $sessionUser['id']);

        if ($currentUser === null) {
            unset($_SESSION['user']);
            Response::redirect('/');
        }

        $validator = (new Validator($_POST))
            ->required('current_password', 'Current password')
            ->required('password', 'New password')
            ->password('password', 'New password')
            ->required('password_confirm', 'Password confirmation')
            ->matches('password_confirm', 'password', 'Password confirmation');

        if (!password_verify($validator->value('current_password'), (string) $currentUser['password_hash'])) {
            $validator->addError('current_password', 'The current password is incorrect.');
        }

        if (!$validator->passes()) {
            $_SESSION['errors'] = $validator->errors();

            Response::redirect('/account');
        }

        $passwordHash = password_hash($validator->value('password'), PASSWORD_DEFAULT);

        if (!is_string($passwordHash) || !$users->updatePassword((int) $currentUser['id'], $passwordHash)) {
            $_SESSION['errors'] = [
                'password' => ['Could not update the password. Please try again.'],
            ];

            Response::redirect('/account');
        }

        $_SESSION['success'] = 'Password updated.';

        Response::redirect('/account');
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

    public function forgotPassword(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            render('forgot-password', [
                'title' => 'Reset Password',
                'navItems' => [
                    ['label' => 'Login', 'href' => '/'],
                ],
                'old' => $_SESSION['old'] ?? [],
                'errors' => $_SESSION['errors'] ?? [],
                'success' => $_SESSION['success'] ?? '',
            ]);

            unset($_SESSION['old'], $_SESSION['errors'], $_SESSION['success']);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::redirect('/forgot');
        }

        if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
            http_response_code(419);
            echo 'Invalid security token.';
            return;
        }

        $validator = (new Validator($_POST))
            ->required('email', 'Email')
            ->email('email', 'Email');

        if (!$validator->passes()) {
            $_SESSION['errors'] = $validator->errors();
            $_SESSION['old'] = [
                'email' => $validator->value('email'),
            ];

            Response::redirect('/forgot');
        }

        $users = new UserRepository();
        $user = $users->findByEmail($validator->value('email'));

        if ($user !== null) {
            $resetToken = PasswordResetToken::generate();
            $users->storePasswordResetToken(
                (int) $user['id'],
                PasswordResetToken::hash($resetToken),
                PasswordResetToken::expiresAt()
            );

            (new Mailer())->sendPasswordReset(
                (string) $user['email'],
                (string) $user['username'],
                AppUrl::to('/reset-password?token=' . $resetToken)
            );
        }

        $_SESSION['success'] = 'If that email address is registered, we sent a password reset link.';

        Response::redirect('/forgot');
    }

    public function resetPassword(): void
    {
        $token = $_SERVER['REQUEST_METHOD'] === 'POST'
            ? ($_POST['token'] ?? '')
            : ($_GET['token'] ?? '');

        if (!is_string($token) || !preg_match('/^[a-f0-9]{64}$/', $token)) {
            $_SESSION['errors'] = [
                'reset' => ['The password reset link is invalid or expired.'],
            ];

            Response::redirect('/forgot');
        }

        $users = new UserRepository();

        if ($users->findByPasswordResetToken($token) === null) {
            $_SESSION['errors'] = [
                'reset' => ['The password reset link is invalid or expired.'],
            ];

            Response::redirect('/forgot');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            render('reset-password', [
                'title' => 'Choose New Password',
                'navItems' => [
                    ['label' => 'Login', 'href' => '/'],
                ],
                'token' => $token,
                'errors' => $_SESSION['errors'] ?? [],
            ]);

            unset($_SESSION['errors']);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::redirect('/forgot');
        }

        if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
            http_response_code(419);
            echo 'Invalid security token.';
            return;
        }

        $validator = (new Validator($_POST))
            ->required('password', 'Password')
            ->password('password', 'Password')
            ->required('password_confirm', 'Password confirmation')
            ->matches('password_confirm', 'password', 'Password confirmation');

        if (!$validator->passes()) {
            $_SESSION['errors'] = $validator->errors();

            Response::redirect('/reset-password?token=' . $token);
        }

        $passwordHash = password_hash($validator->value('password'), PASSWORD_DEFAULT);

        if (!is_string($passwordHash) || !$users->resetPassword($token, $passwordHash)) {
            $_SESSION['errors'] = [
                'reset' => ['Could not reset the password. Please request a new link.'],
            ];

            Response::redirect('/forgot');
        }

        $_SESSION['success'] = 'Your password has been reset. You can now log in.';

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

    private function galleryPageFrom(mixed $page): int
    {
        if (!is_string($page) && !is_int($page)) {
            return 1;
        }

        $page = (int) $page;

        return max(1, $page);
    }

    private function galleryUrl(?int $imageId = null): string
    {
        $page = $this->galleryPageFrom($_POST['page'] ?? $_GET['page'] ?? null);
        $url = $page > 1 ? '/gallery?page=' . $page : '/gallery';

        if ($imageId !== null) {
            $url .= '#image-' . $imageId;
        }

        return $url;
    }

    private function requireUser(): array
    {
        $user = $_SESSION['user'] ?? null;

        if (!is_array($user) || empty($user['id'])) {
            Response::redirect('/');
        }

        return $user;
    }
}
