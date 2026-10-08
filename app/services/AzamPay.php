<?php

/**
 * Mobile-money payments (M-Pesa, Tigo Pesa, Airtel Money, HaloPesa) through AzamPay.
 *
 * The customer enters their phone number; AzamPay sends a PIN prompt to that phone; when the
 * customer approves, AzamPay calls our callback URL and the payment is confirmed automatically.
 *
 * Disabled until these are set in .env (from the AzamPay merchant portal):
 *   AZAMPAY_APP_NAME, AZAMPAY_CLIENT_ID, AZAMPAY_CLIENT_SECRET, AZAMPAY_API_KEY
 *   AZAMPAY_CALLBACK_TOKEN  a long random secret; the callback URL registered with AzamPay is
 *                           https://<your-domain>/public/index.php?url=gateway/azampay/<token>
 *   AZAMPAY_SANDBOX         true (default) while testing, false for real money
 */
class AzamPay
{
    public const PROVIDERS = ['Mpesa' => 'M-Pesa (Vodacom)', 'Tigo' => 'Tigo Pesa / Mixx by Yas', 'Airtel' => 'Airtel Money', 'Halopesa' => 'HaloPesa'];

    public static ?string $lastError = null;

    public static function isEnabled(): bool
    {
        foreach (['AZAMPAY_APP_NAME', 'AZAMPAY_CLIENT_ID', 'AZAMPAY_CLIENT_SECRET', 'AZAMPAY_API_KEY', 'AZAMPAY_CALLBACK_TOKEN'] as $key) {
            if (self::config($key) === '') {
                return false;
            }
        }

        return true;
    }

    /**
     * Asks AzamPay to send a payment prompt to the customer's phone.
     * Returns the external id on success, or null (see $lastError).
     */
    public static function requestPayment(array $payment, string $phone, string $provider): ?string
    {
        self::$lastError = null;

        if (!self::isEnabled()) {
            self::$lastError = 'Mobile-money payments are not connected.';
            return null;
        }
        if (!isset(self::PROVIDERS[$provider])) {
            self::$lastError = 'Choose your mobile-money network.';
            return null;
        }

        $msisdn = whatsapp_number($phone);
        if ($msisdn === '') {
            self::$lastError = 'Enter a valid phone number, e.g. 0754 123 456.';
            return null;
        }

        $amount = (float) $payment['amount'];
        $externalId = 'DCF' . (int) $payment['id'] . 'T' . time();

        $db = Database::connect();
        $db->prepare(
            'INSERT INTO payment_gateway_requests (payment_id, external_id, provider, msisdn, amount) VALUES (?, ?, ?, ?, ?)'
        )->execute([(int) $payment['id'], $externalId, $provider, $msisdn, $amount]);

        $token = self::accessToken();
        $response = $token === null ? null : self::postJson(
            self::checkoutBase() . '/azampay/mno/checkout',
            ['Authorization: Bearer ' . $token, 'X-API-Key: ' . self::config('AZAMPAY_API_KEY')],
            [
                'accountNumber' => $msisdn,
                'amount' => (string) round($amount),
                'currency' => 'TZS',
                'externalId' => $externalId,
                'provider' => $provider,
                'additionalProperties' => (object) [],
            ]
        );

        if (!is_array($response) || empty($response['success'])) {
            self::$lastError ??= (string) ($response['message'] ?? 'The payment request was not accepted.');
            $db->prepare('UPDATE payment_gateway_requests SET status = "failed", message = ? WHERE external_id = ?')
                ->execute([mb_substr(self::$lastError, 0, 255), $externalId]);
            return null;
        }

        $db->prepare('UPDATE payment_gateway_requests SET gateway_reference = ?, message = ? WHERE external_id = ?')
            ->execute([(string) ($response['transactionId'] ?? ''), mb_substr((string) ($response['message'] ?? ''), 0, 255), $externalId]);

        return $externalId;
    }

    /**
     * Applies AzamPay's callback. Returns a short result code for logging and tests:
     * confirmed | failed | duplicate | unknown | amount_mismatch.
     */
    public static function handleCallback(array $data): string
    {
        $externalId = (string) ($data['utilityref'] ?? $data['externalreference'] ?? '');
        $db = Database::connect();
        $stmt = $db->prepare('SELECT * FROM payment_gateway_requests WHERE external_id = ?');
        $stmt->execute([$externalId]);
        $request = $stmt->fetch();

        if (!$request) {
            return 'unknown';
        }
        if ($request['status'] !== 'requested') {
            return 'duplicate';
        }

        $succeeded = strtolower((string) ($data['transactionstatus'] ?? '')) === 'success';
        $paidAmount = (float) ($data['amount'] ?? 0);
        $status = $succeeded && $paidAmount + 0.5 >= (float) $request['amount'] ? 'success' : 'failed';

        $db->prepare('UPDATE payment_gateway_requests SET status = ?, gateway_reference = COALESCE(?, gateway_reference), message = ?, callback_payload = ? WHERE id = ?')
            ->execute([
                $status,
                ($data['reference'] ?? null) ?: null,
                mb_substr((string) ($data['message'] ?? ''), 0, 255),
                json_encode($data),
                (int) $request['id'],
            ]);

        if ($status !== 'success') {
            return $succeeded ? 'amount_mismatch' : 'failed';
        }

        $paymentModel = new Payment();
        $db->prepare('UPDATE payments SET method = "mobile_money", payment_reference = ?, submitted_at = COALESCE(submitted_at, CURRENT_TIMESTAMP) WHERE id = ?')
            ->execute([(string) (($data['reference'] ?? '') ?: $externalId), (int) $request['payment_id']]);
        $paymentModel->updateStatus((int) $request['payment_id'], 'confirmed', null, 'Paid by ' . $request['provider'] . ' from +' . $request['msisdn'] . ' (AzamPay)');

        $payment = $paymentModel->find((int) $request['payment_id']);
        $label = $payment['order_number'] ?? ($payment['invoice_number'] ?? 'your order');
        if (!empty($payment['user_id'])) {
            (new Notification())->create((int) $payment['user_id'], 'Payment confirmed', 'We received your mobile-money payment for ' . $label . '. Thank you.', 'success');
        }
        (new Notification())->notifyRoles(['manager', 'admin'], 'Mobile-money payment received', 'Payment for ' . $label . ' was confirmed automatically by AzamPay.', 'success');
        (new AuditLog())->create(null, 'payment_confirmed_azampay', 'payments', (int) $request['payment_id'], $externalId);

        return 'confirmed';
    }

    public static function latestRequest(int $paymentId): ?array
    {
        $stmt = Database::connect()->prepare('SELECT * FROM payment_gateway_requests WHERE payment_id = ? ORDER BY id DESC LIMIT 1');
        $stmt->execute([$paymentId]);

        return $stmt->fetch() ?: null;
    }

    public static function callbackTokenMatches(string $token): bool
    {
        $expected = self::config('AZAMPAY_CALLBACK_TOKEN');

        return $expected !== '' && hash_equals($expected, $token);
    }

    private static function accessToken(): ?string
    {
        $response = self::postJson(self::authBase() . '/AppRegistration/GenerateToken', [], [
            'appName' => self::config('AZAMPAY_APP_NAME'),
            'clientId' => self::config('AZAMPAY_CLIENT_ID'),
            'clientSecret' => self::config('AZAMPAY_CLIENT_SECRET'),
        ]);
        $token = is_array($response) ? (string) ($response['data']['accessToken'] ?? '') : '';

        if ($token === '') {
            self::$lastError ??= 'Could not sign in to AzamPay. Check the AzamPay keys.';
            return null;
        }

        return $token;
    }

    private static function postJson(string $url, array $headers, array $body): ?array
    {
        if (!function_exists('curl_init')) {
            self::$lastError = 'The PHP curl extension is not enabled.';
            return null;
        }

        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_HTTPHEADER => array_merge(['Content-Type: application/json', 'Accept: application/json'], $headers),
            CURLOPT_POSTFIELDS => json_encode($body),
        ]);
        $raw = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        $decoded = is_string($raw) ? json_decode($raw, true) : null;
        if ($raw === false || $status < 200 || $status >= 300) {
            self::$lastError = 'AzamPay request failed' . ($status ? ' (HTTP ' . $status . ')' : '') . ($error ? ': ' . $error : '');
            error_log(self::$lastError . ' ' . (is_string($raw) ? substr($raw, 0, 300) : ''));
            return is_array($decoded) ? $decoded : null;
        }

        return is_array($decoded) ? $decoded : null;
    }

    private static function authBase(): string
    {
        return self::sandbox() ? 'https://authenticator-sandbox.azampay.co.tz' : 'https://authenticator.azampay.co.tz';
    }

    private static function checkoutBase(): string
    {
        return self::sandbox() ? 'https://sandbox.azampay.co.tz' : 'https://checkout.azampay.co.tz';
    }

    private static function sandbox(): bool
    {
        return filter_var(self::config('AZAMPAY_SANDBOX') ?: 'true', FILTER_VALIDATE_BOOLEAN);
    }

    private static function config(string $key): string
    {
        return trim((string) ($_ENV[$key] ?? ''));
    }
}
