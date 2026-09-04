<section class="public-page-heading">
    <span class="public-kicker">Sales and cargo support</span>
    <h1>Contact Us</h1>
    <p>Send your product, invoice, or cargo question to the <?= h(company_name()) ?> team.</p>
</section>

<section class="public-contact-grid">
    <form method="post" action="<?= h(url('contact')) ?>" class="panel public-form-panel needs-validation" novalidate>
        <?= Auth::csrfField() ?>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="name">Full Name</label>
                <input class="form-control" id="name" name="name" placeholder="e.g. Juma Mwakalinga" required>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="phone">Phone</label>
                <input class="form-control" id="phone" name="phone" placeholder="e.g. 0652 532 646" required>
            </div>
            <div class="col-12">
                <label class="form-label" for="email">Email</label>
                <input type="email" class="form-control" id="email" name="email" placeholder="you@example.com">
            </div>
            <div class="col-12">
                <label class="form-label" for="message">Message</label>
                <textarea class="form-control" id="message" name="message" rows="5" placeholder="Tell us about your product, invoice, or cargo question" required></textarea>
            </div>
            <div class="col-12">
                <button class="btn btn-primary" type="submit"><i class="bi bi-send"></i> Send Message</button>
            </div>
        </div>
    </form>

    <aside class="public-contact-card">
        <h2><?= h(company_name()) ?></h2>
        <p><?= h(company_address()) ?></p>
        <p><i class="bi bi-telephone"></i> <?= h(company_phone()) ?></p>
        <p><i class="bi bi-envelope"></i> <?= h(company_email()) ?></p>
        <p><i class="bi bi-headset"></i> <?= h(customer_contact_email()) ?></p>
        <?php if (company_instagram_url()): ?>
            <p>
                <i class="bi bi-instagram"></i>
                <a href="<?= h(company_instagram_url()) ?>" target="_blank" rel="noopener"><?= h(company_social_handle()) ?></a>
                <small class="d-block text-muted">Same handle on all social platforms</small>
            </p>
        <?php endif; ?>
        <a class="btn btn-outline-primary" href="<?= h(url('request-quotation')) ?>">
            <i class="bi bi-chat-square-text"></i> Request Quotation
        </a>
    </aside>
</section>
