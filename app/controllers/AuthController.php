<?php

class AuthController extends Controller
{
    private const MAX_LOGIN_ATTEMPTS = 5;
    private const LOGIN_LOCKOUT_MINUTES = 15;

    public function login(): void
    {
        if (Auth::check()) {
            $this->redirect('dashboard');
        }

        if ($this->isPost()) {
            $this->validateCsrf();

            $auditLog = new AuditLog();
            $ipAddress = (string) ($_SERVER['REMOTE_ADDR'] ?? '');

            if ($ipAddress !== '' && $auditLog->recentFailedLogins($ipAddress, self::LOGIN_LOCKOUT_MINUTES) >= self::MAX_LOGIN_ATTEMPTS) {
                flash('error', 'Too many failed login attempts. Please try again in ' . self::LOGIN_LOCKOUT_MINUTES . ' minutes.');
                $this->redirect('login');
            }

            $email = filter_var($this->post('email'), FILTER_VALIDATE_EMAIL);
            $password = (string) $this->post('password');

            if (!$email || $password === '') {
                flash('error', 'Enter a valid email address and password.');
                $this->redirect('login');
            }

            $userModel = new User();
            $user = $userModel->findByEmail($email);

            if (!$user || $user['status'] !== 'active' || !password_verify($password, $user['password'])) {
                $auditLog->create(null, 'login_failed', 'users', null, 'Email: ' . $email);
                flash('error', 'Invalid credentials or inactive account.');
                $this->redirect('login');
            }

            Auth::login($user);
            $auditLog->create((int) $user['id'], 'login', 'users', (int) $user['id']);
            flash('success', 'Welcome back, ' . $user['name'] . '.');
            $this->redirect('dashboard');
        }

        $this->view('auth/login', ['pageTitle' => 'Sign In']);
    }

    public function register(): void
    {
        if (Auth::check()) {
            $this->redirect('dashboard');
        }

        if ($this->isPost()) {
            $this->validateCsrf();

            $name = $this->cleanString($this->post('name'));
            $email = filter_var($this->post('email'), FILTER_VALIDATE_EMAIL);
            $password = (string) $this->post('password');
            $confirmPassword = (string) $this->post('confirm_password');

            if ($name === '' || !$email || strlen($password) < Auth::MIN_PASSWORD_LENGTH || $password !== $confirmPassword) {
                flash('error', 'Please complete the form. Passwords must match and be at least ' . Auth::MIN_PASSWORD_LENGTH . ' characters.');
                $this->redirect('register');
            }

            $userModel = new User();

            if ($userModel->findByEmail($email)) {
                flash('error', 'That email address is already registered.');
                $this->redirect('register');
            }

            try {
                $userId = $userModel->create([
                    'name' => $name,
                    'email' => $email,
                    'password' => $password,
                    'role' => 'customer',
                    'phone' => $this->cleanString($this->post('phone')),
                    'address' => $this->cleanString($this->post('address')),
                    'city' => $this->cleanString($this->post('city')),
                    'country' => $this->cleanString($this->post('country')) ?: 'Tanzania',
                ]);

                (new Notification())->create($userId, 'Account created', 'Welcome. You can now browse products and place cargo orders.', 'success');
                (new AuditLog())->create($userId, 'customer_registered', 'users', $userId);

                flash('success', 'Registration successful. Please sign in.');
                $this->redirect('login');
            } catch (Throwable $exception) {
                flash('error', 'Registration failed: ' . $exception->getMessage());
                $this->redirect('register');
            }
        }

        $this->view('auth/register', ['pageTitle' => 'Customer Registration']);
    }

    public function logout(): void
    {
        $userId = Auth::id();

        if ($userId !== null) {
            (new AuditLog())->create($userId, 'logout', 'users', $userId);
        }

        Auth::logout();
        Auth::startSecureSession();
        flash('success', 'You have been signed out.');
        $this->redirect('login');
    }
}
