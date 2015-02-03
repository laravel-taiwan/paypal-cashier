<?php namespace Beyond\PaypalCashier\Controllers;
/*
     .o8                                                         .o8
    "888                                                        "888
     888oooo.   .ooooo.  oooo    ooo  .ooooo.  ooo. .oo.    .oooo888
     d88' `88b d88' `88b  `88.  .8'  d88' `88b `888P"Y88b  d88' `888
     888   888 888ooo888   `88..8'   888   888  888   888  888   888
     888   888 888    .o    `888'    888   888  888   888  888   888
     `Y8bod8P' `Y8bod8P'     .8'     `Y8bod8P' o888o o888o `Y8bod88P" Inc.
                         .o..P'
                         `Y8P'
 */

use Illuminate\Routing\Controller as BaseController;
use Beyond\PaypalCashier\Subscription;
use PayPal\Api\Payer;
use PayPal\Api\PayerInfo;
use PayPal\Api\FundingInstrument;
use PayPal\Api\CreditCard;
use PayPal\Api\ShippingAddress;
use PayPal\Api\Links;
use PayPal\Rest\ApiContext;
use PayPal\Auth\OAuthTokenCredential;
use Carbon\Carbon;

/**
 * This controller is used for testing purpose only.
 *
 * 1. find existing plan.
 * 2. subscribe on the plan.
 * 3. try
 *
 */
class SubscriptionController extends BaseController{

    /**
     * Instance of Plan Repository
     *
     * @var Beyond\PaypalCashier\PlanRepository
     */
    protected $repo;

    /**
     * Sample plan id.
     *
     * @var
     */
    protected $samplePlanId = 'P-53R82169JN2163459DQ4RFBY';

    /**
     * Create agreement.
     *
     * @return void
     */
    public function createAgreement()
    {
        $apiContext = $this->apiContextProvider();

        // create a new agreement
        $subscription = new Subscription([
            'name'          =>  'sample agreement',
            'description'   =>  'sample description',
            'start_date'    =>  Carbon::now()->addDay()->format('Y-m-d\TH:i:s\Z')
        ]);

        // apply agreement settings
        $this->applySubscriptionSettings($subscription);

        // create new subscription
        $subscription = $subscription->createSubscription($apiContext);

//        var_dump($subscription->getSdkSubscription());
//        die;
    }

    /**
     * Get specified agreement and check its status.
     *
     *
     */
    public function getAgreement()
    {

    }

    /**
     * Apply subscription settings.
     *
     * @param Beyond\PaypalCashier\Subscription
     */
    protected function applySubscriptionSettings($subscription)
    {
        // find existing plan by plan id
        $plan  = $this->planProvider();
        $payer = $this->payerProvider();
        $shippingAddress = $this->shippingAddressProvider();
        $links = $this->linksProvider();

        $subscription->setPlan($plan);
        $subscription->setPayer($payer);
        $subscription->setShippingAddress($shippingAddress);
//        $subscription->setLinks();

    }

    /**
     * ApiContext instance provider.
     *
     * @return PayPal\Api\
     */
    protected function apiContextProvider()
    {
        $apiContext = new ApiContext(
            new OAuthTokenCredential(
                'AW3K_xAvssx_6NKlwND8AkOdilveb2t1n9qtcrc5DHxfvnnrME3u9uPqI9gv',
                'EJRSABDIeN15U2zw32Ai0YlB2IruSjRetDDG2eiK7MwqoWR6mDlgZ5xXaOq2'
            )
        );

        $apiContext->setConfig(
            array(
                'mode' => 'sandbox',
                'log.LogEnabled' => true,
                'log.FileName' => '../PayPal.log',
                'log.LogLevel' => 'FINE',
                'validation.level' => 'log',
                'cache.enabled' => true,
                // 'http.CURLOPT_CONNECTTIMEOUT' => 30
                // 'http.headers.PayPal-Partner-Attribution-Id' => '123123123'
            )
        );

        return $apiContext;
    }

    /**
     * Provides instance of Beyond\PaypalCashier\Subscription.
     *
     * @todo for testing purpose only
     * @return Beyond\PaypalCashier\Subscription
     */
    protected function planProvider()
    {
        $planRepo = \App::make('Beyond\PaypalCashier\PlanRepository');

        $plan = $planRepo->getPlanById($this->samplePlanId);

//        var_dump($plan->getLinks());
//        die;
        return $plan;
    }

    /**
     * Provides instance of PayPal\Api\Payer
     *
     * @todo for testing purpose only
     * @return PayPal\Api\Payer
     */
    protected function payerProvider()
    {
        $payer = new Payer();

        $payer->setPaymentMethod('credit_card');

        $payer->setPayerInfo(new PayerInfo(array('email' => 'huangchiheng@gmail.com')));

        $creditCard = $this->creditCardProvider();

        $fundingInstrument = new FundingInstrument();

        $fundingInstrument->setCreditCard($creditCard);

        $payer->setFundingInstruments(array($fundingInstrument));

        return $payer;
    }

    /**
     * Provides instance of PayPal\Api\ShippingAddress
     *
     * @todo for testing purpose only
     * @return PayPal\Api\ShippingAddress
     */
    protected function shippingAddressProvider()
    {
        // Add Shipping Address
        $shippingAddress = new ShippingAddress();
        $shippingAddress->setLine1('111 First Street')
            ->setCity('Saratoga')
            ->setState('CA')
            ->setPostalCode('95070')
            ->setCountryCode('US');

        return $shippingAddress;
    }

    /**
     * Provides instance of PayPal\Api\Links
     *
     * "href": "https://api.sandbox.paypal.com/v1/payments/billing-agreements/EC-0JP008296V451950C/agreement-execute",
     * "rel": "execute",
     * "method": "POST"
     *
     * @todo for testing purpose only
     * @return PayPal\Api\Links
     */
    protected function linksProvider()
    {
        // redirect link to paypal approval page.
        $link = new Links();
        $link->setHref("https://api.sandbox.paypal.com/v1/payments/billing-agreements/EC-0JP008296V451950C/agreement-execute");
        $link->setRel('execute');
        $link->setMethod('POST');

        return $link;

    }

    /**
     * Provides instance of PayPal\Api\CreditCard.
     * Issues: 使用 sdk sample credit card 會發生 internal server error
     *  - http://stackoverflow.com/questions/15954312/paypal-sandbox-credit-card-details-not-working
     *
     * @todo for testing purpose only
     * @return PayPal\Api\CreditCard
     */
    protected function creditCardProvider()
    {
        $creditCard = new CreditCard();
//        $creditCard->setType('visa')
//            ->setNumber('4417119669820331')
//            ->setExpireMonth('12')
//            ->setExpireYear('2017')
//            ->setCvv2('128');
        $creditCard->setType('visa')
            ->setNumber('4032032107556109')
            ->setExpireMonth('01')
            ->setExpireYear('2020')
            ->setCvv2('123');

        return $creditCard;
    }
}