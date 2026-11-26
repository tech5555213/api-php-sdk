<?php

namespace Loamhill\Api;

/**
 * Public exchange rates, no authentication.
 */
final class Rates
{
    /** @var RequestBuilder */
    private $requestBuilder;

    /** @param string|null $baseUrl */
    public function __construct($baseUrl = null)
    {
        $this->requestBuilder = new RequestBuilder('', '', RequestBuilder::AUTH_NONE, $baseUrl);
    }

    /** GET /v1/exchange-rate/{currency}/list */
    public function list($currency)
    {
        return $this->requestBuilder->sendGet('v1/exchange-rate/' . rawurlencode($currency) . '/list');
    }

    /** GET /v2/exchange-rate/list */
    public function all()
    {
        return $this->requestBuilder->sendGet('v2/exchange-rate/list');
    }

    /** GET /v2/exchange-rate/{currency}/list — rates as a dictionary */
    public function dictionary($currency)
    {
        return $this->requestBuilder->sendGet('v2/exchange-rate/' . rawurlencode($currency) . '/list');
    }

    /**
     * GET /v1/exchange-rate/rates.xml
     *
     * @return string Raw XML
     */
    public function xml()
    {
        return $this->requestBuilder->sendRaw('v1/exchange-rate/rates.xml');
    }
}
