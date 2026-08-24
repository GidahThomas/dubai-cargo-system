<?php

class UserController extends Controller
{
    public function index(): void
    {
        $this->requireRole(['manager', 'admin']);

        $filters = [
            'search' => $this->cleanString($this->get('search')),
        ];

        $role = Auth::role() === 'manager' ? 'customer' : $this->cleanString($this->get('role'));

        if ($role !== '') {
            $filters['role'] = $role;
        }

        $this->view('users/index', [
            'pageTitle' => Auth::role() === 'manager' ? 'Customers' : 'Users',
            'users' => (new User())->all($filters),
            'filters' => $filters,
        ]);
    }

    public function store(): void
    {
        $this->requireRole('admin');
        $this->validateCsrf();

        $data = $this->userPayload();

        if (!$this->validUserData($data, true)) {
            flash('error', 'Name, valid email, role, and password are required.');
            $this->redirect('users');
        }

        if ((new User())->findByEmail($data['email'])) {
            flash('error', 'Email address already exists.');
            $this->redirect('users');
        }

        $userId = (new User())->create($data);
        (new AuditLog())->create(Auth::id(), 'user_created', 'users', $userId, $data['email']);

        flash('success', 'User created.');
        $this->redirect('users');
    }

    public function update(int $id): void
    {
        $this->requireRole('admin');
        $this->validateCsrf();

        $data = $this->userPayload(false);

        if (!$this->validUserData($data, false)) {
            flash('error', 'Name, valid email, and role are required.');
            $this->redirect('users');
        }

        (new User())->update($id, $data);
        (new AuditLog())->create(Auth::id(), 'user_updated', 'users', $id, $data['email']);

        flash('success', 'User updated.');
        $this->redirect('users');
    }

    public function delete(int $id): void
    {
        $this->requireRole('admin');
        $this->validateCsrf();

        if ($id === Auth::id()) {
            flash('error', 'You cannot deactivate your own account.');
            $this->redirect('users');
        }

        (new User())->setStatus($id, 'inactive');
        (new AuditLog())->create(Auth::id(), 'user_deactivated', 'users', $id);

        flash('success', 'User deactivated.');
        $this->redirect('users');
    }

    public function updateProfile(): void
    {
        $this->requireLogin();
        $this->validateCsrf();

        $name = $this->cleanString($this->post('name'));

        if ($name === '') {
            flash('error', 'Name is required.');
            $this->redirect('dashboard');
        }

        (new User())->updateProfile(Auth::id(), [
            'name' => $name,
            'phone' => $this->cleanString($this->post('phone')),
            'address' => $this->cleanString($this->post('address')),
        ]);

        if (Auth::role() === 'customer') {
            (new Customer())->updateDetails(Auth::id(), [
                'city' => $this->cleanString($this->post('city')),
                'country' => $this->cleanString($this->post('country')),
            ]);
        }

        $_SESSION['user']['name'] = $name;
        (new AuditLog())->create(Auth::id(), 'profile_updated', 'users', Auth::id());

        flash('success', 'Profile updated.');
        $this->redirect('dashboard');
    }

    private function userPayload(bool $withPassword = true): array
    {
        $payload = [
            'name' => $this->cleanString($this->post('name')),
            'email' => strtolower($this->cleanString($this->post('email'))),
            'role' => $this->cleanString($this->post('role')),
            'phone' => $this->cleanString($this->post('phone')),
            'address' => $this->cleanString($this->post('address')),
            'status' => in_array($this->post('status'), ['active', 'inactive'], true) ? $this->post('status') : 'active',
            'city' => $this->cleanString($this->post('city')),
            'country' => $this->cleanString($this->post('country')) ?: 'United Arab Emirates',
        ];

        $password = (string) $this->post('password');

        if ($withPassword || $password !== '') {
            $payload['password'] = $password;
        }

        return $payload;
    }

    private function validUserData(array $data, bool $passwordRequired): bool
    {
        if ($data['name'] === '' || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        if (!in_array($data['role'], ['customer', 'manager', 'admin'], true)) {
            return false;
        }

        return !$passwordRequired || strlen($data['password'] ?? '') >= 6;
    }
}
