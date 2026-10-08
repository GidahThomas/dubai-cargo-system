<?php

class NotificationController extends Controller
{
    public function index(): void
    {
        $this->requireLogin();

        $this->view('notifications/index', [
            'pageTitle' => 'Notifications',
            'notifications' => (new Notification())->forUser(Auth::id(), 100),
        ]);
    }

    public function readAll(): void
    {
        $this->requireLogin();
        $this->validateCsrf();

        (new Notification())->markAllRead(Auth::id());
        flash('success', 'All notifications marked as read.');
        $this->redirect('notifications');
    }

    public function markRead(): void
    {
        $this->requireAjaxLogin();
        $this->validateAjaxCsrf();

        $notificationId = (int) $this->post('notification_id');

        if ($notificationId < 1) {
            $this->json(['success' => false, 'message' => 'Invalid notification.'], 422);
        }

        (new Notification())->markRead($notificationId, Auth::id());

        $this->json([
            'success' => true,
            'message' => 'Notification marked as read.',
            'unread_count' => (new Notification())->unreadCount(Auth::id()),
        ]);
    }

    public function markAllRead(): void
    {
        $this->requireAjaxLogin();
        $this->validateAjaxCsrf();

        (new Notification())->markAllRead(Auth::id());

        $this->json([
            'success' => true,
            'message' => 'Notifications marked as read.',
            'unread_count' => 0,
        ]);
    }
}
