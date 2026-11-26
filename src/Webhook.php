<?php

namespace Loamhill\Api;

/**
 * Webhook signature check.
 *
 * sign = md5(base64(json_encode(body without sign, JSON_UNESCAPED_UNICODE)) + api_key)
 * Key: Payment API key for "payment" and "wallet" events, Payout API key for "payout".
 * Webhooks are sent from 91.216.220.24 and must be answered with HTTP 200 within 40 seconds.
 */
final class Webhook
{
    const SENDER_IP = '91.216.220.24';

    /**
     * @param string|array $payload Raw request body or the decoded array
     * @param string $apiKey
     * @return bool
     */
    public static function verify($payload, $apiKey)
    {
        $data = is_array($payload) ? $payload : json_decode((string) $payload, true);
        if (!is_array($data) || empty($data['sign']) || !is_string($data['sign'])) {
            return false;
        }

        $sign = $data['sign'];
        unset($data['sign']);

        $hash = md5(base64_encode(json_encode($data, JSON_UNESCAPED_UNICODE)) . $apiKey);

        return hash_equals($hash, $sign);
    }

    /**
     * Reads php://input, verifies the signature and returns the decoded data, or null if invalid.
     *
     * @param string $paymentKey Used for "payment" and "wallet" events
     * @param string|null $payoutKey Used for "payout" events
     * @param string|null $rawBody Defaults to php://input
     * @return array|null
     */
    public static function parse($paymentKey, $payoutKey = null, $rawBody = null)
    {
        $raw = $rawBody === null ? file_get_contents('php://input') : $rawBody;
        $data = json_decode((string) $raw, true);
        if (!is_array($data)) {
            return null;
        }

        $key = (isset($data['type']) && $data['type'] === 'payout') ? $payoutKey : $paymentKey;
        if ($key === null || $key === '') {
            return null;
        }

        return self::verify($data, $key) ? $data : null;
    }
}
