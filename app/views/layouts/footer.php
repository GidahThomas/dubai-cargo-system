<?php if (($layout ?? '') === 'public'): ?>
    </main>
    <footer class="public-footer">
        <div class="public-footer-top">
            <div class="public-footer-brand">
                <strong><?= h(company_name()) ?></strong>
                <p>Fast, reliable cargo, invoice handling, and shipment tracking for modern businesses across Tanzania.</p>
                <div class="public-footer-contact">
                    <span><i class="bi bi-telephone"></i> <?= h(company_phone()) ?></span>
                    <span><i class="bi bi-envelope"></i> <?= h(customer_contact_email()) ?></span>
                    <?php foreach (whatsapp_contacts() as $contact): ?>
                        <a href="<?= h($contact['url']) ?>" target="_blank" rel="noopener">
                            <i class="bi bi-whatsapp"></i> <?= h($contact['phone']) ?> <small>(<?= h($contact['label']) ?>)</small>
                        </a>
                    <?php endforeach; ?>
                    <?php if (company_instagram_url()): ?>
                        <a href="<?= h(company_instagram_url()) ?>" target="_blank" rel="noopener">
                            <i class="bi bi-instagram"></i> <?= h(company_social_handle()) ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="public-footer-links">
                <a href="<?= h(url('track-shipment')) ?>">Track Shipment</a>
                <a href="<?= h(url('request-invoice')) ?>">Request Invoice</a>
                <a href="<?= h(url('gallery')) ?>">Gallery</a>
                <a href="<?= h(url('contact')) ?>">Contact Us</a>
                <a href="<?= h(url('login')) ?>">Sign In</a>
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
