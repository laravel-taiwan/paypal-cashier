<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Beyond\PaypalCashier\Plan;
use Mockery as m;
use PayPal\Api\ChargeModel;
use PayPal\Api\PaymentDefinition;
use PayPal\Api\MerchantPreferences;
use PayPal\Api\Currency;
use PayPal\Rest\ApiContext;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Api\PatchRequest;
use PayPal\Api\Patch;
use PayPal\Common\PayPalModel;

class PaypalPlanRestfulSdkTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->migratePaypalPlansTable();
    }

    protected function migratePaypalPlansTable()
    {
        Schema::create('paypal_plans', function (Blueprint $table) {
            $table->increments('id');
            $table->string('plan_id')->nullable();
            $table->string('name');
            $table->string('description');
            $table->string('type');
            $table->string('state')->nullable();

            $table->timestamps();
        });
    }

    protected function tearDown()
    {
        Schema::drop('paypal_plans');
    }

    /**
     * Provide sample PayPal\Api\PaymentDefination instance.
     *
     * @return PayPal\Api\PaymentDefination
     */
    protected function paymentDefinationProvider()
    {
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

        return $paymentDefinition;
    }

    protected function merchantPreferenceProvider()
    {
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

        return $merchantPreferences;
    }

    /**
     * Provide new plan to whom needs it.
     *
     * @return Beyond\PaypalCashier\Plan
     */
    protected function newPlanProvider()
    {
        // create new plan
        $plan = new Plan;

        $plan = new Plan([
            'name'          =>  'sample plan',
            'type'          =>  'fixed',
            'description'   =>  'sample plan'
        ]);

        $paymentDefinition = $this->paymentDefinationProvider();

        $merchantPreference = $this->merchantPreferenceProvider();

        $apiContext = $this->apiContextProvider();

        $plan->withPaymentDefinations($paymentDefinition)->withMerchantPreferences($merchantPreference)->createPlan($apiContext);

        return $plan;
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

    public function test_paypal_plan_init()
    {
        $plan = new Plan;

        $this->assertInstanceOf('Beyond\PaypalCashier\Plan', $plan);
    }

    /**
     * Test create new plan.
     *
     * Tests:
     *      1. Test attributes have been set to Beyond\PaypalCashier\Plan
     *      2. Test Sdk plan attributes has been set
     */
    public function test_init_new_plan()
    {
        // create a new plan instance
        $plan = new Plan([
            'name'          =>  'sample plan',
            'type'          =>  'fixed',
            'description'   =>  'sample plan'
        ]);

        // 1.
        $this->assertEquals($plan->name, 'sample plan');
        $this->assertEquals($plan->type, 'fixed');
        $this->assertEquals($plan->description, 'sample plan');

        // 2.

        $this->assertEquals($plan->getName(), 'sample plan');
        $this->assertEquals($plan->getType(), 'fixed');
        $this->assertEquals($plan->getDescription(), 'sample plan');
    }

    /**
     * Test plan model create new plan.
     *
     * Tests:
     *      1. Makesure plan id has been set.
     *      2. Makesure plan id has been saved into database.
     */
    public function test_create_new_plan()
    {
        $plan = new Plan([
            'name'          =>  'sample plan',
            'type'          =>  'fixed',
            'description'   =>  'sample plan'
        ]);

        $paymentDefinition = $this->paymentDefinationProvider();

        $merchantPreference = $this->merchantPreferenceProvider();

        $apiContext = $this->apiContextProvider();

        $plan  = $plan->withPaymentDefinations($paymentDefinition)->withMerchantPreferences($merchantPreference)->createPlan($apiContext);

        // 1.
        $this->assertNotNull($plan->getId());

        // 2.
        $count = DB::table('paypal_plans')->where('plan_id',  $plan->getId())->count();
        $this->assertEquals(1, $count);

    }

    /**
     * Test update existing plan.
     *
     * Tests:
     *      1. makesure specified plan has been updated.
     */
    public function test_update_existing_plan()
    {
        $newPlan = $this->newPlanProvider();
        $apiContext = $this->apiContextProvider();

        // initialize PayPal\Api\PatchRequest
        $patch = new Patch();


        $value = new PayPalModel('{
           "state":"ACTIVE"
         }');

        $patch->setOp('replace')
            ->setPath('/')
            ->setValue($value);
        $patchRequest = new PatchRequest();
        $patchRequest->addPatch($patch);

        $newPlan->updatePlan($patchRequest, $apiContext);


        // 1.
        $this->assertEquals('ACTIVE', $newPlan->getState());

    }

    public function test_plan_repository_save_plan_method()
    {
        $plan = new Plan;

        $plan->savePlan($information);

    }
}