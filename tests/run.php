<?php
// Offline checks: signing and webhook verification. Run: php tests/run.php

require __DIR__ . '/../vendor/autoload.php';

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

exit($fail ? 1 : 0);
