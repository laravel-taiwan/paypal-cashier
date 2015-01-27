<?php

use PayPal\Rest\ApiContext;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Api\Agreement;
use PayPal\Api\Plan;
use PayPal\Api\Payer;
use PayPal\Api\ShippingAddress;
use PayPal\Api\PayerInfo;
use PayPal\Api\CreditCard;
use PayPal\Api\FundingInstrument;

class PaypalRestfulSubscriptionTest extends TestCase
{
    /**
     * Sample plan id. All agreements will be subscribed to this plan.
     */
    protected $samplePlanId = 'P-53R82169JN2163459DQ4RFBY';

    protected $credentials = array(
        'client_id'     => 'AW3K_xAvssx_6NKlwND8AkOdilveb2t1n9qtcrc5DHxfvnnrME3u9uPqI9gv',
        'client_secret' =>  'EJRSABDIeN15U2zw32Ai0YlB2IruSjRetDDG2eiK7MwqoWR6mDlgZ5xXaOq2'
    );

    public function setUp()
    {
        parent::setUp();
    }

    public function tearDown()
    {
        parent::tearDown();
    }

    protected function apiContextProvider()
    {
        $apiContext = new ApiContext(
            new OAuthTokenCredential(
                $this->credentials['client_id'],
                $this->credentials['client_secret']
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

    public function test_create_agreement_to_plan_with_credit_card()
    {
        $apiContext = $this->apiContextProvider();


        // create a new plan and set it's plan id
        $plan = new Plan();
        $plan->setId($this->samplePlanId);

        // create new agreement
        $agreement = new Agreement();

        $agreement->setName('DPRP')
            ->setDescription('Payment with credit Card')
            ->setStartDate('2015-06-17T9:45:04Z');

        $agreement->setPlan($plan);

        // Add Payer
        $payer = new Payer();
        $payer->setPaymentMethod('credit_card')
            ->setPayerInfo(new PayerInfo(array('email' => 'jaypatel512-facilitator@hotmail.com')));

        // Add Credit Card to Funding Instruments
        $creditCard = new CreditCard();
        $creditCard->setType('visa')
            ->setNumber('4417119669820331')
            ->setExpireMonth('12')
            ->setExpireYear('2017')
            ->setCvv2('128');

        $fundingInstrument = new FundingInstrument();
        $fundingInstrument->setCreditCard($creditCard);
        $payer->setFundingInstruments(array($fundingInstrument));
        //Add Payer to Agreement
        $agreement->setPayer($payer);

        // Add Shipping Address
        $shippingAddress = new ShippingAddress();
        $shippingAddress->setLine1('111 First Street')
            ->setCity('Saratoga')
            ->setState('CA')
            ->setPostalCode('95070')
            ->setCountryCode('US');
        $agreement->setShippingAddress($shippingAddress);

        $agreement = $agreement->create($apiContext);

        var_dump($agreement);
        die;
    }

}