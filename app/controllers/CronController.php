<?php

/**
 * Scheduled jobs over HTTP, for hosts without a command line or cron (Vercel; cron-job.org pings).
 * Disabled unless CRON_SECRET is set; every call must carry it, either as
 * "Authorization: Bearer <secret>" (what Vercel Cron sends) or ?token=<secret>.
 *
 *   index.php?url=cron/messages   send due WhatsApp/SMS messages (every few minutes)
 *   index.php?url=cron/daily      messages + expired sign-in sessions cleanup (once a day)
 */
class CronController extends Controller
{
    public function messages(): void
    {
        $this->authorize();
        $this->respond(['messages' => MessageWorker::run()]);
    }

    public function daily(): void
    {
        $this->authorize();

        $removedSessions = Database::connect()
            ->prepare('DELETE FROM sessions WHERE last_activity < ?');
        $removedSessions->execute([time() - 7 * 24 * 3600]);

        $this->respond([
            'messages' => MessageWorker::run(),
            'expired_sessions_removed' => $removedSessions->rowCount(),
        ]);
    }

    private function authorize(): void
    {
        $secret = (string) ($_ENV['CRON_SECRET'] ?? '');
        $header = (string) ($_SERVER['HTTP_AUTHORIZATION'] ?? '');
        $given = str_starts_with($header, 'Bearer ') ? substr($header, 7) : (string) ($_GET['token'] ?? '');

        if (strlen($secret) < 16 || !hash_equals($secret, $given)) {
            http_response_code(404);
            echo 'Page not found.';
            exit;
        }
    }

    private function respond(array $result): void
    {
        header('Content-Type: application/json');
        header('Cache-Control: no-store');
        echo json_encode(['ok' => true, 'at' => date('c')] + $result);
    }
}
