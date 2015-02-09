<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use PayPal\Api\ChargeModel;
use PayPal\Api\PaymentDefinition;
use PayPal\Api\MerchantPreferences;
use PayPal\Api\Currency;
use PayPal\Api\PatchRequest;
use PayPal\Api\Patch;
use PayPal\Common\PayPalModel;

/**
 * @group PaypalCashier
 * @author Bryan Huang
 *
 * @todo touch database 的測試應該由 Beyond\PaypalCashier\Plan 來做此測試不應該動到 database
 */
class PaypalPlanRepositoryTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->app->register('Beyond\PaypalCashier\PaypalCashierServiceProvider');

        $this->migratePaypalPlansTable();
    }

    public function tearDown()
    {
        parent::tearDown();

        Schema::drop('paypal_plans');
    }

    /**
     * Create paypal plan table.
     */
    protected function migratePaypalPlansTable()
    {
        Schema::create('paypal_plans', function (Blueprint $table) {
            $table->increments('id');
            $table->string('plan_id');
            $table->string('name');
            $table->string('description');
            $table->string('type');
            $table->string('state');

            $table->timestamps();
        });
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

    protected function planRepositoryProvider()
    {
        return app('Beyond\PaypalCashier\PlanRepository');
    }

    public function test_init_paypal_plan_repository()
    {
        $manager = app('Beyond\PaypalCashier\PlanRepository');

        $this->assertInstanceOf('Beyond\PaypalCashier\PlanRepository', $manager);
    }

    /**
     * Test create a new plan.
     *
     * 1. makesure it returns instance of Beyond\PaypalCashier\Plan
     * 2. makesure attributes has been set to Beyond\PaypalCashier\Plan
     * 3. makesure attributes has been set to Paypal\Api\Plan
     */
    public function test_get_new_plan()
    {
        $repo = $this->planRepositoryProvider();

        $info = array(
            'name' => 'sample plan',
            'description' => 'sample plan description',
            'type' => 'fixed',
        );

        $plan = $repo->getNewPlan($info);

        // 1.
        $this->assertInstanceOf('Beyond\PaypalCashier\Plan', $plan);

        // 2.
        $this->assertEquals('sample plan', $plan->name);
        $this->assertEquals('sample plan description', $plan->description);
        $this->assertEquals('fixed', $plan->type);

        // 3.
        $paypalPlan = $plan->getSdkPlan();
        $this->assertEquals('sample plan', $paypalPlan->getName());
        $this->assertEquals('sample plan description', $paypalPlan->getDescription());
        $this->assertEquals('fixed', $paypalPlan->getType());

    }

    /**
     * Test plan has been created.
     *
     * 1. makesure plan record has been created in database.
     */
    public function test_plan_repository_create_new_plan()
    {
        $repo = $this->planRepositoryProvider();

        $paymentDefination = $this->paymentDefinationProvider();

        $merchantPreference = $this->merchantPreferenceProvider();

        $plan = $repo->createPlan([
            'name'          =>  'sample plan',
            'description'   =>  'sample description',
            'type'          =>  'fixed',
        ], $paymentDefination, $merchantPreference);

        // 1.
        $count = DB::table('paypal_plans')->where('plan_id', $plan->getId())->count();
        $this->assertEquals(1, $count);

    }

    protected function createdPlanProvider()
    {
        $repo = $this->planRepositoryProvider();

        $paymentDefination = $this->paymentDefinationProvider();

        $merchantPreference = $this->merchantPreferenceProvider();

        $plan = $repo->createPlan([
            'name'          =>  'sample plan',
            'description'   =>  'sample description',
            'type'          =>  'fixed',
        ], $paymentDefination, $merchantPreference);

        return $plan;
    }

    /**
     * Test retrieve plan by Plan unique id.
     *
     * Tests:
     *      1. Assert that the retrieved plan uid equals to that in DataBase.
     */
    public function test_plan_repository_get_plan()
    {
        $repo = $this->planRepositoryProvider();

        $createdPlan = $this->createdPlanProvider();

        $plan = $repo->getPlanById($createdPlan->getId());

        $this->assertEquals($createdPlan->getId(), $plan->getId());
    }

    /**
     * Test update plan to Active state.
     *
     * Tests:
     *      1. Assert that the state of the created plan has been updated to "Active" state.
     */
    public function test_plan_repository_update_plan()
    {
        $repo = $this->planRepositoryProvider();

        // 建立新的 plan
        $createdPlan = $this->createdPlanProvider();

        $patch = new Patch();

        $value = new PayPalModel('{
	       "state":"ACTIVE"
	     }');

        $patch->setOp('replace')
            ->setPath('/')
            ->setValue($value);
        $patchRequest = new PatchRequest();
        $patchRequest->addPatch($patch);

        $repo->updatePlan($createdPlan, $patchRequest);

        $this->assertEquals($createdPlan->getState(), 'ACTIVE');

    }

    public function __test_dynamic_method()
    {
        $dummy = new Dummy;

        $method = 'Dummy';
//        $_needs = $this->{"get{$action}Needs"}($options);
        var_dump($dummy->{"set{$method}"}());
    }
}