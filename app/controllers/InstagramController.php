<?php

class InstagramController extends Controller
{
    private const ROLES = ['manager', 'admin'];

    public function index(): void
    {
        $this->requireRole(self::ROLES);

        $postModel = new InstagramPost();

        $this->view('instagram/index', [
            'pageTitle' => 'Instagram Import',
            'exportDir' => InstagramImporter::DEFAULT_EXPORT_DIR,
            'exportReady' => is_dir(InstagramImporter::DEFAULT_EXPORT_DIR) && (glob(InstagramImporter::DEFAULT_EXPORT_DIR . '/*') ?: []) !== [],
            'counts' => $postModel->counts(),
            'posts' => $postModel->all(false, 200),
        ]);
    }

    public function import(): void
    {
        $this->requireRole(self::ROLES);
        $this->validateCsrf();

        if (!$this->isPost()) {
            $this->redirect('instagram');
        }

        set_time_limit(0);

        try {
            $stats = (new InstagramImporter($this->post('create_products') === '1', Auth::id()))->import();
        } catch (Throwable $exception) {
            flash('error', 'Import failed: ' . $exception->getMessage());
            $this->redirect('instagram');
        }

        (new AuditLog())->create(Auth::id(), 'instagram_import', 'instagram_posts', null, json_encode(array_diff_key($stats, ['warnings' => true])));

        $message = sprintf(
            'Imported %d new post(s) with %d photo/video file(s); %d already imported; %d draft product(s) created.',
            $stats['imported'],
            $stats['media_copied'],
            $stats['skipped_existing'],
            $stats['products_created']
        );
        if ($stats['warnings']) {
            $message .= ' ' . count($stats['warnings']) . ' file(s) were missing from the export.';
        }

        flash('success', $message);
        $this->redirect('instagram');
    }

    public function visibility(int $postId): void
    {
        $this->requireRole(self::ROLES);
        $this->validateCsrf();

        if ($this->isPost()) {
            (new InstagramPost())->setVisible($postId, $this->post('visible') === '1');
            flash('success', 'Gallery updated.');
        }

        $this->redirect('instagram');
    }
}
