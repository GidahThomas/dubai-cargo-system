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
        $locationId = $this->activeLocationId();

        $this->view('manager/dashboard', [
            'pageTitle' => 'Store Manager Dashboard',
            'activeLocation' => $locationId !== null ? (new Location())->find($locationId) : null,
            'productCount' => (new Product())->countActive(),
            'pendingOrders' => (new Order())->countByStatus('pending', $locationId),
            'pendingPayments' => (new Payment())->countByStatus('pending'),
            'inTransitShipments' => (new Shipment())->countByStatus('in_transit'),
            'pendingQuotations' => (new Quotation())->countByStatus('pending'),
            'approvedQuotations' => (new Quotation())->countByStatus('approved'),
            'generatedInvoices' => (new Invoice())->all(),
            'pendingDeliveries' => (new Delivery())->countByStatus('pending'),
            'completedDeliveries' => (new Delivery())->countByStatus('delivered'),
            'lowStock' => (new Product())->lowStock(8, $locationId),
            'recentOrders' => (new Order())->recent(8, $locationId),
            'chartData' => [
                'dailySales' => $ledger->chartSeries('daily', $locationId),
                'weeklySales' => $ledger->chartSeries('weekly', $locationId),
                'monthlySales' => $ledger->chartSeries('monthly', $locationId),
                'ordersByStatus' => (new Order())->statusCounts($locationId),
                'paymentsByStatus' => (new Payment())->statusCounts(),
                'shipmentsByStatus' => (new Shipment())->statusCounts(),
            ],
        ]);
    }

    private function admin(): void
    {
        $ledger = new StoreLedger();
        $locationId = $this->activeLocationId();

        $this->view('admin/dashboard', [
            'pageTitle' => 'Executive Dashboard',
            'activeLocation' => $locationId !== null ? (new Location())->find($locationId) : null,
            'totalUsers' => (new User())->countByRole(),
            'totalCustomers' => (new User())->countByRole('customer'),
            'productCount' => (new Product())->countActive(),
            'totalOrders' => (new Order())->countByStatus(null, $locationId),
            'salesTotal' => (new Order())->salesTotal($locationId),
            'shipmentCount' => (new Shipment())->countByStatus(),
            'pendingQuotations' => (new Quotation())->countByStatus('pending'),
            'approvedQuotations' => (new Quotation())->countByStatus('approved'),
            'generatedInvoices' => (new Invoice())->all(),
            'pendingDeliveries' => (new Delivery())->countByStatus('pending'),
            'completedDeliveries' => (new Delivery())->countByStatus('delivered'),
            'monthlySales' => (new Order())->monthlySales($locationId),
            'recentOrders' => (new Order())->recent(8, $locationId),
            'auditLogs' => (new AuditLog())->all(),
            'chartData' => [
                'dailySales' => $ledger->chartSeries('daily', $locationId),
                'weeklySales' => $ledger->chartSeries('weekly', $locationId),
                'monthlySales' => $ledger->chartSeries('monthly', $locationId),
                'ordersByStatus' => (new Order())->statusCounts($locationId),
                'paymentsByStatus' => (new Payment())->statusCounts(),
                'shipmentsByStatus' => (new Shipment())->statusCounts(),
            ],
        ]);
    }
}
