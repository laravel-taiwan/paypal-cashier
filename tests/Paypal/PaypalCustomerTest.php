<?php

use Beyond\Module\Cashier\PaypalCashier\Subscription;
use Beyond\Module\Cashier\PaypalCashier\Customer;


/**
 * @group paypalCashier
 */ 
class PaypalCustomerTest extends TestCase
{
	public function setUp()
	{
		parent::setUp();
		Subscription::flushEventListeners();
		Subscription::boot();

		Customer::flushEventListeners();
		Customer::boot();
	}

	public function tearDown()
	{
		$this->setUp();
	}

	/**
	 * 提供測試資料
	 * @todo fix warning
	 * 
	 */
	public function sampleDataProvider()
	{
		$timeStamp = time()+date('Z');

	 	/*
		 *	Recurring payment info	
		 */
	 	$params = [
	 		'PROFILESTARTDATE'	=>	gmdate("Y-m-d H:i:s",$timeStamp),
			'DESC'				=>	'subscribe on shit',
			'TOTALBILLINGCYCLES'=>	12,
			'BILLINGPERIOD'		=>	'Month', 
			'BILLINGFREQUENCY'	=>	1, 
			'TRIALBILLINGPERIOD'=>	'SemiMonth', // 試用期一個 cycle 是一個月
			'TRIALBILLINGFREQUENCY'=>	1, 	// 計費頻率
			'TRIALTOTALBILLINGCYCLES'=> 1,  // 幾個 cycle
			
			'AMT'				=>	'10',
			'MAXFAILEDPAYMENTS'	=>	3,

			'IPADDRESS'			=>	'220.136.43.59',
			'CREDITCARDTYPE'	=>	'visa',
			'ACCT'		=>	'4479091807941789',
			'EXPDATE'	=>	'042018', 
			'FIRSTNAME'	=>	'chiheng',
			'LASTNAME'	=>	'huang', 
			'STREE'		=>	'some street',
			'CITY'		=>	'taipei', 
			'STATE'		=>	'taipei', 
			'COUNTRYCODE'	=>	'886', 
			'ZIP'		=>	'10466', 
	 	];

	 	return array(
	 		array($params)
	 	);
	}



	/**
	 * 提供 customer 測試資料
	 */ 
	public function customerSampleDataProvider()
	{
		$_data = [
			'title'			=>	'I-LAX4W6WHV35K',
			'slug'			=>	'i-lax4w6whv35k',
			'name'			=>	'iLAX4W6WHV35K',
			'profileId'		=>	'I-LAX4W6WHV35K', 
			'profileStatus'	=>	'ActiveProfile',
			'timeStamp'		=>	'2014-07-11T05:04:47Z',
			'correlationId'	=>	'1f15235683bc5',
			'ack'			=>	'Success',
			'version'		=>	'115',
			'build'			=>	'build'
		];

		return array(
			array($_data)
		);
	}


	/**
	 * 測試建立新的 subscription
	 *
	 * @dataProvider sampleDataProvider
	 */ 
	public function testCustomerCreateSubscription($params)
	{
		// $customer = Customer::create(['title'	=>	$uuid]);
		$customer = new Customer; 

		$customer->save();

		$subscription = $customer->createSubscription($params);

		$this->assertTrue( !!$subscription->profileId );
	}

	/**
	 * 測試 Customer Retrieve 功能
	 */ 
	public function testCustomerRetrieve()
	{
		$customer = new Customer;

		$customer->fill(['title'	=>	'test', 'uid'	=>	'someuid']);	

		$customer->save();

		$customer = Customer::retrieve('someuid');

		$this->assertEquals('someuid', $customer->uid);
	}

	/**
	 * 測試取消 subscription
	 *
	 * @dataProvider sampleDataProvider
	 */ 
	public function testCustomerCancelSubscription($params)
	{
		// 建立新的 user
		$customer = new Customer;

		$subscription = $customer->createSubscription($params);

		// 取得最新建立的 subscription		
		$_subscription = $customer->subscription()->get()[0];

		// cancel 指定的 profile
		$subscription = $customer->cancelSubscription($_subscription->profileId);

		// 檢查 status
		$this->assertEquals('CancelledProfile', $subscription->profileStatus);
	}	

	/**
	 * 測試 update subscription
	 *
	 * @dataProvider sampleDataProvider
	 */
	 public function testCustomerUpdateSubscription($_params)
	 {
	 	$customer = new Customer;

	 	$customer->save();

	 	$subscription = $customer->createSubscription($_params);

	 	$_updateData = [

	 		'profileId'	=>	$subscription->profileId,
	 		'DESC'		=>	'modified'
	 	];

	 	$subscription = $customer->updateSubscription($_updateData);

	 	$info = $subscription->getSubscriptionInfo();

	 	$this->assertEquals('modified', $info['DESC']);
	 } 

	 // public function testCaseInsensitiveRegx()
	 // {
	 // 	$this->assertTrue(preg_match("/profileid/i", 'ProfileId'));
	 // }
}

