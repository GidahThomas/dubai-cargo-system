<?php

class MessageQueueTest extends TestCase
{
    private const KEYS = ['WHATSAPP_TOKEN', 'WHATSAPP_PHONE_NUMBER_ID', 'SMS_PROVIDER', 'SMS_USERNAME', 'SMS_API_KEY', 'SMS_SECRET_KEY'];
    private array $savedEnv = [];
    private PDO $db;

    protected function setUp(): void
    {
        $this->savedEnv = array_intersect_key($_ENV, array_flip(self::KEYS));
        foreach (self::KEYS as $key) {
            unset($_ENV[$key]);
        }
        $this->db = Database::connect();
        $this->db->exec('DELETE FROM message_queue');
    }

    protected function tearDown(): void
    {
        foreach (self::KEYS as $key) {
            unset($_ENV[$key]);
        }
        $_ENV = $this->savedEnv + $_ENV;
        $this->db->exec('DELETE FROM message_queue');
    }

    public function testNothingIsQueuedWhenNoChannelIsConnected(): void
    {
        $this->assertEquals(0, CustomerMessenger::queue($this->customerId(), 'Order updated', 'Your order is ready.'));
        $this->assertEquals(0, $this->queued());
    }

    public function testCustomerUpdateIsQueuedOncePerConnectedChannel(): void
    {
        $this->connectBothChannels();

        $this->assertEquals(2, CustomerMessenger::queue($this->customerId(), 'Order updated', 'Your order is ready.'));
        $rows = $this->db->query('SELECT channel, status, body FROM message_queue ORDER BY CAST(channel AS CHAR)')->fetchAll();
        $this->assertEquals(['sms', 'whatsapp'], array_column($rows, 'channel'));
        $this->assertEquals(['pending', 'pending'], array_column($rows, 'status'));
        $this->assertStringContainsString(company_name() . ': Order updated', $rows[0]['body']);
    }

    public function testStaffAreNeverMessaged(): void
    {
        $this->connectBothChannels();
        $adminId = (int) $this->db->query('SELECT id FROM users WHERE role = "admin" ORDER BY id LIMIT 1')->fetchColumn();

        $this->assertEquals(0, CustomerMessenger::queue($adminId, 'Low stock', 'Restock soon.'));
        $this->assertEquals(0, $this->queued());
    }

    public function testNotificationQueuesTheCustomerCopy(): void
    {
        $this->connectBothChannels();
        (new Notification())->create($this->customerId(), 'Shipment updated', 'Tracking DCF-1 is now In Transit.');

        $this->assertEquals(2, $this->queued());
    }

    public function testFailedMessageIsRetriedThenGivenUp(): void
    {
        $queue = new MessageQueue();
        $id = $queue->enqueue('sms', $this->customerId(), '255700000003', 'Title', 'Body');

        foreach (MessageQueue::RETRY_MINUTES as $attempt => $minutes) {
            $row = $this->row($id);
            $this->assertEquals('pending', $row['status'], 'Still retrying before attempt ' . ($attempt + 1));
            $queue->markAttemptFailed($row, 'Gateway timeout');
        }
        $queue->markAttemptFailed($this->row($id), 'Gateway timeout');

        $row = $this->row($id);
        $this->assertEquals('failed', $row['status']);
        $this->assertEquals(count(MessageQueue::RETRY_MINUTES) + 1, (int) $row['attempts']);

        $this->assertEquals(1, $queue->retryFailed());
        $this->assertEquals('pending', $this->row($id)['status']);
    }

    private function connectBothChannels(): void
    {
        $_ENV['WHATSAPP_TOKEN'] = 'test-token';
        $_ENV['WHATSAPP_PHONE_NUMBER_ID'] = '123';
        $_ENV['SMS_PROVIDER'] = 'africastalking';
        $_ENV['SMS_USERNAME'] = 'sandbox';
        $_ENV['SMS_API_KEY'] = 'test-key';
    }

    private function customerId(): int
    {
        return (int) $this->db->query('SELECT id FROM users WHERE role = "customer" ORDER BY id LIMIT 1')->fetchColumn();
    }

    private function queued(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM message_queue')->fetchColumn();
    }

    private function row(int $id): array
    {
        $stmt = $this->db->prepare('SELECT * FROM message_queue WHERE id = ?');
        $stmt->execute([$id]);

        return $stmt->fetch();
    }
}
