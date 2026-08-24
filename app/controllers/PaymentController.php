<?php

class PaymentController extends Controller
{
    public function index(): void
    {
        $this->requireLogin();

        $filters = [
            'status' => $this->cleanString($this->get('status')),
            'search' => $this->cleanString($this->get('search')),
            'method' => $this->cleanString($this->get('method')),
            'date_from' => $this->cleanString($this->get('date_from')),
            'date_to' => $this->cleanString($this->get('date_to')),
        ];

        if (Auth::role() === 'customer') {
            $filters['user_id'] = Auth::id();
        }

        $this->view('payments/index', [
            'pageTitle' => 'Payments',
            'payments' => (new Payment())->all($filters),
            'statuses' => Payment::statuses(),
            'filters' => $filters,
        ]);
    }

    public function submit(): void
    {
        $this->requireRole('customer');
        $this->validateCsrf();

        $paymentId = (int) $this->post('payment_id');
        $reference = $this->cleanString($this->post('payment_reference'));
        $method = $this->cleanString($this->post('method'));

        if ($reference === '' || !in_array($method, ['cash', 'bank_transfer', 'mobile_money', 'card'], true)) {
            flash('error', 'Payment reference and method are required.');
            $this->redirect('payments');
        }

        (new Payment())->submit($paymentId, Auth::id(), [
            'payment_reference' => $reference,
            'method' => $method,
            'notes' => $this->cleanString($this->post('notes')),
        ]);

        $this->notifyRoles(['manager', 'admin'], 'Payment submitted', 'A customer payment reference is ready for review.', 'warning');
        (new AuditLog())->create(Auth::id(), 'payment_submitted', 'payments', $paymentId, $reference);

        flash('success', 'Payment submitted for review.');
        $this->redirect('payments');
    }

    public function confirm(): void
    {
        $this->changeStatus('confirmed');
    }

    public function reject(): void
    {
        $this->changeStatus('rejected');
    }

    public function ajaxStatus(): void
    {
        $this->requireAjaxRole(['manager', 'admin']);
        $this->validateAjaxCsrf();

        $paymentId = (int) $this->post('payment_id');
        $status = $this->cleanString($this->post('status'));
        $notes = $this->cleanString($this->post('notes'));

        if (!in_array($status, Payment::statuses(), true) || $status === 'pending') {
            $this->json(['success' => false, 'message' => 'Invalid payment status.'], 422);
        }

        $paymentModel = new Payment();
        $payment = $paymentModel->find($paymentId);

        if (!$payment) {
            $this->json(['success' => false, 'message' => 'Payment record not found.'], 404);
        }

        $paymentModel->updateStatus($paymentId, $status, Auth::id(), $notes);
        $reference = $payment['order_number'] ?: ($payment['invoice_number'] ?? 'invoice payment');

        if (!empty($payment['user_id'])) {
            (new Notification())->create((int) $payment['user_id'], 'Payment ' . readable_status($status), 'Payment for ' . $reference . ' was ' . $status . '.', $status === 'confirmed' ? 'success' : 'danger');
        }

        (new AuditLog())->create(Auth::id(), 'ajax_payment_' . $status, 'payments', $paymentId, $reference);

        $this->json([
            'success' => true,
            'message' => 'Payment ' . $status . '.',
            'status' => $status,
            'status_label' => readable_status($status),
            'badge_class' => badge_class($status),
        ]);
    }

    private function changeStatus(string $status): void
    {
        $this->requireRole(['manager', 'admin']);
        $this->validateCsrf();

        $paymentId = (int) $this->post('payment_id');
        $notes = $this->cleanString($this->post('notes'));

        $paymentModel = new Payment();
        $payment = $paymentModel->find($paymentId);

        if (!$payment) {
            flash('error', 'Payment record not found.');
            $this->redirect('payments');
        }

        $paymentModel->updateStatus($paymentId, $status, Auth::id(), $notes);
        $reference = $payment['order_number'] ?: ($payment['invoice_number'] ?? 'invoice payment');

        if (!empty($payment['user_id'])) {
            (new Notification())->create((int) $payment['user_id'], 'Payment ' . readable_status($status), 'Payment for ' . $reference . ' was ' . $status . '.', $status === 'confirmed' ? 'success' : 'danger');
        }

        (new AuditLog())->create(Auth::id(), 'payment_' . $status, 'payments', $paymentId, $reference);

        flash('success', 'Payment ' . $status . '.');
        $this->redirect('payments');
    }

    private function notifyRoles(array $roles, string $title, string $message, string $type): void
    {
        $notification = new Notification();

        foreach ((new User())->byRoles($roles) as $user) {
            $notification->create((int) $user['id'], $title, $message, $type);
        }
    }
}
