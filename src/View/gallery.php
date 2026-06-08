<section class="gallery-page" aria-labelledby="gallery-title">
    <div class="auth-intro">
        <p class="eyebrow">Gallery</p>
        <h1 id="gallery-title">Public gallery</h1>
        <p class="intro-copy">Browse the latest Camagru images from everyone.</p>
    </div>

    <?php if (!empty($success)): ?>
        <p class="form-success"><?= e($success) ?></p>
    <?php endif; ?>

    <?= errorFor('gallery', $errors ?? []) ?>
    <?= errorFor('comment', $errors ?? []) ?>

    <?php if (empty($images)): ?>
        <section class="gallery-section">
            <p>No images have been shared yet.</p>
        </section>
    <?php else: ?>
        <div class="public-gallery-grid">
            <?php foreach ($images as $image): ?>
                <article id="image-<?= (int) $image['id'] ?>" class="gallery-card">
                    <img
                        src="/uploads/<?= e($image['file_name']) ?>"
                        alt="Camagru image by <?= e($image['username']) ?>"
                        loading="lazy"
                    >

                    <div class="gallery-card-body">
                        <div class="gallery-card-meta">
                            <strong><?= e($image['username']) ?></strong>
                            <time datetime="<?= e((string) $image['created_at']) ?>">
                                <?= e(date('M j, Y H:i', strtotime((string) $image['created_at']))) ?>
                            </time>
                        </div>

                        <div class="gallery-actions">
                            <?php if (!empty($user)): ?>
                                <form action="/gallery/like" method="post">
                                    <?= Csrf::field() ?>
                                    <input type="hidden" name="image_id" value="<?= (int) $image['id'] ?>">
                                    <button class="secondary" type="submit">
                                        <?= ((int) $image['liked_by_viewer']) === 1 ? 'Unlike' : 'Like' ?>
                                    </button>
                                </form>
                            <?php endif; ?>

                            <span><?= (int) $image['likes_count'] ?> like<?= ((int) $image['likes_count']) === 1 ? '' : 's' ?></span>
                            <span><?= (int) $image['comments_count'] ?> comment<?= ((int) $image['comments_count']) === 1 ? '' : 's' ?></span>
                        </div>

                        <?php if (!empty($image['comments'])): ?>
                            <div class="comments-list">
                                <?php foreach ($image['comments'] as $comment): ?>
                                    <p>
                                        <strong><?= e($comment['username']) ?></strong>
                                        <?= e($comment['body']) ?>
                                    </p>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($user)): ?>
                            <form class="comment-form" action="/gallery/comment" method="post">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="image_id" value="<?= (int) $image['id'] ?>">
                                <label>
                                    <span>Comment</span>
                                    <input
                                        type="text"
                                        name="comment"
                                        maxlength="1000"
                                        required
                                    >
                                </label>
                                <button type="submit">Post</button>
                            </form>
                        <?php else: ?>
                            <p class="form-note">Log in to like or comment.</p>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
