<?php

class OrderController extends Controller
{
    public function index(): void
    {
        $this->requireLogin();

        $filters = [
            'status' => $this->cleanString($this->get('status')),
            'search' => $this->cleanString($this->get('search')),
            'date_from' => $this->cleanString($this->get('date_from')),
            'date_to' => $this->cleanString($this->get('date_to')),
        ];

        if (Auth::role() === 'customer') {
            $filters['user_id'] = Auth::id();
        }

        $this->view('orders/index', [
            'pageTitle' => 'Orders',
            'orders' => (new Order())->all($filters),
            'statuses' => Order::statuses(),
            'filters' => $filters,
        ]);
    }

    public function store(): void
    {
        $this->requireRole('customer');
        $this->validateCsrf();

        $productId = (int) $this->post('product_id');
        $quantity = max(1, (int) $this->post('quantity', 1));
        $address = $this->cleanString($this->post('shipping_address'));
        $notes = $this->cleanString($this->post('notes'));

        if ($productId < 1 || $address === '') {
            flash('error', 'Please choose a product and provide a shipping address.');
            $this->redirect('products');
        }

        try {
            $orderId = (new Order())->createFromProduct(Auth::id(), $productId, $quantity, $address, $notes);
            $order = (new Order())->find($orderId);

            $this->notifyRoles(['manager', 'admin'], 'New order placed', 'Order ' . $order['order_number'] . ' is waiting for review.', 'info');
            (new AuditLog())->create(Auth::id(), 'order_created', 'orders', $orderId, $order['order_number']);

            flash('success', 'Order placed successfully. Please submit your payment reference.');
            $this->redirect('orders');
        } catch (Throwable $exception) {
            flash('error', 'Order could not be placed: ' . $exception->getMessage());
            $this->redirect('products');
        }
    }

    public function updateStatus(): void
    {
        $this->requireRole(['manager', 'admin']);
        $this->validateCsrf();

        $orderId = (int) $this->post('order_id');
        $status = $this->cleanString($this->post('status'));

        if (!in_array($status, Order::statuses(), true)) {
            flash('error', 'Invalid order status.');
            $this->redirect('orders');
        }

        $orderModel = new Order();
        $order = $orderModel->find($orderId);

        if (!$order) {
            flash('error', 'Order not found.');
            $this->redirect('orders');
        }

        $orderModel->updateStatus($orderId, $status);
        (new Notification())->create((int) $order['user_id'], 'Order updated', 'Your order ' . $order['order_number'] . ' is now ' . readable_status($status) . '.', 'info');
        (new AuditLog())->create(Auth::id(), 'order_status_updated', 'orders', $orderId, $status);

        flash('success', 'Order status updated.');
        $this->redirect('orders');
    }

    public function ajaxUpdateStatus(): void
    {
        $this->requireAjaxRole(['manager', 'admin']);
        $this->validateAjaxCsrf();

        $orderId = (int) $this->post('order_id');
        $status = $this->cleanString($this->post('status'));

        if (!in_array($status, Order::statuses(), true)) {
            $this->json(['success' => false, 'message' => 'Invalid order status.'], 422);
        }

        $orderModel = new Order();
        $order = $orderModel->find($orderId);

        if (!$order) {
            $this->json(['success' => false, 'message' => 'Order not found.'], 404);
        }

        $orderModel->updateStatus($orderId, $status);
        (new Notification())->create((int) $order['user_id'], 'Order updated', 'Your order ' . $order['order_number'] . ' is now ' . readable_status($status) . '.', 'info');
        (new AuditLog())->create(Auth::id(), 'ajax_order_status_updated', 'orders', $orderId, $status);

        $this->json([
            'success' => true,
            'message' => 'Order status updated.',
            'status' => $status,
            'status_label' => readable_status($status),
            'badge_class' => badge_class($status),
        ]);
    }

    public function cancel(): void
    {
        $this->requireLogin();
        $this->validateCsrf();

        $orderId = (int) $this->post('order_id');
        $userId = Auth::role() === 'customer' ? Auth::id() : null;

        if ((new Order())->cancel($orderId, $userId)) {
            (new AuditLog())->create(Auth::id(), 'order_cancelled', 'orders', $orderId);
            flash('success', 'Order cancelled and inventory restored.');
        } else {
            flash('error', 'Only pending orders can be cancelled.');
        }

        $this->redirect('orders');
    }

    private function notifyRoles(array $roles, string $title, string $message, string $type): void
    {
        $notification = new Notification();

        foreach ((new User())->byRoles($roles) as $user) {
            $notification->create((int) $user['id'], $title, $message, $type);
        }
    }
}
