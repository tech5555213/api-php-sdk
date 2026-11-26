# Loamhill PHP SDK

PHP SDK for the [Loamhill](https://api.loamhill.com) crypto payment API: invoices, static wallets, payouts, refunds, transfers, balances, exchange rates, the User API and webhook verification.

## Installation

```bash
composer require loamhill/api-php-sdk
```

Requires PHP 7.0+ with `curl` and `json`.

## Migrating from cryptomus/api-php-sdk

The method names and arguments are the same: `create`, `info`, `services`, `history`, `balance`, `reSendNotifications`, `createWallet`, `payout->create`, `payout->info`. Here is what you need to change:

1. Replace the package:
   ```bash
   composer remove cryptomus/api-php-sdk
   composer require loamhill/api-php-sdk
   ```
2. Replace the namespace `Cryptomus\Api` with `Loamhill\Api` (in `Client::...` calls and `catch (RequestBuilderException $e)`).
3. Use the merchant ID, Payment API key and Payout API key from your Loamhill dashboard.
4. Webhook verification code keeps working with the Loamhill key. If you allowlist IPs, add `91.216.220.24`.

Compatibility notes:
- `history($page)` still accepts a page number: a numeric value means the first page. To get further pages, pass `paginate.nextCursor`.
- As in the Cryptomus SDK, a method returns `true` when the API answers with an empty `result`.
- Unlike the Cryptomus SDK, every non-200 response throws `RequestBuilderException`, even one without `message` or `errors`.

## Authorization

Every merchant has two keys, both on the merchant's API page in the Business dashboard:

| Key | Used for | Client |
|---|---|---|
| Payment API key | Invoices, static wallets, payment history, discounts, balances, settings; signs `payment` and `wallet` webhooks | `Client::payment()` |
| Payout API key | Payouts, refunds, transfers; signs `payout` webhooks | `Client::payout()` |
| User API key | Personal account User API (`userId` instead of merchant ID) | `Client::user()` |

Keep keys on your server only. Load them from environment variables, never commit them.

```php
<?php
require 'vendor/autoload.php';

$merchant = getenv('LOAMHILL_MERCHANT_ID');

$payment = \Loamhill\Api\Client::payment(getenv('LOAMHILL_PAYMENT_API_KEY'), $merchant);
$payout  = \Loamhill\Api\Client::payout(getenv('LOAMHILL_PAYOUT_API_KEY'), $merchant);
$user    = \Loamhill\Api\Client::user(getenv('LOAMHILL_USER_API_KEY'), getenv('LOAMHILL_USER_ID'));
$rates   = \Loamhill\Api\Client::rates();
```

Every client takes an optional last argument `$baseUrl` to point at another host, for example a test stand.

Each method returns the `result` part of the response as an array. Amounts are strings: send them as strings too.

## Errors

Any non-200 response or `state: 1` throws `\Loamhill\Api\RequestBuilderException`:

```php
try {
    $invoice = $payment->create($data);
} catch (\Loamhill\Api\RequestBuilderException $e) {
    $e->getCode();    // HTTP status: 401, 403, 404, 422, 500, 503
    $e->getMessage(); // e.g. "Invalid Sign." or "Minimum amount 10.00 USD"
    $e->getErrors();  // HTTP 422 validation errors: ['order_id' => ['validation.alpha_dash']]
    $e->getMethod();  // API path that failed
}
```

## Payments (Payment API key)

### Create an invoice

```php
$invoice = $payment->create([
    'amount'       => '25.00',
    'currency'     => 'USD',
    'order_id'     => 'order-1001',
    'url_callback' => 'https://shop.example.com/webhooks/loamhill',
]);
// $invoice['uuid'], $invoice['url'] ...
```

`order_id` makes the request idempotent: repeating it returns the existing invoice.

### All payment methods

| Method | Endpoint |
|---|---|
| `create(array $data)` | `POST /v1/payment` |
| `info(['uuid' => ...])` or `info(['order_id' => ...])` | `POST /v1/payment/info` |
| `history($cursor = null, array $filters = [])` | `POST /v1/payment/list` |
| `markAsPaid(array $data)` | `POST /v1/payment/mark-as-paid` |
| `qr(array $data)` | `POST /v1/payment/qr` |
| `reSendNotifications(array $data)` | `POST /v1/payment/resend` |
| `services()` | `POST /v1/payment/services` |
| `testWebhookPayment(array $data)` | `POST /v1/test-webhook/payment` |
| `testWebhookPayout(array $data)` | `POST /v1/test-webhook/payout` |
| `testWebhookWallet(array $data)` | `POST /v1/test-webhook/wallet` |
| `createWallet(array $data)` | `POST /v1/wallet` |
| `walletQr(array $data)` | `POST /v1/wallet/qr` |
| `blockAddress(array $data)` | `POST /v1/wallet/block-address` |
| `blockedAddressRefund(array $data)` | `POST /v1/wallet/blocked-address-refund` |
| `walletPaymentHistory($cursor = null, array $filters = [])` | `POST /v1/wallet/payment/list` |
| `businessWallets($cursor = null, array $filters = [])` | `POST /v1/wallet/list` |
| `balance()` | `POST /v1/balance` |
| `transactionHistory($cursor = null, array $filters = [])` | `POST /v1/transaction/list` |
| `currencies()` | `POST /v1/settings/payments/currencies/list` |
| `discountList()` | `POST /v1/payment/discount/list` |
| `setDiscount(array $data)` | `POST /v1/payment/discount/set` |

## Payouts, refunds, transfers (Payout API key)

```php
$result = $payout->create([
    'amount'       => '15',
    'currency'     => 'USDT',
    'network'      => 'tron',
    'order_id'     => 'payout-555321',
    'address'      => 'TXguLRFtrAFrEDA17WuPfrxB84jVzJcNNV',
    'is_subtract'  => true,
    'url_callback' => 'https://shop.example.com/webhooks/loamhill',
]);
```

| Method | Endpoint |
|---|---|
| `create(array $data)` | `POST /v1/payout` |
| `calculate(array $data)` | `POST /v1/payout/calc` |
| `info(['uuid' => ...])` | `POST /v1/payout/info` |
| `history($cursor = null, array $filters = [])` | `POST /v1/payout/list` |
| `services()` | `POST /v1/payout/services` |
| `priority(['currency_code' => 'USDT', 'network_code' => 'tron'])` | `GET /v1/payout/priority` |
| `refund(array $data)` | `POST /v1/payment/refund` |
| `transferToPersonal(array $data)` | `POST /v1/transfer/to-personal` |
| `transferToBusiness(array $data)` | `POST /v1/transfer/to-business` |

## User API (User API key)

| Method | Endpoint |
|---|---|
| `paymentServices()` | `GET /v2/user-api/payment/services` |
| `payoutServices()` | `GET /v2/user-api/payout/services` |
| `transactions($cursor = null, array $filters = [])` | `POST /v2/user-api/transaction/list` |
| `walletAddress('USDT', 'tron')` | `GET /v2/user-api/wallet-address/list` |

## Exchange rates (public)

| Method | Endpoint |
|---|---|
| `list('USDT')` | `GET /v1/exchange-rate/{currency}/list` |
| `all()` | `GET /v2/exchange-rate/list` |
| `dictionary('USDT')` | `GET /v2/exchange-rate/{currency}/list` |
| `xml()` (raw XML string) | `GET /v1/exchange-rate/rates.xml` |

## Pagination

List methods return `items` and `paginate`. Pass `paginate.nextCursor` as the first argument to get the next page:

```php
$cursor = null;
do {
    $page = $payment->history($cursor, ['date_from' => '2026-10-01 00:00:00', 'date_to' => '2026-10-31 23:59:59']);
    foreach ($page['items'] as $item) {
        // ...
    }
    $cursor = $page['paginate']['nextCursor'];
} while ($cursor !== null);
```

The cursor goes to the query string and is not part of the signature.

## Webhooks

```php
<?php
require 'vendor/autoload.php';

$data = \Loamhill\Api\Webhook::parse(
    getenv('LOAMHILL_PAYMENT_API_KEY'), // payment and wallet events
    getenv('LOAMHILL_PAYOUT_API_KEY')   // payout events
);

if ($data === null) {
    http_response_code(401);
    exit;
}

http_response_code(200); // answer within 40 seconds, process asynchronously
// $data['type'], $data['uuid'], $data['status'] ...
```

- `Webhook::verify($payloadOrArray, $apiKey)` checks one key explicitly.
- Events from `/v1/test-webhook/*` are all signed with the **Payment** API key, including the test payout event: verify those with `Webhook::verify($raw, $paymentKey)`.
- Webhooks come from `91.216.220.24` (`Webhook::SENDER_IP`). The IP check complements the signature check, it does not replace it.
- The same status can arrive more than once: process events idempotently by `uuid` and `status`.

## Tests

```bash
composer install  # optional: tests fall back to tests/autoload.php
composer test
```

## License

MIT
