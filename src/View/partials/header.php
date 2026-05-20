<header class="site-header">
    <div class="site-shell header-content">
        <a class="brand" href="/">Camagru</a>

        <?php if (!empty($navItems)): ?>
            <nav class="site-nav" aria-label="Primary navigation">
                <?php foreach ($navItems as $item): ?>
                    <a href="<?= e($item['href']) ?>"><?= e($item['label']) ?></a>
                <?php endforeach; ?>
            </nav>
        <?php endif; ?>
    </div>
</header>
