<?php

class NotificationController extends Controller
{
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
