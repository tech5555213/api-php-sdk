<?php

namespace Loamhill\Api;

final class Client
{
    /**
     * Endpoints signed with the Payment API key.
     *
     * @param string $paymentKey
     * @param string $merchantUuid
     * @param string|null $baseUrl
     * @return Payment
     */
    public static function payment($paymentKey, $merchantUuid, $baseUrl = null)
    {
        return new Payment($paymentKey, $merchantUuid, $baseUrl);
    }

    /**
     * Endpoints signed with the Payout API key.
     *
     * @param string $payoutKey
     * @param string $merchantUuid
     * @param string|null $baseUrl
     * @return Payout
     */
    public static function payout($payoutKey, $merchantUuid, $baseUrl = null)
    {
        return new Payout($payoutKey, $merchantUuid, $baseUrl);
    }

    /**
     * User API (personal account), signed with the User API key.
     *
     * @param string $userApiKey
     * @param string $userId
     * @param string|null $baseUrl
     * @return User
     */
    public static function user($userApiKey, $userId, $baseUrl = null)
    {
        return new User($userApiKey, $userId, $baseUrl);
    }

    /**
     * Public exchange rates, no authentication.
     *
     * @param string|null $baseUrl
     * @return Rates
     */
    public static function rates($baseUrl = null)
    {
        return new Rates($baseUrl);
    }
}
