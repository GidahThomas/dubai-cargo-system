<div class="page-header">
    <div>
        <p class="eyebrow"><?= Auth::role() === 'manager' ? 'Customer directory' : 'Access control' ?></p>
        <h1><?= Auth::role() === 'manager' ? 'Customers' : 'Users' ?></h1>
    </div>
</div>

<section class="filter-panel">
    <form class="row g-3 align-items-end" method="get" action="<?= h(url()) ?>">
        <input type="hidden" name="url" value="users">
        <div class="col-md-4">
            <label class="form-label" for="search"><i class="bi bi-search me-1"></i>Search</label>
            <input class="form-control" id="search" name="search" value="<?= h($filters['search'] ?? '') ?>" placeholder="Name, email, phone">
        </div>
        <?php if (Auth::role() === 'admin'): ?>
            <div class="col-md-3">
                <label class="form-label" for="role">Role</label>
                <select class="form-select" id="role" name="role">
                    <option value="">All roles</option>
                    <?php foreach (['customer', 'manager', 'admin'] as $role): ?>
                        <option value="<?= h($role) ?>" <?= ($filters['role'] ?? '') === $role ? 'selected' : '' ?>><?= h(readable_status($role)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>
        <div class="col-md-2">
            <button class="btn btn-dark w-100" type="submit"><i class="bi bi-funnel"></i> Filter</button>
        </div>
    </form>
</section>

<?php if (Auth::role() === 'admin'): ?>
    <section class="panel mt-4">
        <div class="panel-header">
            <h2><i class="bi bi-person-plus me-2"></i>Create User</h2>
        </div>
        <form method="post" action="<?= h(url('users/store')) ?>" class="row g-3 align-items-end needs-validation" novalidate>
            <?= Auth::csrfField() ?>
            <div class="col-md-3">
                <label class="form-label" for="name_new">Name</label>
                <input class="form-control" id="name_new" name="name" placeholder="e.g. Juma Mwakalinga" required>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="email_new">Email</label>
                <input type="email" class="form-control" id="email_new" name="email" placeholder="you@example.com" required>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="role_new">Role</label>
                <select class="form-select" id="role_new" name="role">
                    <option value="customer">Customer</option>
                    <option value="manager">Manager</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="password_new">Password</label>
                <input type="password" class="form-control" id="password_new" name="password" minlength="6" placeholder="At least 6 characters" required>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100" type="submit"><i class="bi bi-person-plus me-2"></i>Create</button>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="phone_new">Phone</label>
                <input class="form-control" id="phone_new" name="phone" placeholder="e.g. 0652 532 646">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="address_new">Address</label>
                <input class="form-control" id="address_new" name="address" placeholder="Street, ward, and landmark">
            </div>
            <div class="col-md-2">
                <label class="form-label" for="city_new">City</label>
                <input class="form-control" id="city_new" name="city" placeholder="e.g. Dar es Salaam">
            </div>
            <div class="col-md-2">
                <label class="form-label" for="country_new">Country</label>
                <input class="form-control" id="country_new" name="country" placeholder="e.g. Tanzania">
            </div>
            <div class="col-md-2">
                <label class="form-label" for="status_new">Status</label>
                <select class="form-select" id="status_new" name="status">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
        </form>
    </section>
<?php endif; ?>

<section class="panel mt-4">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Role</th>
                <th>Status</th>
                <th>Customer Code</th>
                <?php if (Auth::role() === 'admin'): ?><th class="text-end">Action</th><?php endif; ?>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= h($user['name']) ?></td>
                    <td><?= h($user['email']) ?></td>
                    <td><?= h($user['phone'] ?? '') ?></td>
                    <td><span class="badge text-bg-light text-dark"><?= h(readable_status($user['role'])) ?></span></td>
                    <td><span class="badge <?= h(badge_class($user['status'])) ?>"><?= h(readable_status($user['status'])) ?></span></td>
                    <td><?= h($user['customer_code'] ?? '') ?></td>
                    <?php if (Auth::role() === 'admin'): ?>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#editUser<?= (int) $user['id'] ?>" title="Edit user">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <form method="post" action="<?= h(url('users/delete/' . $user['id'])) ?>" class="d-inline" data-confirm="Deactivate this user?">
                                <?= Auth::csrfField() ?>
                                <button class="btn btn-sm btn-outline-danger" type="submit" title="Deactivate"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    <?php endif; ?>
                </tr>
                <?php if (Auth::role() === 'admin'): ?>
                    <tr class="collapse" id="editUser<?= (int) $user['id'] ?>">
                        <td colspan="7" class="bg-light">
                            <form method="post" action="<?= h(url('users/update/' . $user['id'])) ?>" class="row g-2 align-items-end needs-validation" novalidate>
                                <?= Auth::csrfField() ?>
                                <div class="col-md-2">
                                    <label class="form-label">Name</label>
                                    <input class="form-control" name="name" value="<?= h($user['name']) ?>" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" name="email" value="<?= h($user['email']) ?>" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Role</label>
                                    <select class="form-select" name="role">
                                        <?php foreach (['customer', 'manager', 'admin'] as $role): ?>
                                            <option value="<?= h($role) ?>" <?= $user['role'] === $role ? 'selected' : '' ?>><?= h(readable_status($role)) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Phone</label>
                                    <input class="form-control" name="phone" value="<?= h($user['phone'] ?? '') ?>" placeholder="e.g. 0652 532 646">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Status</label>
                                    <select class="form-select" name="status">
                                        <option value="active" <?= $user['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                        <option value="inactive" <?= $user['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Password</label>
                                    <input type="password" class="form-control" name="password" placeholder="Keep current">
                                </div>
                                <div class="col-md-10">
                                    <label class="form-label">Address</label>
                                    <input class="form-control" name="address" value="<?= h($user['address'] ?? '') ?>" placeholder="Street, ward, and landmark">
                                </div>
                                <div class="col-md-2">
                                    <button class="btn btn-primary w-100" type="submit"><i class="bi bi-save me-2"></i>Save</button>
                                </div>
                            </form>
                        </td>
                    </tr>
                <?php endif; ?>
            <?php endforeach; ?>
            <?php if (!$users): ?>
                <tr><td colspan="<?= Auth::role() === 'admin' ? 7 : 6 ?>" class="text-center text-muted py-4">No users found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
