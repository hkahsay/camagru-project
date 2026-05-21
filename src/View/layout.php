<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Camagru') ?></title>
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
</head>
<body>
    <?php partial('header', ['navItems' => $navItems ?? []]); ?>

    <main class="site-main">
        <div class="site-shell">
            <?php
            if (isset($viewPath) && is_file($viewPath)) {
                require $viewPath;
            } else {
                http_response_code(500);
                echo 'View not provided.';
            }
            ?>
        </div>
    </main>

    <?php partial('footer'); ?>

    <?php foreach (($scripts ?? []) as $script): ?>
        <script src="<?= asset($script) ?>"></script>
    <?php endforeach; ?>
</body>
</html>
