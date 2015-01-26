<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Beyond\PaypalCashier\Plan;
use Beyond\PaypalCashier\Subscription;
use PayPal\Api\Plan as PaypalPlan;
use PayPal\Rest\ApiContext;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Api\Payer;
use PayPal\Api\PayerInfo;
use PayPal\Api\CreditCard;
use PayPal\Api\FundingInstrument;
use PayPal\Api\ShippingAddress;
use Paypal\Api\Agreement;
use Carbon\Carbon;

class PaypalSubscriptionRestfulModelTest extends TestCase
{
    /**
     * Sample plan id. All agreements will be subscribed to this plan.
     */
    protected $samplePlanId = 'P-53R82169JN2163459DQ4RFBY';

    public function setUp()
    {
        parent::setUp();

        $this->migrateSubscription();
    }

    protected function migrateSubscription()
    {
        Schema::create('paypal_agreements', function (Blueprint $table) {
            $table->increments('id');
            $table->string('subscription_id');
            $table->string('name');
            $table->string('description');
            $table->timestamp('start_date');
            $table->timestamps();
        });
    }

    public function tearDown()
    {
        parent::tearDown();

        Schema::drop('paypal_agreements');
    }

    /**
     * Initialize Beyond\PaypalCashier\Subscription Instance.
     */
    public function test_init_subscription()
    {
        $subscription = new Subscription([
            'name'          =>  'sample agreement',
            'description'   =>  'sample description',
            'start_date'    =>  Carbon::now()->toAtomString()
        ]);

        $this->assertInstanceOf('Beyond\PaypalCashier\Subscription', $subscription);
    }

    public function test_get_agreement_info()
    {
        $subscription = new Subscription([
            'name'          =>  'sample agreement',
            'description'   =>  'sample description',
            'start_date'    =>  Carbon::now()->toAtomString()
        ]);

        $this->assertEquals('sample agreement', $subscription->getName());
        $this->assertEquals('sample description', $subscription->getDescription());
        $this->assertNotEmpty($subscription->getStartDate());

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
     * Provide Instance of Beyond\PaypalCashier\Plan
     *
     * @return Beyond\PaypalCashier\Plan
     */
    protected function planProvider()
    {
        $apiContext = $this->apiContextProvider();

        $plan = new Plan;

        $plan = $plan->getByPlanId($this->samplePlanId, $apiContext);

        return $plan;
    }

    protected function creditCardProvider()
    {
        $creditCard = new CreditCard();
        $creditCard->setType('visa')
            ->setNumber('4417119669820331')
            ->setExpireMonth('12')
            ->setExpireYear('2017')
            ->setCvv2('128');

        return $creditCard;
    }

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

    public function shippingAddressProvider()
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
     * 提示：
     *  1. paypal sandbox 在建立新的 agreement 時常會失敗 (internal server error 500).
     *  2. 在建立新的 agreement 時要注意 start_date 不能是當前時間，最快也要是當前時間加上 1 天.
     *      - http://stackoverflow.com/questions/25858816/paypal-billing-agreements-rest-api-how-to-start-immediately
     */
    public function test_create_billing_agreement()
    {
        // need to prepare
        // 1. payer
        // 2. plan
        // 3. shipping address
        // 4. api context
        $apiContext = $this->apiContextProvider();
        $plan = $this->planProvider();
        $payer = $this->payerProvider();
        $shippingAddress = $this->shippingAddressProvider();


        $subscription = new Subscription([
            'name'          =>  'sample agreement',
            'description'   =>  'sample description',
            'start_date'    =>  Carbon::now()->addDay()->format('Y-m-d\TH:i:s\Z')
        ]);

        $subscription->setPlan($plan);
        $subscription->setPayer($payer);
        $subscription->setShippingAddress($shippingAddress);

        $subscription = $subscription->createSubscription($apiContext);

        $count = DB::table('paypal_agreements')->where('name', 'sample agreement')->count();

        $this->assertEquals(1, $count);
    }

    /**
     *
     *
     *
     */
//    public function test_create_sample_plan()
//    {
//
//    }


}