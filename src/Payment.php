<?php

namespace Loamhill\Api;

/**
 * Endpoints signed with the Payment API key.
 */
final class Payment
{
    /** @var RequestBuilder */
    private $requestBuilder;

    /** @var string */
    private $version = 'v1';

    /**
     * @param string $paymentKey
     * @param string $merchantUuid
     * @param string|null $baseUrl
     */
    public function __construct($paymentKey, $merchantUuid, $baseUrl = null)
    {
        $this->requestBuilder = new RequestBuilder($paymentKey, $merchantUuid, RequestBuilder::AUTH_MERCHANT, $baseUrl);
    }

    /** POST /v1/payment/services */
    public function services(array $parameters = [])
    {
        return $this->requestBuilder->sendRequest($this->version . '/payment/services', $parameters);
    }

    /** POST /v1/payment — create an invoice */
    public function create(array $data)
    {
        return $this->requestBuilder->sendRequest($this->version . '/payment', $data);
    }

    /** POST /v1/payment/info — pass ['uuid' => ...] or ['order_id' => ...] */
    public function info(array $data = [])
    {
        return $this->requestBuilder->sendRequest($this->version . '/payment/info', $data);
    }

    /**
     * POST /v1/payment/list
     *
     * @param string|null $cursor paginate.nextCursor from the previous page
     * @param array $parameters e.g. date_from, date_to
     */
    public function history($cursor = null, array $parameters = [])
    {
        return $this->requestBuilder->sendRequest($this->version . '/payment/list', $parameters, ['cursor' => $cursor]);
    }

    /** POST /v1/payment/mark-as-paid — accept an underpaid payment */
    public function markAsPaid(array $data)
    {
        return $this->requestBuilder->sendRequest($this->version . '/payment/mark-as-paid', $data);
    }

    /** POST /v1/payment/qr */
    public function qr(array $data)
    {
        return $this->requestBuilder->sendRequest($this->version . '/payment/qr', $data);
    }

    /** POST /v1/payment/resend — resend a payment webhook */
    public function reSendNotifications(array $data)
    {
        return $this->requestBuilder->sendRequest($this->version . '/payment/resend', $data);
    }

    /** POST /v1/test-webhook/payment */
    public function testWebhookPayment(array $data)
    {
        return $this->requestBuilder->sendRequest($this->version . '/test-webhook/payment', $data);
    }

    /** POST /v1/test-webhook/payout */
    public function testWebhookPayout(array $data)
    {
        return $this->requestBuilder->sendRequest($this->version . '/test-webhook/payout', $data);
    }

    /** POST /v1/test-webhook/wallet */
    public function testWebhookWallet(array $data)
    {
        return $this->requestBuilder->sendRequest($this->version . '/test-webhook/wallet', $data);
    }

    /** POST /v1/wallet — create a static wallet */
    public function createWallet(array $data)
    {
        return $this->requestBuilder->sendRequest($this->version . '/wallet', $data);
    }

    /** POST /v1/wallet/qr */
    public function walletQr(array $data)
    {
        return $this->requestBuilder->sendRequest($this->version . '/wallet/qr', $data);
    }

    /** POST /v1/wallet/block-address */
    public function blockAddress(array $data)
    {
        return $this->requestBuilder->sendRequest($this->version . '/wallet/block-address', $data);
    }

    /** POST /v1/wallet/blocked-address-refund */
    public function blockedAddressRefund(array $data)
    {
        return $this->requestBuilder->sendRequest($this->version . '/wallet/blocked-address-refund', $data);
    }

    /** POST /v1/wallet/payment/list — payments to static wallets */
    public function walletPaymentHistory($cursor = null, array $parameters = [])
    {
        return $this->requestBuilder->sendRequest($this->version . '/wallet/payment/list', $parameters, ['cursor' => $cursor]);
    }

    /** POST /v1/wallet/list — business wallets */
    public function businessWallets($cursor = null, array $parameters = [])
    {
        return $this->requestBuilder->sendRequest($this->version . '/wallet/list', $parameters, ['cursor' => $cursor]);
    }

    /** POST /v1/balance */
    public function balance(array $parameters = [])
    {
        return $this->requestBuilder->sendRequest($this->version . '/balance', $parameters);
    }

    /** POST /v1/transaction/list */
    public function transactionHistory($cursor = null, array $parameters = [])
    {
        return $this->requestBuilder->sendRequest($this->version . '/transaction/list', $parameters, ['cursor' => $cursor]);
    }

    /** POST /v1/settings/payments/currencies/list — enabled payment currencies */
    public function currencies(array $parameters = [])
    {
        return $this->requestBuilder->sendRequest($this->version . '/settings/payments/currencies/list', $parameters);
    }

    /** POST /v1/payment/discount/list */
    public function discountList(array $parameters = [])
    {
        return $this->requestBuilder->sendRequest($this->version . '/payment/discount/list', $parameters);
    }

    /** POST /v1/payment/discount/set */
    public function setDiscount(array $data)
    {
        return $this->requestBuilder->sendRequest($this->version . '/payment/discount/set', $data);
    }
}
