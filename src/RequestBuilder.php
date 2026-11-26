<?php

namespace Loamhill\Api;

/**
 * Low-level HTTP client for https://api.loamhill.com.
 *
 * Signature: sign = md5(base64(body) + api_key).
 * - POST without parameters signs and sends "{}".
 * - GET signs an empty string (unless a body is sent, as in the User API wallet-address call).
 * - The pagination cursor is a query parameter and is not signed.
 */
final class RequestBuilder
{
    const API_URL = 'https://api.loamhill.com/';

    const AUTH_MERCHANT = 'merchant';
    const AUTH_USER = 'userId';
    const AUTH_NONE = '';

    /** @var string */
    private $secretKey;
    /** @var string */
    private $authId;
    /** @var string */
    private $authHeader;
    /** @var string */
    private $baseUrl;
    /** @var int */
    private $timeout;

    /**
     * @param string $secretKey  Payment, Payout or User API key ('' for public endpoints)
     * @param string $authId     Merchant UUID or User ID ('' for public endpoints)
     * @param string $authHeader self::AUTH_MERCHANT, self::AUTH_USER or self::AUTH_NONE
     * @param string|null $baseUrl Override the API host (for example a test stand)
     * @param int $timeout Request timeout in seconds
     */
    public function __construct($secretKey, $authId, $authHeader = self::AUTH_MERCHANT, $baseUrl = null, $timeout = 30)
    {
        $this->secretKey = (string) $secretKey;
        $this->authId = (string) $authId;
        $this->authHeader = $authHeader;
        $this->baseUrl = rtrim($baseUrl ? $baseUrl : self::API_URL, '/') . '/';
        $this->timeout = (int) $timeout;
    }

    /**
     * Signs a body the way Loamhill expects.
     *
     * @param string $body
     * @param string $key
     * @return string
     */
    public static function sign($body, $key)
    {
        return md5(base64_encode($body) . $key);
    }

    /**
     * POST request with a JSON body.
     *
     * @param string $uri
     * @param array $data
     * @param array $query Query parameters (e.g. ['cursor' => '...']), not signed
     * @return mixed
     * @throws RequestBuilderException
     */
    public function sendRequest($uri, array $data = [], array $query = [])
    {
        return $this->execute('POST', $uri, self::encode($data, true), $query);
    }

    /**
     * GET request. Query parameters are not signed; the signed body is an empty string.
     *
     * @param string $uri
     * @param array $query
     * @param array|null $body Optional JSON body (User API wallet-address sends one)
     * @return mixed
     * @throws RequestBuilderException
     */
    public function sendGet($uri, array $query = [], $body = null)
    {
        $encoded = $body === null ? '' : self::encode($body, true);

        return $this->execute('GET', $uri, $encoded, $query);
    }

    /**
     * GET request that returns the raw response body (for XML rates).
     *
     * @param string $uri
     * @return string
     * @throws RequestBuilderException
     */
    public function sendRaw($uri)
    {
        return $this->execute('GET', $uri, '', [], true);
    }

    /**
     * curl_close() has no effect since PHP 8.0 and is deprecated since 8.5.
     *
     * @param resource $curl
     */
    private static function close($curl)
    {
        if (PHP_VERSION_ID < 80000) {
            curl_close($curl);
        }
    }

    /**
     * @param array $data
     * @param bool $emptyAsObject
     * @return string
     */
    private static function encode(array $data, $emptyAsObject)
    {
        if ($emptyAsObject && empty($data)) {
            return '{}';
        }

        return json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param string $method
     * @param string $uri
     * @param string $body
     * @param array $query
     * @param bool $raw
     * @return mixed
     * @throws RequestBuilderException
     */
    private function execute($method, $uri, $body, array $query, $raw = false)
    {
        $url = $this->baseUrl . ltrim($uri, '/');
        // A numeric page value (e.g. history(1)) means the first page.
        // Loamhill cursors are opaque strings, so a number is never a valid cursor.
        if (isset($query['cursor']) && preg_match('/^\d+$/', (string) $query['cursor'])) {
            unset($query['cursor']);
        }
        $query = array_filter($query, function ($value) {
            return $value !== null && $value !== '';
        });
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }

        $headers = ['Accept: application/json'];
        if ($this->authHeader !== self::AUTH_NONE) {
            $headers[] = 'Content-Type: application/json;charset=UTF-8';
            $headers[] = $this->authHeader . ': ' . $this->authId;
            $headers[] = 'sign: ' . self::sign($body, $this->secretKey);
        }

        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CUSTOMREQUEST => $method,
        ];
        if ($method === 'POST' || $body !== '') {
            $headers[] = 'Content-Length: ' . strlen($body);
            $options[CURLOPT_POSTFIELDS] = $body;
        }
        $options[CURLOPT_HTTPHEADER] = $headers;

        $curl = curl_init();
        curl_setopt_array($curl, $options);
        $response = curl_exec($curl);
        $responseCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if ($response === false) {
            $error = curl_error($curl);
            self::close($curl);
            throw new RequestBuilderException($error, $responseCode, $uri);
        }
        self::close($curl);

        if ($raw && $responseCode === 200) {
            return $response;
        }

        if ($response === '') {
            if ($responseCode !== 200) {
                throw new RequestBuilderException('Empty response', $responseCode, $uri);
            }
            return true;
        }

        $json = json_decode($response, true);
        if (!is_array($json)) {
            throw new RequestBuilderException('Invalid JSON response: ' . json_last_error_msg(), $responseCode, $uri);
        }

        $state = isset($json['state']) ? (int) $json['state'] : null;

        if ($responseCode !== 200 || $state === 1) {
            $errors = isset($json['errors']) && is_array($json['errors']) ? $json['errors'] : [];
            if (!empty($json['message'])) {
                $message = $json['message'];
            } elseif (!empty($errors)) {
                $message = 'Validation error';
            } else {
                $message = 'HTTP ' . $responseCode;
            }
            throw new RequestBuilderException($message, $responseCode, $uri, $errors);
        }

        // An empty result means success and returns true.
        if (!empty($json['result'])) {
            return $json['result'];
        }

        if (!array_key_exists('result', $json) && !array_key_exists('state', $json)) {
            return $json; // public endpoints that answer without the envelope
        }

        return true;
    }
}
