<?php

namespace Loamhill\Api;

/**
 * User API: personal account, headers userId + sign (User API key).
 */
final class User
{
    /** @var RequestBuilder */
    private $requestBuilder;

    /** @var string */
    private $version = 'v2';

    /**
     * @param string $userApiKey
     * @param string $userId
     * @param string|null $baseUrl
     */
    public function __construct($userApiKey, $userId, $baseUrl = null)
    {
        $this->requestBuilder = new RequestBuilder($userApiKey, $userId, RequestBuilder::AUTH_USER, $baseUrl);
    }

    /** GET /v2/user-api/payment/services */
    public function paymentServices()
    {
        return $this->requestBuilder->sendGet($this->version . '/user-api/payment/services');
    }

    /** GET /v2/user-api/payout/services */
    public function payoutServices()
    {
        return $this->requestBuilder->sendGet($this->version . '/user-api/payout/services');
    }

    /** POST /v2/user-api/transaction/list */
    public function transactions($cursor = null, array $parameters = [])
    {
        return $this->requestBuilder->sendRequest($this->version . '/user-api/transaction/list', $parameters, ['cursor' => $cursor]);
    }

    /**
     * GET /v2/user-api/wallet-address/list — the docs send currency and network as a signed JSON body.
     *
     * @param string $currency
     * @param string $network
     */
    public function walletAddress($currency, $network)
    {
        return $this->requestBuilder->sendGet(
            $this->version . '/user-api/wallet-address/list',
            [],
            ['currency' => $currency, 'network' => $network]
        );
    }
}
