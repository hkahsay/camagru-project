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
        ]);
    }
}
