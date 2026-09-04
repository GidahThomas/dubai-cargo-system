<section class="auth-showcase">
    <div class="auth-showcase-brand">
        <div class="auth-brand">
            <img class="brand-logo" src="<?= h(public_url(company_logo_path())) ?>" alt="<?= h(company_name()) ?> logo">
            <div>
                <h1><?= h(company_name()) ?></h1>
                <p>Genuine products, unbeatable prices</p>
            </div>
        </div>

        <p class="auth-showcase-tagline">
            Quality products and fast, reliable service
            &mdash; delivered straight to you.
        </p>

        <ul class="auth-showcase-features">
            <li>
                <i class="bi bi-truck"></i>
                <div>
                    <strong>Fast delivery</strong>
                    <span>Straight to your doorstep, with live tracking.</span>
                </div>
            </li>
            <li>
                <i class="bi bi-receipt-cutoff"></i>
                <div>
                    <strong>Clear invoices, honest pricing</strong>
                    <span>Printable invoices with every cost in <?= h(default_currency_code()) ?>.</span>
                </div>
            </li>
            <li>
                <i class="bi bi-headset"></i>
                <div>
                    <strong>We're here to help</strong>
                    <span>Reach our team by phone or Instagram anytime.</span>
                </div>
            </li>
        </ul>

        <div class="auth-showcase-contact">
            <a href="tel:<?= h(company_phone()) ?>"><i class="bi bi-telephone"></i> <?= h(company_phone()) ?></a>
            <?php if (company_instagram_url()): ?>
                <a href="<?= h(company_instagram_url()) ?>" target="_blank" rel="noopener"><i class="bi bi-instagram"></i> <?= h(company_social_handle()) ?></a>
            <?php endif; ?>
        </div>
    </div>

    <div class="auth-showcase-form">
        <div class="auth-form-heading">
            <h2>Welcome Back</h2>
            <p>Sign in with your team email to manage orders, inventory, and customers.</p>
        </div>

        <form method="post" action="<?= h(url('login')) ?>" class="needs-validation" novalidate>
            <?= Auth::csrfField() ?>

            <div class="mb-3">
                <label for="email" class="form-label">Email address</label>
                <div class="input-icon-group">
                    <i class="bi bi-envelope"></i>
                    <input type="email" class="form-control" id="email" name="email" placeholder="you@yourbusiness.test" required autofocus>
                </div>
                <div class="invalid-feedback">A valid email address is required.</div>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <div class="input-icon-group">
                    <i class="bi bi-lock"></i>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
                </div>
                <div class="invalid-feedback">Password is required.</div>
            </div>

            <button class="btn btn-primary w-100" type="submit">
                <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
            </button>
        </form>

        <div class="auth-links">
            <a href="<?= h(url()) ?>"><i class="bi bi-arrow-left"></i> Continue to public website</a>
        </div>

        <div class="auth-form-footnote">
            <p>Just here to shop? No account needed &mdash; browse products and get an instant invoice.</p>
            <a class="btn btn-outline-primary btn-sm" href="<?= h(url('products')) ?>">
                <i class="bi bi-grid"></i> Browse Products
            </a>
        </div>
    </div>
</section>
