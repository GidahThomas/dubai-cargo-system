<?php

class LocationController extends Controller
{
    public function index(): void
    {
        $this->requireRole(['manager', 'admin']);

        $this->view('locations/index', [
            'pageTitle' => 'Locations',
            'locations' => (new Location())->all(),
        ]);
    }

    public function store(): void
    {
        $this->requireRole(['manager', 'admin']);
        $this->validateCsrf();

        $data = $this->locationPayload();

        if ($data['name'] === '' || $data['code'] === '') {
            flash('error', 'Name and code are required.');
            $this->redirect('locations');
        }

        try {
            $locationId = (new Location())->create($data);
            $this->seedInventoryForNewLocation($locationId);
            (new AuditLog())->create(Auth::id(), 'location_created', 'locations', $locationId, $data['name']);
            flash('success', 'Location added. Existing products now have a zero-stock row here — update quantities from the Products page.');
        } catch (Throwable $exception) {
            flash('error', 'Could not create location: ' . $exception->getMessage());
        }

        $this->redirect('locations');
    }

    public function update(int $id): void
    {
        $this->requireRole(['manager', 'admin']);
        $this->validateCsrf();

        $data = $this->locationPayload();

        if ($data['name'] === '' || $data['code'] === '') {
            flash('error', 'Name and code are required.');
            $this->redirect('locations');
        }

        (new Location())->update($id, $data);
        (new AuditLog())->create(Auth::id(), 'location_updated', 'locations', $id, $data['name']);
        flash('success', 'Location updated.');
        $this->redirect('locations');
    }

    public function setStatus(int $id): void
    {
        $this->requireRole(['manager', 'admin']);
        $this->validateCsrf();

        $isActive = $this->post('is_active') === '1';
        (new Location())->setStatus($id, $isActive);
        (new AuditLog())->create(Auth::id(), 'location_status_updated', 'locations', $id, $isActive ? 'active' : 'inactive');
        flash('success', 'Location status updated.');
        $this->redirect('locations');
    }

    public function switch(): void
    {
        $this->requireLogin();
        $this->validateCsrf();

        $locationId = $this->post('location_id');
        $_SESSION['active_location_id'] = ($locationId === '' || $locationId === null) ? null : (int) $locationId;

        $this->redirect($this->cleanString($this->post('return_to')) ?: 'dashboard');
    }

    private function seedInventoryForNewLocation(int $locationId): void
    {
        $locationModel = new Location();

        foreach ((new Product())->all([], false) as $product) {
            $locationModel->seedInventoryRow((int) $product['id'], $locationId);
        }
    }

    private function locationPayload(): array
    {
        return [
            'name' => $this->cleanString($this->post('name')),
            'code' => $this->cleanString($this->post('code')),
            'address' => $this->cleanString($this->post('address')),
            'phone' => $this->cleanString($this->post('phone')),
        ];
    }
}
