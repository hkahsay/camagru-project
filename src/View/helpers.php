<?php

declare(strict_types=1);

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function asset(string $path): string
{
    return '/' . ltrim($path, '/');
}

function old(string $field, array $old = []): string
{
    return e((string) ($old[$field] ?? ''));
}

function errorFor(string $field, array $errors = []): string
{
    if (empty($errors[$field][0])) {
        return '';
    }

    return '<p class="field-error">' . e($errors[$field][0]) . '</p>';
}
