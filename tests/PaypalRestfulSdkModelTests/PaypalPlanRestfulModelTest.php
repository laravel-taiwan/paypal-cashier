<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Beyond\PaypalCashier\Plan;
use Mockery as m;
include __DIR__.'/../PaypalTestCase.php';


//class PaypalPlanRestfulSdkTest extends TestCase
class PaypalPlanRestfulSdkTest extends PaypalTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->migratePaypalPlansTable();
    }

    /**
     * Remove paypal_plan table
     */
    protected function tearDown()
    {
        Schema::drop('paypal_plans');
    }

    /**
     * Migrate Paypal plan table.
     */
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

//        $paymentDefinition = $this->paymentDefinationProvider();

        $paymentDefinition = $this->dayPaymentDefinationProvider();

        $merchantPreference = $this->merchantPreferenceProvider();

        $apiContext = $this->apiContextProvider();

        $plan  = $plan->withPaymentDefinations($paymentDefinition)->withMerchantPreferences($merchantPreference)->createPlan($apiContext);

//        var_dump($plan->getId());
//        die;

//        var_dump($plan->getSdkPlan());
//        die;
        // 1.
//        $this->assertNotNull($plan->getId());
//
//        // 2.
//        $count = DB::table('paypal_plans')->where('plan_id',  $plan->getId())->count();
//        $this->assertEquals(1, $count);

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

    public function test_create_sample_plan_for_subscription()
    {
        $samplePlan = new Plan([
            'name'          =>  'test plan',
            'type'          =>  'fixed',
            'description'   =>  'for testing purpose'
        ]);

        $paymentDefination = $this->paymentDefinationProvider();
        $merchantPreferences= $this->merchantPreferenceProvider();
        $apiContext = $this->apiContextProvider();

        $samplePlan = $samplePlan->withPaymentDefinations($paymentDefination)->withMerchantPreferences($merchantPreferences)->createPlan($apiContext);

        // update sample plan state to active
        $patch = new Patch();

        $value = new PayPalModel('{
           "state":"ACTIVE"
         }');

        $patch->setOp('replace')
            ->setPath('/')
            ->setValue($value);
        $patchRequest = new PatchRequest();
        $patchRequest->addPatch($patch);


        $samplePlan->updatePlan($patchRequest, $apiContext);

        var_dump($samplePlan);
        die;
    }

    public function test_activate_sample_plan()
    {


    }

    public function test_plan_repository_save_plan_method()
    {
        $plan = new Plan;

        $plan->savePlan($information);

    }
}