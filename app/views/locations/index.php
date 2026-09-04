<div class="page-header">
    <div>
        <p class="eyebrow">Branches &amp; warehouses</p>
        <h1>Locations</h1>
    </div>
</div>

<section class="panel">
    <div class="panel-header">
        <h2>Add Location</h2>
    </div>
    <form method="post" action="<?= h(url('locations/store')) ?>" class="row g-3 align-items-end needs-validation" novalidate>
        <?= Auth::csrfField() ?>
        <div class="col-md-3">
            <label class="form-label" for="name">Name</label>
            <input class="form-control" id="name" name="name" placeholder="e.g. Arusha Branch" required>
        </div>
        <div class="col-md-2">
            <label class="form-label" for="code">Code</label>
            <input class="form-control code-text" id="code" name="code" placeholder="e.g. ARU" maxlength="20" required>
        </div>
        <div class="col-md-4">
            <label class="form-label" for="address">Address</label>
            <input class="form-control" id="address" name="address" placeholder="Street, city">
        </div>
        <div class="col-md-2">
            <label class="form-label" for="phone">Phone</label>
            <input class="form-control" id="phone" name="phone" placeholder="Optional">
        </div>
        <div class="col-md-1">
            <button class="btn btn-primary w-100" type="submit" title="Add location">
                <i class="bi bi-plus-lg"></i>
            </button>
        </div>
    </form>
</section>

<section class="panel mt-4">
    <?php foreach ($locations as $location): ?>
        <form id="locationForm<?= (int) $location['id'] ?>" method="post" action="<?= h(url('locations/update/' . $location['id'])) ?>">
            <?= Auth::csrfField() ?>
        </form>
    <?php endforeach; ?>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
            <tr>
                <th>Name</th>
                <th>Code</th>
                <th>Address</th>
                <th>Phone</th>
                <th>Status</th>
                <th class="text-end">Action</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($locations as $location): ?>
                <?php $formId = 'locationForm' . (int) $location['id']; ?>
                <tr>
                    <td><input form="<?= h($formId) ?>" class="form-control form-control-sm" name="name" value="<?= h($location['name']) ?>" required></td>
                    <td><input form="<?= h($formId) ?>" class="form-control form-control-sm code-text" name="code" value="<?= h($location['code']) ?>" style="max-width: 100px;" required></td>
                    <td><input form="<?= h($formId) ?>" class="form-control form-control-sm" name="address" value="<?= h($location['address'] ?? '') ?>"></td>
                    <td><input form="<?= h($formId) ?>" class="form-control form-control-sm" name="phone" value="<?= h($location['phone'] ?? '') ?>" style="max-width: 140px;"></td>
                    <td>
                        <span class="badge <?= $location['is_active'] ? 'text-bg-success' : 'text-bg-light text-dark' ?>">
                            <?= $location['is_active'] ? 'Active' : 'Inactive' ?>
                        </span>
                    </td>
                    <td class="text-end">
                        <button form="<?= h($formId) ?>" class="btn btn-sm btn-outline-primary" type="submit" title="Save">
                            <i class="bi bi-save"></i>
                        </button>
                        <form method="post" action="<?= h(url('locations/setStatus/' . $location['id'])) ?>" class="d-inline">
                            <?= Auth::csrfField() ?>
                            <input type="hidden" name="is_active" value="<?= $location['is_active'] ? '0' : '1' ?>">
                            <button class="btn btn-sm btn-outline-secondary" type="submit" title="<?= $location['is_active'] ? 'Deactivate' : 'Activate' ?>">
                                <i class="bi bi-<?= $location['is_active'] ? 'pause' : 'play' ?>"></i>
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$locations): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">No locations yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
