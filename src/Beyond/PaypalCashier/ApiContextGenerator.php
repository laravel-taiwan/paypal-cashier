<?php namespace Beyond\PaypalCashier;

use PayPal\Rest\ApiContext;
use PayPal\Auth\OAuthTokenCredential;

class ApiContextGenerator
{
    /**
     * Make instance of Paypal\Rest\ApiContext.
     *
     * @param string $clientId
     * @param string $clientSecret
     * @return string $apiContext
     */
    public static function make($clientId, $clientSecret, array $config = array())
    {
        $apiContext = new ApiContext(new OAuthTokenCredential(

            $clientId,
            $clientSecret
        ));

        // default config
        $apiContext->setConfig($config);

        return $apiContext;
    }




}