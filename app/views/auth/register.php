<section class="auth-card auth-card-wide">
    <div class="auth-brand">
        <img class="brand-logo" src="<?= h(asset(company_logo_path())) ?>" alt="<?= h(company_name()) ?> logo">
        <div>
            <h1>Customer Registration</h1>
            <p>Create an account to order electronics and track cargo shipments.</p>
        </div>
    </div>

    <form method="post" action="<?= h(url('register')) ?>" class="needs-validation" novalidate>
        <?= Auth::csrfField() ?>

        <div class="row g-3">
            <div class="col-md-6">
                <label for="name" class="form-label">Full name</label>
                <input type="text" class="form-control" id="name" name="name" placeholder="e.g. Juma Mwakalinga" required>
                <div class="invalid-feedback">Full name is required.</div>
            </div>

            <div class="col-md-6">
                <label for="email" class="form-label">Email address</label>
                <input type="email" class="form-control" id="email" name="email" placeholder="you@example.com" required>
                <div class="invalid-feedback">A valid email is required.</div>
            </div>

            <div class="col-md-6">
                <label for="phone" class="form-label">Phone</label>
                <input type="text" class="form-control" id="phone" name="phone" placeholder="e.g. 0652 532 646">
            </div>

            <div class="col-md-6">
                <label for="city" class="form-label">City</label>
                <input type="text" class="form-control" id="city" name="city" placeholder="e.g. Dar es Salaam">
            </div>

            <div class="col-md-6">
                <label for="country" class="form-label">Country</label>
                <input type="text" class="form-control" id="country" name="country" value="Tanzania">
            </div>

            <div class="col-md-6">
                <label for="address" class="form-label">Delivery address</label>
                <input type="text" class="form-control" id="address" name="address" placeholder="Street, ward, and landmark">
            </div>

            <div class="col-md-6">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" minlength="6" placeholder="At least 6 characters" required>
                <div class="invalid-feedback">Use at least 6 characters.</div>
            </div>

            <div class="col-md-6">
                <label for="confirm_password" class="form-label">Confirm password</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="6" placeholder="Re-enter your password" required>
                <div class="invalid-feedback">Confirm your password.</div>
            </div>
        </div>

        <button class="btn btn-primary w-100 mt-4" type="submit">
            <i class="bi bi-person-plus me-2"></i>Create Account
        </button>
    </form>

    <div class="auth-links">
        <a href="<?= h(url('login')) ?>">Already have an account?</a>
    </div>
</section>
