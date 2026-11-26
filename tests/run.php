<?php
// Offline checks: signing and webhook verification. Run: php tests/run.php

require is_file(__DIR__ . '/../vendor/autoload.php') ? __DIR__ . '/../vendor/autoload.php' : __DIR__ . '/autoload.php';

use Loamhill\Api\RequestBuilder;
use Loamhill\Api\Webhook;

$fail = 0;
function check($name, $cond)
{
    global $fail;
    echo ($cond ? 'ok   ' : 'FAIL ') . $name . PHP_EOL;
    if (!$cond) {
        $fail++;
    }
}

$key = 'test-key';

check('sign = md5(base64(body) + key)',
    RequestBuilder::sign('{"order_id":"order-1001"}', $key) === md5(base64_encode('{"order_id":"order-1001"}') . $key));
check('GET signs empty string', RequestBuilder::sign('', $key) === md5($key));

$event = [
    'type' => 'payment',
    'uuid' => '8f3c2a1e-6b4d-4e7a-9c21-5d0f7b3e9a14',
    'order_id' => 'order-1001',
    'amount' => '25.00',
    'status' => 'paid',
    'url' => 'https://shop.example.com/ok',
    'comment' => 'Оплата',
];
$event['sign'] = md5(base64_encode(json_encode($event, JSON_UNESCAPED_UNICODE)) . $key);
$raw = json_encode($event, JSON_UNESCAPED_UNICODE);

check('webhook valid (array)', Webhook::verify($event, $key) === true);
check('webhook valid (raw)', Webhook::verify($raw, $key) === true);
check('webhook wrong key', Webhook::verify($event, 'other') === false);
$tampered = $event;
$tampered['amount'] = '99.00';
check('webhook tampered', Webhook::verify($tampered, $key) === false);
$nosign = $event;
unset($nosign['sign']);
check('webhook without sign rejected', Webhook::verify($nosign, $key) === false);
check('parse() picks payment key', Webhook::parse($key, 'payout-key', $raw) !== null);

$payout = ['type' => 'payout', 'uuid' => 'x', 'status' => 'paid'];
$payout['sign'] = md5(base64_encode(json_encode($payout, JSON_UNESCAPED_UNICODE)) . 'payout-key');
check('parse() picks payout key', Webhook::parse($key, 'payout-key', json_encode($payout)) !== null);
check('parse() payout without payout key', Webhook::parse($key, null, json_encode($payout)) === null);

// --- HTTP behaviour against tests/fake-api.php (php -S 127.0.0.1:8765 tests/fake-api.php)
$base = getenv('LOAMHILL_FAKE_API');
if ($base) {
    $p = \Loamhill\Api\Client::payment($key, 'm-uuid', $base);

    $r = $p->history();
    check('history() sends no cursor', $r['query'] === [] && $r['body'] === '{}');
    $r = $p->history(1);
    check('history(1) (Cryptomus style) sends no cursor', $r['query'] === []);
    $r = $p->history('eyJpZCI6MTI0ODh9', ['date_from' => '2026-10-01 00:00:00']);
    check('history($cursor) sends cursor in query', $r['query'] === ['cursor' => 'eyJpZCI6MTI0ODh9']);
    check('cursor not in signed body', $r['sign'] === md5(base64_encode($r['body']) . $key) && strpos($r['body'], 'cursor') === false);
    check('merchant header', $r['merchant'] === 'm-uuid');

    $r = \Loamhill\Api\Client::payout($key, 'm-uuid', $base)->priority(['currency_code' => 'USDT', 'network_code' => 'tron']);
    check('GET signs empty string', $r['method'] === 'GET' && $r['body'] === '' && $r['sign'] === md5($key));

    $r = \Loamhill\Api\Client::user($key, 'u-id', $base)->walletAddress('USDT', 'tron');
    check('user API: userId header + signed GET body', $r['userId'] === 'u-id' && $r['sign'] === md5(base64_encode($r['body']) . $key));

    $rb = new RequestBuilder($key, 'm-uuid', RequestBuilder::AUTH_MERCHANT, $base);
    check('empty result returns true', $rb->sendRequest('v1/empty') === true);
    try {
        $rb->sendRequest('v1/fail');
        check('422 throws', false);
    } catch (\Loamhill\Api\RequestBuilderException $e) {
        check('422 throws with errors', $e->getCode() === 422 && isset($e->getErrors()['order_id']));
    }
} else {
    echo "skip HTTP tests (set LOAMHILL_FAKE_API, see composer test)" . PHP_EOL;
}

exit($fail ? 1 : 0);
