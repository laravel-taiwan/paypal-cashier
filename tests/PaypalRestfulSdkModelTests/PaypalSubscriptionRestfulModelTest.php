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
use Paypal\Api\Links;
use Carbon\Carbon;

class PaypalSubscriptionRestfulModelTest extends TestCase
{
    /**
     * Sample plan id. All agreements will be subscribed to this plan.
     */
    protected $samplePlanId = 'P-53R82169JN2163459DQ4RFBY';

    /**
     * Sample subscription id. All manipulations will be targeted towards this agreement.
     */
    protected $sampleSubscriptionId = 'I-VELLH556AAXL';

    public function setUp()
    {
        parent::setUp();

        $this->migrateSubscription();
    }

    protected function migrateSubscription()
    {
        Schema::create('paypal_subscription', function (Blueprint $table) {
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

        Schema::drop('paypal_subscription');
    }

    /**
     * Initialize Beyond\PaypalCashier\Subscription Instance.
     */
    public function test_init_subscription()
    {
        $subscription = new Subscription([
            'name' => 'sample agreement',
            'description' => 'sample description',
            'start_date' => Carbon::now()->toAtomString()
        ]);

        $this->assertInstanceOf('Beyond\PaypalCashier\Subscription', $subscription);
    }

    public function test_get_agreement_info()
    {
        $subscription = new Subscription([
            'name' => 'sample agreement',
            'description' => 'sample description',
            'start_date' => Carbon::now()->toAtomString()
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

//        $apiContext = new ApiContext(
//            new OAuthTokenCredential(
//                'ASwJABCijpIMKPIMHYYKX-hj6TYkdAehwP9kGOlMsFC28wy_SrHZzswDMz9q',
//                'EAdVhxA_E6lTO6oMoSV-MPZiVlkmV1IALSsbPOwOy7-Q2ceZGcc5dGdEYxr3'
//            )
//        );

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
    protected function planProvider($planId = NULL)
    {
        $planId = is_null($planId) ? $this->samplePlanId : $planId ;

        $apiContext = $this->apiContextProvider();

        $plan = new Plan;

        $plan = $plan->getByPlanId($planId, $apiContext);

        return $plan;
    }

    protected function creditCardProvider()
    {
        $creditCard = new CreditCard();
        $creditCard->setType('visa')
            ->setNumber('4032031282261386')
            ->setExpireMonth('02')
            ->setExpireYear('2020')
            ->setCvv2('123');

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

    protected function payerProvider2()
    {
        $payer = new Payer();

        $payer->setPaymentMethod('credit_card');

        $payer->setPayerInfo(new PayerInfo(array('email' => 'bryan@beyond.com')));

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

    public function test_get_plan()
    {
        $apiContext = $this->apiContextProvider();

        $plan = new Plan;

        $plan = $plan->getByPlanId($this->samplePlanId, $apiContext);

//        var_dump($plan);
//        die;
    }

    /**
     * Test create new billing agreement.
     *
     * Tests:
     *      1. Test create new agreement.
     *
     * @group PaypalCashier
     *
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
            'name' => 'sample agreement',
            'description' => 'sample description',
            'start_date' => Carbon::now()->addDay()->format('Y-m-d\TH:i:s\Z')
        ]);

        $subscription->setPlan($plan);
        $subscription->setPayer($payer);
        $subscription->setShippingAddress($shippingAddress);

        $subscription = $subscription->createSubscription($apiContext);

        $subscriptions = DB::table('paypal_subscription')->where('name', 'sample agreement');

        // 
        $this->assertEquals(1, $subscriptions->count());

        $this->sampleSubscriptionId = $subscriptions->first()->subscription_id;

        // Check subscription exists from paypal server
        $this->test_get_agreement();
    }

    public function __test_create_billing_agreement_2()
    {
        // need to prepare
        // 1. payer
        // 2. plan
        // 3. shipping address
        // 4. api context
        $apiContext = $this->apiContextProvider();
        $plan = $this->planProvider();

//        var_dump($plan->getSdkPlan()->getState());
//        die;
//        $plan = new Plan;

//        $plan->setId('P-38680909GR2448623JTT2S5Y');
//        var_dump(get_class($plan));
//        die;

//        $plan = $plan->getByPlanId('P-38680909GR2448623JTT2S5Y', $apiContext);

//        $payer = $this->payerProvider();
        $payer = $this->payerProvider2();
        $shippingAddress = $this->shippingAddressProvider();


        $subscription = new Subscription([
            'name' => 'day billing agreement',
            'description' => 'day billing agreement',
            'start_date' => Carbon::now()->addDay()->format('Y-m-d\TH:i:s\Z')
        ]);

        // apply subscription settings
        $subscription->setPlan($plan);
        $subscription->setPayer($payer);
        $subscription->setShippingAddress($shippingAddress);

        $subscription = $subscription->createSubscription($apiContext);

//        var_dump($subscription->getSdkSubscription());
//        die;
//        $count = DB::table('paypal_subscription')->where('name', 'sample agreement')->count();
//
//        $this->assertEquals(1, $count);
    }


    /**
     * Test get agreement by uid.
     *
     * @group PaypalCashier
     *
     * @todo the outcome of getName method is null. check later.
     * Tests:
     *      1. makesure agreement uid matches
     */
    public function test_get_agreement()
    {

        $apiContext = $this->apiContextProvider();

        $subscription = new Subscription();

        $subscription = $subscription->getBySubscriptionId($this->sampleSubscriptionId, $apiContext);

        $this->assertEquals('Active', $subscription->getState());

        $this->assertEquals($this->sampleSubscriptionId, $subscription->getId());
    }

    public function test_parse_ipn_message()
    {
//        $msg = 'payment_cycle=every+2+Months&txn_type=recurring_payment_profile_created&last_name=Shopper&initial_payment_status=Completed&next_payment_date=02%3A00%3A00+Jan+30%2C+2015+PST&residence_country=US&initial_payment_amount=1.00&currency_code=USD&time_created=21%3A42%3A21+Jan+28%2C+2015+PST&verify_sign=AgR5nWP4yUEpbgjsP604CIBg.1SKABaqxKf6OIqcWOCmW7osNcVEXzNU&period_type=+Regular&payer_status=unverified&test_ipn=1&tax=0.00&payer_email=huangchiheng%40gmail.com&first_name=Joe&receiver_email=huangc770216%40163.com&payer_id=8H64M5VGUE6H8&product_type=1&initial_payment_txn_id=3V93406267343630R&shipping=10.00&amount_per_cycle=110.00&profile_status=Active&charset=windows-1252&notify_version=3.8&amount=110.00&outstanding_balance=0.00&recurring_payment_id=I-KRHKU9GKCYNY&product_name=sample+description&ipn_track_id=98a4afd1062ba';

        $msg = 'mc_gross=110.00&period_type=+Regular&outstanding_balance=0.00&next_payment_date=03%3A00%3A00+Apr+03%2C+2015+PDT&protection_eligibility=Ineligible&payment_cycle=every+2+Months&tax=0.00&payer_id=8E6NUGFBKA3PY&payment_date=02%3A11%3A01+Feb+03%2C+2015+PST&payment_status=Pending&product_name=sample+description&charset=windows-1252&recurring_payment_id=I-SWD6NXGDCF88&first_name=&mc_fee=3.49&notify_version=3.8&amount_per_cycle=110.00&payer_status=unverified&currency_code=USD&business=huangc770216%40163.com&verify_sign=AbeIxGeMXLrb6o01UZ.gLzQ50ZVzATfl1sY1tx72kau56fwQCtrJU6qt&payer_email=huangchiheng%40gmail.com&initial_payment_amount=1.00&profile_status=Active&amount=110.00&txn_id=7M771832V48033915&payment_type=instant&last_name=&receiver_email=huangc770216%40163.com&payment_fee=3.49&receiver_id=DTCBVPRQQX4AJ&pending_reason=paymentreview&txn_type=recurring_payment&mc_currency=USD&residence_country=US&test_ipn=1&receipt_id=4562-9057-5156-0725&transaction_subject=&payment_gross=110.00&shipping=10.00&product_type=1&time_created=21%3A22%3A18+Feb+01%2C+2015+PST&ipn_track_id=c85ed4fda13d1';

        $parsedArr = $this->parseIpn($msg);

//        var_dump($parsedArr);

    }

    public function parseIpn($content)
    {
        if($content !== '')
        {
            $_str = urldecode($content);

            $_arr = explode('&', $_str);

            $responseArr = [];

            foreach($_arr as $key=>$value)
            {
                $_temp = explode('=', $value);

                $_trimmedKey = trim($_temp[0]);

                $responseArr[$_trimmedKey] = $_temp[1];
            }
        }

        return $responseArr;
    }

    public function test_storage_path()
    {
//        var_dump(storage_path());
//        die;
    }
}
