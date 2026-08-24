<section class="public-page-heading">
    <span class="public-kicker">Not available</span>
    <h1><?= h($pageTitle ?? 'Page Not Found') ?></h1>
    <p>The requested record is not available from the public website.</p>
    <a class="btn btn-primary" href="<?= h(url()) ?>">
        <i class="bi bi-house"></i> Home
    </a>
</section>
