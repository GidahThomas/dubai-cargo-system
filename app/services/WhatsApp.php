<?php

/**
 * Sends customer updates through the WhatsApp Business Cloud API (Meta).
 *
 * Disabled until WHATSAPP_TOKEN and WHATSAPP_PHONE_NUMBER_ID are set in .env.
 * Business-initiated messages need an approved template (WHATSAPP_TEMPLATE) with
 * two body variables: {{1}} = title, {{2}} = message. Without a template, plain text
 * is sent, which WhatsApp only delivers within 24 hours of the customer's last message.
 * Failures are logged and never interrupt the request.
 */
class WhatsApp
{
    public static ?string $lastError = null;

    private const API_VERSION = 'v21.0';

    public static function isEnabled(): bool
    {
        return self::config('WHATSAPP_TOKEN') !== '' && self::config('WHATSAPP_PHONE_NUMBER_ID') !== '';
    }

    public static function send(string $phone, string $title, string $message): bool
    {
        self::$lastError = null;

        if (!self::isEnabled()) {
            return false;
        }

        $to = whatsapp_number($phone);
        if ($to === '') {
            error_log(self::$lastError = 'WhatsApp: invalid phone number "' . $phone . '"');
            return false;
        }

        $template = self::config('WHATSAPP_TEMPLATE');
        $payload = ['messaging_product' => 'whatsapp', 'to' => $to];

        if ($template !== '') {
            $payload['type'] = 'template';
            $payload['template'] = [
                'name' => $template,
                'language' => ['code' => self::config('WHATSAPP_TEMPLATE_LANGUAGE') ?: 'en'],
                'components' => [[
                    'type' => 'body',
                    'parameters' => [
                        ['type' => 'text', 'text' => $title],
                        ['type' => 'text', 'text' => $message],
                    ],
                ]],
            ];
        } else {
            $payload['type'] = 'text';
            $payload['text'] = ['body' => company_name() . "\n*" . $title . "*\n" . $message];
        }

        return self::post($payload);
    }

    private static function post(array $payload): bool
    {
        if (!function_exists('curl_init')) {
            error_log(self::$lastError = 'WhatsApp: the PHP curl extension is not enabled');
            return false;
        }

        $endpoint = sprintf('https://graph.facebook.com/%s/%s/messages', self::API_VERSION, rawurlencode(self::config('WHATSAPP_PHONE_NUMBER_ID')));
        $curl = curl_init($endpoint);
        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => 6,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . self::config('WHATSAPP_TOKEN'),
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
        ]);

        $response = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        if ($response === false || $status < 200 || $status >= 300) {
            error_log(self::$lastError = sprintf('WhatsApp: send failed (HTTP %d) %s %s', $status, $error, is_string($response) ? substr($response, 0, 500) : ''));
            return false;
        }

        return true;
    }

    private static function config(string $key): string
    {
        return trim((string) ($_ENV[$key] ?? ''));
    }
}
