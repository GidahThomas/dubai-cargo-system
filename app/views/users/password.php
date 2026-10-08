<div class="page-header">
    <div>
        <p class="eyebrow">Account security</p>
        <h1>Change Password</h1>
    </div>
</div>

<section class="panel" style="max-width: 560px">
    <form method="post" action="<?= h(url('users/password')) ?>" class="needs-validation" novalidate autocomplete="off">
        <?= Auth::csrfField() ?>
        <div class="mb-3">
            <label class="form-label" for="current_password">Current password</label>
            <input type="password" class="form-control" id="current_password" name="current_password" autocomplete="current-password" required>
            <div class="invalid-feedback">Enter your current password.</div>
        </div>
        <div class="mb-3">
            <label class="form-label" for="new_password">New password</label>
            <input type="password" class="form-control" id="new_password" name="new_password" minlength="<?= Auth::MIN_PASSWORD_LENGTH ?>" autocomplete="new-password" required>
            <div class="form-text">At least <?= Auth::MIN_PASSWORD_LENGTH ?> characters. A short sentence is easy to remember and hard to guess.</div>
        </div>
        <div class="mb-4">
            <label class="form-label" for="confirm_password">Confirm new password</label>
            <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="<?= Auth::MIN_PASSWORD_LENGTH ?>" autocomplete="new-password" required>
        </div>
        <button class="btn btn-primary" type="submit"><i class="bi bi-shield-lock"></i> Change password</button>
    </form>
</section>
