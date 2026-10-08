<?php

/**
 * Sends customer updates by SMS through Africa's Talking or Beem Africa.
 *
 * Disabled until SMS_PROVIDER and that provider's credentials are set in .env:
 *   SMS_PROVIDER=africastalking  + SMS_USERNAME, SMS_API_KEY            (SMS_SANDBOX=true for testing)
 *   SMS_PROVIDER=beem            + SMS_API_KEY, SMS_SECRET_KEY
 *   SMS_SENDER_ID                  optional registered sender name, e.g. DUBAITECH
 * Only active customers with a phone number receive messages. Failures are logged and never
 * interrupt the request.
 */
class Sms
{
    public static ?string $lastError = null;

    private const MAX_LENGTH = 300;

    public static function isEnabled(): bool
    {
        return match (self::provider()) {
            'africastalking' => self::config('SMS_USERNAME') !== '' && self::config('SMS_API_KEY') !== '',
            'beem' => self::config('SMS_API_KEY') !== '' && self::config('SMS_SECRET_KEY') !== '',
            default => false,
        };
    }


    public static function format(string $title, string $message): string
    {
        $text = company_name() . ': ' . $title . '. ' . $message;

        return mb_strlen($text) > self::MAX_LENGTH ? rtrim(mb_substr($text, 0, self::MAX_LENGTH - 3)) . '...' : $text;
    }

    public static function send(string $phone, string $text): bool
    {
        self::$lastError = null;

        if (!self::isEnabled()) {
            return false;
        }

        $number = whatsapp_number($phone);
        if ($number === '') {
            error_log(self::$lastError = 'SMS: invalid phone number "' . $phone . '"');
            return false;
        }

        return self::provider() === 'beem' ? self::sendViaBeem($number, $text) : self::sendViaAfricasTalking($number, $text);
    }

    private static function sendViaAfricasTalking(string $number, string $text): bool
    {
        $sandbox = filter_var(self::config('SMS_SANDBOX'), FILTER_VALIDATE_BOOLEAN);
        $fields = array_filter([
            'username' => self::config('SMS_USERNAME'),
            'to' => '+' . $number,
            'message' => $text,
            'from' => self::config('SMS_SENDER_ID'),
        ], static fn (string $value): bool => $value !== '');

        return self::post(
            $sandbox ? 'https://api.sandbox.africastalking.com/version1/messaging' : 'https://api.africastalking.com/version1/messaging',
            ['apiKey: ' . self::config('SMS_API_KEY'), 'Accept: application/json', 'Content-Type: application/x-www-form-urlencoded'],
            http_build_query($fields)
        );
    }

    private static function sendViaBeem(string $number, string $text): bool
    {
        return self::post(
            'https://apisms.beem.africa/v1/send',
            [
                'Authorization: Basic ' . base64_encode(self::config('SMS_API_KEY') . ':' . self::config('SMS_SECRET_KEY')),
                'Content-Type: application/json',
            ],
            (string) json_encode([
                'source_addr' => self::config('SMS_SENDER_ID') ?: 'INFO',
                'schedule_time' => '',
                'encoding' => 0,
                'message' => $text,
                'recipients' => [['recipient_id' => 1, 'dest_addr' => $number]],
            ])
        );
    }

    private static function post(string $url, array $headers, string $body): bool
    {
        if (!function_exists('curl_init')) {
            error_log(self::$lastError = 'SMS: the PHP curl extension is not enabled');
            return false;
        }

        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => 6,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => $body,
        ]);

        $response = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        if ($response === false || $status < 200 || $status >= 300) {
            error_log(self::$lastError = sprintf('SMS: send failed (HTTP %d) %s %s', $status, $error, is_string($response) ? substr($response, 0, 500) : ''));
            return false;
        }

        return true;
    }

    private static function provider(): string
    {
        return strtolower(self::config('SMS_PROVIDER'));
    }

    private static function config(string $key): string
    {
        return trim((string) ($_ENV[$key] ?? ''));
    }
}
