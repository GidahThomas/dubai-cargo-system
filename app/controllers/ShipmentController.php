<?php

class ShipmentController extends Controller
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

        $trackingResult = null;
        $trackingNumber = $this->cleanString($this->get('tracking_number'));

        if ($trackingNumber !== '') {
            $trackingResult = (new Shipment())->findByTrackingNumber(
                $trackingNumber,
                Auth::role() === 'customer' ? Auth::id() : null
            );
        }

        $this->view('shipments/index', [
            'pageTitle' => 'Shipments',
            'shipments' => (new Shipment())->all($filters),
            'orders' => Auth::role() === 'customer' ? [] : (new Order())->all(),
            'statuses' => Shipment::statuses(),
            'filters' => $filters,
            'trackingResult' => $trackingResult,
        ]);
    }

    public function store(): void
    {
        $this->requireRole(['manager', 'admin']);
        $this->validateCsrf();

        $orderId = (int) $this->post('order_id');

        if ($orderId < 1) {
            flash('error', 'Please choose an order.');
            $this->redirect('shipments');
        }

        try {
            $shipmentId = (new Shipment())->createForOrder($orderId, [
                'tracking_number' => $this->cleanString($this->post('tracking_number')),
                'status' => $this->cleanString($this->post('status')),
                'origin' => $this->cleanString($this->post('origin')),
                'destination' => $this->cleanString($this->post('destination')),
                'carrier' => $this->cleanString($this->post('carrier')),
                'expected_arrival' => $this->cleanString($this->post('expected_arrival')),
                'notes' => $this->cleanString($this->post('notes')),
            ], Auth::id());

            $shipment = (new Shipment())->find($shipmentId);
            (new Notification())->create((int) $shipment['user_id'], 'Shipment created', 'Tracking number ' . $shipment['tracking_number'] . ' has been created for your order.', 'success');
            (new AuditLog())->create(Auth::id(), 'shipment_created', 'shipments', $shipmentId, $shipment['tracking_number']);
            flash('success', 'Shipment record created.');
        } catch (Throwable $exception) {
            flash('error', 'Shipment could not be created: ' . $exception->getMessage());
        }

        $this->redirect('shipments');
    }

    public function updateStatus(): void
    {
        $this->requireRole(['manager', 'admin']);
        $this->validateCsrf();

        $shipmentId = (int) $this->post('shipment_id');
        $status = $this->cleanString($this->post('status'));
        $notes = $this->cleanString($this->post('notes'));

        if (!in_array($status, Shipment::statuses(), true)) {
            flash('error', 'Invalid shipment status.');
            $this->redirect('shipments');
        }

        $shipmentModel = new Shipment();
        $shipment = $shipmentModel->find($shipmentId);

        if (!$shipment) {
            flash('error', 'Shipment not found.');
            $this->redirect('shipments');
        }

        $shipmentModel->updateStatus($shipmentId, $status, $notes);
        (new Notification())->create((int) $shipment['user_id'], 'Shipment updated', 'Tracking ' . $shipment['tracking_number'] . ' is now ' . readable_status($status) . '.', 'info');
        (new AuditLog())->create(Auth::id(), 'shipment_status_updated', 'shipments', $shipmentId, $status);

        flash('success', 'Shipment status updated.');
        $this->redirect('shipments');
    }

    public function ajaxUpdateStatus(): void
    {
        $this->requireAjaxRole(['manager', 'admin']);
        $this->validateAjaxCsrf();

        $shipmentId = (int) $this->post('shipment_id');
        $status = $this->cleanString($this->post('status'));
        $notes = $this->cleanString($this->post('notes'));

        if (!in_array($status, Shipment::statuses(), true)) {
            $this->json(['success' => false, 'message' => 'Invalid shipment status.'], 422);
        }

        $shipmentModel = new Shipment();
        $shipment = $shipmentModel->find($shipmentId);

        if (!$shipment) {
            $this->json(['success' => false, 'message' => 'Shipment not found.'], 404);
        }

        $shipmentModel->updateStatus($shipmentId, $status, $notes);
        (new Notification())->create((int) $shipment['user_id'], 'Shipment updated', 'Tracking ' . $shipment['tracking_number'] . ' is now ' . readable_status($status) . '.', 'info');
        (new AuditLog())->create(Auth::id(), 'ajax_shipment_status_updated', 'shipments', $shipmentId, $status);

        $this->json([
            'success' => true,
            'message' => 'Shipment status updated.',
            'status' => $status,
            'status_label' => readable_status($status),
            'badge_class' => badge_class($status),
        ]);
    }

    public function track(): void
    {
        $this->requireLogin();
        $trackingNumber = urlencode($this->cleanString($this->get('tracking_number')));
        $this->redirect('shipments&tracking_number=' . $trackingNumber);
    }
}
