<?php

use PayPal\Rest\ApiContext;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Api\Plan;
use PayPal\Api\PaymentDefinition;
use PayPal\Api\MerchantPreferences;
use PayPal\Api\Currency;
use PayPal\Api\ChargeModel;

/**
 * 1. [x] 嘗試使用 paypal restful api 建立 plan, makesure plan has been created.
 */
class PaypalRestfulSdkSubscriptionTest extends TestCase
{

    protected $credentials = [
        'client_id'     => 'AW3K_xAvssx_6NKlwND8AkOdilveb2t1n9qtcrc5DHxfvnnrME3u9uPqI9gv',
        'client_secret' =>  'EJRSABDIeN15U2zw32Ai0YlB2IruSjRetDDG2eiK7MwqoWR6mDlgZ5xXaOq2'
    ];

    public function setUp()
    {
        parent::setUp();

        ini_set('display_errors', '1');
    }

    protected function getApiContext()
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

    public function test_sdk_get_api_context()
    {
        $apiContext = $this->getApiContext();

        $this->assertInstanceOf($apiContext, "PayPal\\Rest\\ApiContext");
    }

    /**
     * Prepare customized plan info.
     *
     * @return PayPal\Api\Plan
     */
    protected function getPlanInfo()
    {
        // Create a new instance of Plan object
        $plan = new Plan();

        // # Basic Information
        // Fill up the basic information that is required for the plan
        $plan->setName('T-Shirt of the Month Club Plan')
            ->setDescription('Template creation.')
            ->setType('fixed');

        // # Payment definitions for this billing plan.
        $paymentDefinition = new PaymentDefinition();

        // The possible values for such setters are mentioned in the setter method documentation.
        // Just open the class file. e.g. lib/PayPal/Api/PaymentDefinition.php and look for setFrequency method.
        // You should be able to see the acceptable values in the comments.
        $paymentDefinition->setName('Regular Payments')
            ->setType('REGULAR')
            ->setFrequency('Month')
            ->setFrequencyInterval("2")
            ->setCycles("12")
            ->setAmount(new Currency(array('value' => 100, 'currency' => 'USD')));

        // Charge Models
        $chargeModel = new ChargeModel();
        $chargeModel->setType('SHIPPING')
            ->setAmount(new Currency(array('value' => 10, 'currency' => 'USD')));

        $paymentDefinition->setChargeModels(array($chargeModel));

        $merchantPreferences = new MerchantPreferences();

        // $baseUrl = getBaseUrl();
        // ReturnURL and CancelURL are not required and used when creating billing agreement with payment_method as "credit_card".
        // However, it is generally a good idea to set these values, in case you plan to create billing agreements which accepts "paypal" as payment_method.
        // This will keep your plan compatible with both the possible scenarios on how it is being used in agreement.
        $merchantPreferences->setReturnUrl("http://www.return.com")
            ->setCancelUrl("http://www.cancel.com")
            ->setAutoBillAmount("yes")
            ->setInitialFailAmountAction("CONTINUE")
            ->setMaxFailAttempts("0")
            ->setSetupFee(new Currency(array('value' => 1, 'currency' => 'USD')));


        $plan->setPaymentDefinitions(array($paymentDefinition));
        $plan->setMerchantPreferences($merchantPreferences);

        return $plan;
    }

    /**
     * Try to create plan using paypal php restful sdk.
     *
     * 1. makesure plan status = created.
     * 2. makesure id is not null.
     */
    public function test_sdk_create_plan()
    {
        $apiContext = $this->getApiContext();

        $plan = $this->getPlanInfo();

        $plan = $plan->create($apiContext);

        // 1.
        $this->assertEquals('CREATED', $plan->getState());

        // 2.
        $this->assertNotEmpty($plan->getId());
    }
}