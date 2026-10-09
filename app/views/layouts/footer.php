<?php if (($layout ?? '') === 'public'): ?>
    </main>
    <?php
    try {
        // Branches with their own address (one that just repeats the company address adds nothing).
        $footerBranches = array_filter(
            (new Location())->all(true),
            static fn (array $branch): bool => !empty($branch['address']) && strcasecmp(trim($branch['address']), trim(company_address())) !== 0
        );
    } catch (Throwable) {
        $footerBranches = [];
    }
    ?>
    <footer class="public-footer">
        <div class="site-footer-grid">
            <div class="site-footer-col site-footer-about">
                <a class="site-footer-brand" href="<?= h(url()) ?>">
                    <img src="<?= h(public_url(company_logo_path())) ?>" alt="">
                    <strong><?= h(company_name()) ?></strong>
                </a>
                <p>Genuine laptops, desktops and accessories, imported and delivered with full invoice and shipment tracking across Tanzania.</p>
                <?php if (company_instagram_url()): ?>
                    <a class="site-footer-social" href="<?= h(company_instagram_url()) ?>" target="_blank" rel="noopener">
                        <i class="bi bi-instagram"></i> <?= h(company_social_handle() ?: 'Instagram') ?>
                    </a>
                <?php endif; ?>
                <button class="site-footer-install" type="button" data-install-app hidden>
                    <i class="bi bi-phone"></i> Install the <?= h(company_name()) ?> app
                </button>
                <small class="site-footer-ios-tip" data-ios-install-tip hidden>
                    <i class="bi bi-box-arrow-up"></i> On iPhone: tap Share, then <strong>Add to Home Screen</strong> to install the app.
                </small>
            </div>

            <nav class="site-footer-col" aria-label="Shop">
                <h2>Shop</h2>
                <a href="<?= h(url('products')) ?>">All products</a>
                <?php if (gallery_has_posts()): ?>
                    <a href="<?= h(url('gallery')) ?>">Gallery</a>
                <?php endif; ?>
                <a href="<?= h(url('request-quotation')) ?>">Get a quote</a>
                <a href="<?= h(url('request-invoice')) ?>">Request an invoice</a>
                <a href="<?= h(url('track-shipment')) ?>">Track a shipment</a>
            </nav>

            <div class="site-footer-col">
                <h2>Contact</h2>
                <a href="tel:<?= h(preg_replace('/\s+/', '', company_phone())) ?>"><i class="bi bi-telephone"></i> <?= h(company_phone()) ?></a>
                <a href="mailto:<?= h(customer_contact_email()) ?>"><i class="bi bi-envelope"></i> <?= h(customer_contact_email()) ?></a>
                <?php foreach (whatsapp_contacts() as $contact): ?>
                    <a href="<?= h($contact['url']) ?>" target="_blank" rel="noopener">
                        <i class="bi bi-whatsapp"></i> <?= h($contact['phone']) ?> <small><?= h($contact['label']) ?></small>
                    </a>
                <?php endforeach; ?>
            </div>

            <div class="site-footer-col">
                <h2>Visit us</h2>
                <?php if (company_address()): ?>
                    <span><i class="bi bi-geo-alt"></i> <?= h(company_address()) ?></span>
                <?php endif; ?>
                <?php foreach ($footerBranches as $branch): ?>
                    <span><i class="bi bi-shop"></i> <?= h($branch['name']) ?><small><?= h($branch['address']) ?></small></span>
                <?php endforeach; ?>
                <a href="<?= h(url('contact')) ?>"><i class="bi bi-chat-dots"></i> Send us a message</a>
                <?php if (Auth::check()): ?>
                    <a href="<?= h(url('dashboard')) ?>"><i class="bi bi-person"></i> My account</a>
                <?php else: ?>
                    <a href="<?= h(url('login')) ?>"><i class="bi bi-person"></i> Sign in or create an account</a>
                <?php endif; ?>
            </div>
        </div>
        <div class="public-footer-bottom">
            <span>&copy; <?= h(date('Y')) ?> <?= h(company_name()) ?>. All rights reserved.</span>
            <span>Powered by Greenleaf Tech Co Ltd</span>
        </div>
    </footer>
<?php elseif (Auth::check()): ?>
    </div>
<?php else: ?>
    </main>
<?php endif; ?>

<?php if (($layout ?? '') === 'public' || Auth::check()): ?>
    <?php require __DIR__ . '/bottom-nav.php'; ?>
<?php endif; ?>

<?php if (($layout ?? '') === 'public' || Auth::role() === 'customer'): ?>
    <?php $whatsappChat = whatsapp_url('Hello ' . company_name() . ', I have a question.'); ?>
    <?php if ($whatsappChat !== ''): ?>
        <a class="whatsapp-float" href="<?= h($whatsappChat) ?>" target="_blank" rel="noopener" aria-label="Chat with us on WhatsApp">
            <i class="bi bi-whatsapp" aria-hidden="true"></i>
            <span>Chat with us</span>
        </a>
    <?php endif; ?>
<?php endif; ?>

<script src="<?= h(asset('vendor/bootstrap/js/bootstrap.bundle.min.js')) ?>"></script>
<script src="<?= h(asset('vendor/chartjs/chart.umd.js')) ?>"></script>
<script src="<?= h(versioned_asset('js/main.js')) ?>"></script>
</body>
</html>
