<section class="auth-card">
    <div class="auth-brand">
        <img class="brand-logo" src="<?= h(public_url(company_logo_path())) ?>" alt="<?= h(company_name()) ?> logo">
        <div>
            <h1><?= h(company_name()) ?></h1>
            <p>Genuine products, unbeatable prices</p>
        </div>
    </div>

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
        <p>Just here to shop? No account needed, browse products and get an instant invoice.</p>
        <a class="btn btn-outline-primary btn-sm" href="<?= h(url('products')) ?>">
            <i class="bi bi-grid"></i> Browse Products
        </a>
    </div>
</section>
