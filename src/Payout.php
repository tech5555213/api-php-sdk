<?php

namespace Loamhill\Api;

/**
 * Endpoints signed with the Payout API key (payouts, refunds, transfers).
 */
final class Payout
{
    /** @var RequestBuilder */
    private $requestBuilder;

    /** @var string */
    private $version = 'v1';

    /**
     * @param string $payoutKey
     * @param string $merchantUuid
     * @param string|null $baseUrl
     */
    public function __construct($payoutKey, $merchantUuid, $baseUrl = null)
    {
        $this->requestBuilder = new RequestBuilder($payoutKey, $merchantUuid, RequestBuilder::AUTH_MERCHANT, $baseUrl);
    }

    /** POST /v1/payout */
    public function create(array $data)
    {
        return $this->requestBuilder->sendRequest($this->version . '/payout', $data);
    }

    /** POST /v1/payout/calc */
    public function calculate(array $data)
    {
        return $this->requestBuilder->sendRequest($this->version . '/payout/calc', $data);
    }

    /** POST /v1/payout/info — pass ['uuid' => ...] or ['order_id' => ...] */
    public function info(array $data = [])
    {
        return $this->requestBuilder->sendRequest($this->version . '/payout/info', $data);
    }

    /** POST /v1/payout/list */
    public function history($cursor = null, array $parameters = [])
    {
        return $this->requestBuilder->sendRequest($this->version . '/payout/list', $parameters, ['cursor' => $cursor]);
    }

    /** POST /v1/payout/services */
    public function services(array $parameters = [])
    {
        return $this->requestBuilder->sendRequest($this->version . '/payout/services', $parameters);
    }

    /**
     * GET /v1/payout/priority — network fee tiers.
     *
     * @param array $query currency_code, network_code
     */
    public function priority(array $query)
    {
        return $this->requestBuilder->sendGet($this->version . '/payout/priority', $query);
    }

    /** POST /v1/payment/refund — refund a payment (signed with the Payout key) */
    public function refund(array $data)
    {
        return $this->requestBuilder->sendRequest($this->version . '/payment/refund', $data);
    }

    /** POST /v1/transfer/to-personal */
    public function transferToPersonal(array $data)
    {
        return $this->requestBuilder->sendRequest($this->version . '/transfer/to-personal', $data);
    }

    /** POST /v1/transfer/to-business */
    public function transferToBusiness(array $data)
    {
        return $this->requestBuilder->sendRequest($this->version . '/transfer/to-business', $data);
    }
}
