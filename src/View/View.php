<?php

declare(strict_types=1);

function render(string $view, array $data = []): void
{
    extract($data, EXTR_SKIP);

    $viewPath = VIEW_PATH . '/' . $view . '.php';

    if (!is_file($viewPath)) {
        http_response_code(404);
        echo 'View not found.';
        return;
    }

    require VIEW_PATH . '/layout.php';
}

function partial(string $name, array $data = []): void
{
    extract($data, EXTR_SKIP);

    $partialPath = VIEW_PATH . '/partials/' . $name . '.php';

    if (is_file($partialPath)) {
        require $partialPath;
    }
}
