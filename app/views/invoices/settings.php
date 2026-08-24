<div class="page-header">
    <div>
        <p class="eyebrow">Invoice configuration</p>
        <h1>Invoice Settings</h1>
    </div>
    <div class="page-actions">
        <a class="btn btn-outline-secondary" href="<?= h(url('invoices')) ?>">
            <i class="bi bi-arrow-left"></i> Invoice List
        </a>
        <a class="btn btn-primary" href="<?= h(url('invoices/create')) ?>">
            <i class="bi bi-plus-lg"></i> Create Invoice
        </a>
    </div>
</div>

<section class="panel">
    <form method="post" action="<?= h(url('invoices/updateSettings')) ?>" enctype="multipart/form-data" class="needs-validation" novalidate>
        <?= Auth::csrfField() ?>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="company_name">Company Name</label>
                <input class="form-control" id="company_name" name="company_name" value="<?= h($settings['company_name']) ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="phone">Phone</label>
                <input class="form-control" id="phone" name="phone" value="<?= h($settings['phone']) ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="email">Email</label>
                <input type="email" class="form-control" id="email" name="email" value="<?= h($settings['email']) ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="address">Address</label>
                <input class="form-control" id="address" name="address" value="<?= h($settings['address']) ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="tin">TIN</label>
                <input class="form-control" id="tin" name="tin" value="<?= h($settings['tin'] ?? '') ?>" placeholder="Taxpayer Identification Number">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="vrn">VRN</label>
                <input class="form-control" id="vrn" name="vrn" value="<?= h($settings['vrn'] ?? '') ?>" placeholder="VAT Registration Number">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="currency_code">Currency Code</label>
                <input class="form-control" id="currency_code" name="currency_code" value="<?= h($settings['currency_code'] ?? default_currency_code()) ?>" maxlength="12">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="vat_rate">VAT Rate %</label>
                <input type="number" step="0.01" min="0" class="form-control" id="vat_rate" name="vat_rate" value="<?= h($settings['vat_rate'] ?? '0') ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="footer_note">Footer Note</label>
                <input class="form-control" id="footer_note" name="footer_note" value="<?= h($settings['footer_note'] ?? 'Thank you for your business.') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="logo_path">Logo Path</label>
                <input class="form-control" id="logo_path" name="logo_path" value="<?= h($settings['logo_path'] ?? '') ?>" placeholder="uploads/company-logo.png">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="logo_file">Upload Company Logo</label>
                <input type="file" class="form-control" id="logo_file" name="logo_file" accept=".jpg,.jpeg,.png">
            </div>
            <?php if (!empty($settings['logo_path'])): ?>
                <div class="col-12">
                    <div class="settings-logo-preview">
                        <img src="<?= h(public_url($settings['logo_path'])) ?>" alt="<?= h($settings['company_name']) ?>">
                        <span><?= h($settings['logo_path']) ?></span>
                    </div>
                </div>
            <?php endif; ?>
            <div class="col-12">
                <label class="form-label" for="terms">Terms & Conditions</label>
                <textarea class="form-control" id="terms" name="terms" rows="5" placeholder="Payment terms, warranty notes, or other conditions"><?= h($settings['terms'] ?? '') ?></textarea>
            </div>
        </div>

        <div class="form-actions">
            <button class="btn btn-primary" type="submit">
                <i class="bi bi-save"></i> Save Settings
            </button>
        </div>
    </form>
</section>
