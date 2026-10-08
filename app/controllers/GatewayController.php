<?php

/**
 * Server-to-server callbacks from payment gateways. No login or CSRF token is possible here,
 * so each callback URL carries a long secret token that must match .env.
 */
class GatewayController extends Controller
{
    public function azampay(string $token = ''): void
    {
        if (!AzamPay::callbackTokenMatches($token)) {
            http_response_code(404);
            echo 'Not found.';
            return;
        }

        if ($this->requestMethod() !== 'POST') {
            $this->json(['success' => false, 'message' => 'POST required'], 405);
        }

        $payload = json_decode((string) file_get_contents('php://input'), true);
        if (!is_array($payload)) {
            $payload = $_POST;
        }

        try {
            $result = AzamPay::handleCallback($payload);
        } catch (Throwable $exception) {
            ErrorReporter::report($exception);
            $this->json(['success' => false, 'message' => 'Could not record the payment'], 500);
        }

        $this->json(['success' => true, 'result' => $result]);
    }
}
