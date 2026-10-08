<?php

/**
 * Audit Review: the Company Owner's view of every recorded action (logins, payments,
 * shipment updates, invoices, settings changes), with filters and paging.
 */
class AuditController extends Controller
{
    private const PER_PAGE = 50;

    public function index(): void
    {
        $this->requireRole('admin');

        $filters = [
            'action' => $this->cleanString($this->get('action')),
            'user_id' => (int) $this->get('user_id', 0) ?: null,
            'date_from' => $this->validDate($this->get('date_from')),
            'date_to' => $this->validDate($this->get('date_to')),
            'search' => $this->cleanString($this->get('search')),
        ];

        $auditLog = new AuditLog();
        $total = $auditLog->count($filters);
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = min($pages, max(1, (int) $this->get('page', 1)));

        $this->view('audit/index', [
            'pageTitle' => 'Audit Review',
            'logs' => $auditLog->all($filters, self::PER_PAGE, ($page - 1) * self::PER_PAGE),
            'actions' => $auditLog->actions(),
            'users' => (new User())->byRoles(['admin', 'manager', 'customer']),
            'filters' => $filters,
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
        ]);
    }

    private function validDate(mixed $value): ?string
    {
        $value = trim((string) $value);

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : null;
    }
}
