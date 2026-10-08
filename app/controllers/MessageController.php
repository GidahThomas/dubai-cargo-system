<?php

/**
 * Message Log: WhatsApp and SMS messages queued for customers, their delivery status and errors.
 */
class MessageController extends Controller
{
    private const ROLES = ['manager', 'admin'];

    public function index(): void
    {
        $this->requireRole(self::ROLES);

        $status = $this->cleanString($this->get('status'));
        $queue = new MessageQueue();

        $this->view('messages/index', [
            'pageTitle' => 'Message Log',
            'messages' => $queue->recent(200, $status ?: null),
            'counts' => $queue->counts(),
            'status' => $status,
            'channels' => ['WhatsApp' => WhatsApp::isEnabled(), 'SMS' => Sms::isEnabled()],
        ]);
    }

    public function retry(): void
    {
        $this->requireRole(self::ROLES);
        $this->validateCsrf();

        if ($this->isPost()) {
            $count = (new MessageQueue())->retryFailed();
            (new AuditLog())->create(Auth::id(), 'messages_retried', 'message_queue', null, $count . ' message(s)');
            flash('success', $count . ' failed message(s) will be retried within a minute.');
        }

        $this->redirect('messages');
    }
}
