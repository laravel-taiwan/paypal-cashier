<?php

use Beyond\PaypalCashier\Plan;

/*
| paypal plan
--------------------------------------
| plan
| 
|
|
*/
class PaypalPlanTest extends TestCase
{
	public function setUp()
	{
		
		parent::setUp();
		Plan::flushEventListeners();
		Plan::boot();
	}

	public function tearDown()
	{
		$this->setUp();
	}

	/**
	 * 測試建立一個新 plan
	 */ 
	public function testCreatePlan()
	{
		$plan = Plan::create([
			'title'					=>	'monthly',
			'plan_name'				=>	'monthly',
			'total_billing_cycle'	=>	12,
			'billing_period'		=>	'month',
			'billing_frequency'		=>	1,
			'hasTrial'				=>	false,
		]);

		$this->assertEquals('monthly', $plan->title);
		$this->assertEquals('monthly', $plan->plan_name);
	}

	/**
	 * 測試建立一個有 trial 的 plan
	 */ 
	public function testCreatePlanWithTrialPeriod()
	{
		$plan = Plan::create([
			'title'							=>	'monthly',
			'plan_name'						=>	'monthly',
			'total_billing_cycle'			=>	12,
			'billing_period'				=>	'month',
			'billing_frequency'				=>	1,
			'hasTrial'						=>	true,
			'trial_total_billing_cycle'		=>	1,
			'trial_billing_period'			=>	'SemiMonth',
			'trial_billing_frequency'		=>	1
		]);

		$this->assertTrue($plan->hasTrial);
	}

	public function testMatchPlanName()
	{
		$plan = Plan::create([
			'title'							=>	'monthly',
			'plan_name'						=>	'monthly',
			'total_billing_cycle'			=>	12,
			'billing_period'				=>	'month',
			'billing_frequency'				=>	1,
			'hasTrial'						=>	true,
			'trial_total_billing_cycle'		=>	1,
			'trial_billing_period'			=>	'SemiMonth',
			'trial_billing_frequency'		=>	1
		]);

		$plan = Plan::getPlan('monthly');

		$this->assertEquals(1 ,$plan->hasTrial);

		$nullPlan = Plan::getPlan('hello');

		$this->assertNull($nullPlan);
	}
}