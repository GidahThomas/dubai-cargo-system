<?php

class DashboardController extends Controller
{
    public function index(): void
    {
        $this->requireLogin();

        match (Auth::role()) {
            'admin', 'super_admin', 'company_owner' => $this->admin(),
            'manager', 'sales_officer', 'store_manager', 'accountant', 'cargo_officer' => $this->manager(),
            default => $this->customer(),
        };
    }

    private function customer(): void
    {
        $userId = Auth::id();
        $orderModel = new Order();
        $shipmentModel = new Shipment();
        $notificationModel = new Notification();

        $trackingResult = null;
        $trackingNumber = $this->cleanString($this->get('tracking_number'));

        if ($trackingNumber !== '') {
            $trackingResult = $shipmentModel->findByTrackingNumber($trackingNumber, $userId);
        }

        $this->view('customer/dashboard', [
            'pageTitle' => 'Customer Dashboard',
            'orders' => $orderModel->all(['user_id' => $userId]),
            'notifications' => $notificationModel->forUser($userId, 8),
            'unreadCount' => $notificationModel->unreadCount($userId),
            'trackingResult' => $trackingResult,
            'profile' => (new Customer())->findByUserId($userId),
        ]);
    }

    private function manager(): void
    {
        $ledger = new StoreLedger();

        $this->view('manager/dashboard', [
            'pageTitle' => 'Store Manager Dashboard',
            'productCount' => (new Product())->countActive(),
            'pendingOrders' => (new Order())->countByStatus('pending'),
            'pendingPayments' => (new Payment())->countByStatus('pending'),
            'inTransitShipments' => (new Shipment())->countByStatus('in_transit'),
            'pendingQuotations' => (new Quotation())->countByStatus('pending'),
            'approvedQuotations' => (new Quotation())->countByStatus('approved'),
            'generatedInvoices' => (new Invoice())->all(),
            'pendingDeliveries' => (new Delivery())->countByStatus('pending'),
            'completedDeliveries' => (new Delivery())->countByStatus('delivered'),
            'lowStock' => (new Product())->lowStock(8),
            'recentOrders' => (new Order())->recent(8),
            'chartData' => [
                'dailySales' => $ledger->chartSeries('daily'),
                'weeklySales' => $ledger->chartSeries('weekly'),
                'monthlySales' => $ledger->chartSeries('monthly'),
                'ordersByStatus' => (new Order())->statusCounts(),
                'paymentsByStatus' => (new Payment())->statusCounts(),
                'shipmentsByStatus' => (new Shipment())->statusCounts(),
            ],
        ]);
    }

    private function admin(): void
    {
        $ledger = new StoreLedger();

        $this->view('admin/dashboard', [
            'pageTitle' => 'Executive Dashboard',
            'totalUsers' => (new User())->countByRole(),
            'totalCustomers' => (new User())->countByRole('customer'),
            'productCount' => (new Product())->countActive(),
            'totalOrders' => (new Order())->countByStatus(),
            'salesTotal' => (new Order())->salesTotal(),
            'shipmentCount' => (new Shipment())->countByStatus(),
            'pendingQuotations' => (new Quotation())->countByStatus('pending'),
            'approvedQuotations' => (new Quotation())->countByStatus('approved'),
            'generatedInvoices' => (new Invoice())->all(),
            'pendingDeliveries' => (new Delivery())->countByStatus('pending'),
            'completedDeliveries' => (new Delivery())->countByStatus('delivered'),
            'monthlySales' => (new Order())->monthlySales(),
            'recentOrders' => (new Order())->recent(8),
            'auditLogs' => (new AuditLog())->all(),
            'chartData' => [
                'dailySales' => $ledger->chartSeries('daily'),
                'weeklySales' => $ledger->chartSeries('weekly'),
                'monthlySales' => $ledger->chartSeries('monthly'),
                'ordersByStatus' => (new Order())->statusCounts(),
                'paymentsByStatus' => (new Payment())->statusCounts(),
                'shipmentsByStatus' => (new Shipment())->statusCounts(),
            ],
        ]);
    }
}
