<?php

class DeliveryController extends Controller
{
    public function index(): void
    {
        $this->requireRole(['manager', 'admin']);

        $filters = [
            'status' => $this->cleanString($this->get('status')),
            'search' => $this->cleanString($this->get('search')),
        ];

        if (!in_array($filters['status'], Delivery::statuses(), true)) {
            $filters['status'] = '';
        }

        $this->view('deliveries/index', [
            'pageTitle' => 'Deliveries',
            'deliveries' => (new Delivery())->all($filters),
            'statuses' => Delivery::statuses(),
            'filters' => $filters,
        ]);
    }

    public function updateStatus(): void
    {
        $this->requireRole(['manager', 'admin']);
        $this->validateCsrf();

        $deliveryId = (int) $this->post('delivery_id');
        $status = $this->cleanString($this->post('status'));

        if (!in_array($status, Delivery::statuses(), true)) {
            flash('error', 'Invalid delivery status.');
            $this->redirect('deliveries');
        }

        $delivery = (new Delivery())->find($deliveryId);

        if (!$delivery) {
            flash('error', 'Delivery record not found.');
            $this->redirect('deliveries');
        }

        (new Delivery())->updateStatus($deliveryId, $status);
        (new AuditLog())->create(Auth::id(), 'delivery_status_updated', 'deliveries', $deliveryId, $status);
        flash('success', 'Delivery status updated.');
        $this->redirect('deliveries');
    }
}
