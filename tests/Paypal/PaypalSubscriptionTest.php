<?php

use Beyond\Module\Cashier\PaypalCashier\Subscription;
use GuzzleHttp\Client;

class PaypalSubscriptionTest extends TestCase
{

	public function setUp()
	{
		parent::setUp();
		Subscription::flushEventListeners();
		Subscription::boot();
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
				'TRIALBILLINGPERIOD'=>	'Month', // 試用期一個 cycle 是一個月
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
	 * 測試建立新的 profile
	 *
	 * 
	 * @dataProvider sampleDataProvider
	 */
	 public function testCreateSubscription($params)
	 {
	 	
	 	$subscription = new Subscription;

	 	$response = $subscription->createSubscription($params);


	 	$this->assertInstanceOf('Beyond\Module\Cashier\PaypalCashier\Subscription', $response);
	 	$this->assertEquals('Success',$response->ack);
	 	$this->assertEquals('ActiveProfile',$response->profileStatus);

	 	$profile = DB::table('paypal_subscription')->where('id', $subscription->id)->get(); 

	 	// 測試 db 確實有記錄
	 	$this->assertTrue($profile[0]->profileStatus === 'ActiveProfile');
	 } 

	 /**
	  * 
	  *
	  * @dataProvider sampleDataProvider
	  * @expectedException Exception
	  */ 
	 public function testFailCreatingSubscription($params)
	 {
	 	$params['AMT'] = '';

	 	$subscription = new Subscription;

	 	$response = $subscription->createSubscription($params);
	 }

	 /**
	  * 測試儲存 subscription
	  *
	  * @dataProvider sampleDataProvider
	  */ 
	 public function testSaveSubscription($params)
	 {

	 	// 建立新的 subscription
	 	$subscription = new Subscription;

	 	$response = $subscription->createSubscription($params);



	 	// 修改此 subscription 的內容
	 	$info1 = $subscription->getSubscriptionInfo();

	 	var_dump($info1);

	 	// $updateParams = [

	 	// 	'DESC'					=> 'some awesome new subscription',
	 	// ];

	 	// $response = $subscription->saveSubscription($updateParams);

	 	// $info2 = $subscription->getSubscriptionInfo();

	 	// $this->assertEquals($info2['DESC'], 'some awesome new subscription');
	 	// $this->assertTrue(($info1['DESC'] !== $info2['DESC']));
	 }

	 /**
	  * 測試取消訂閱
	  *
	  * @dataProvider sampleDataProvider
	  */ 
	 public function testCancelSubscription($params)
	 {
	 	// 建立新的訂閱
	 	$subscription = new Subscription;

	 	$response = $subscription->createSubscription($params);

	 	$subscription->cancel();

	 	$info = $subscription->getSubscriptionInfo();

	 	//var_dump($info);

	 	$this->assertEquals('Cancelled', $info['STATUS']);

	 }

	 /**
	  * 
	  *
	  *
	  */ 
	 public function testGuzzleCreateStreamData()
	 {
	 	$client = new Client;

	 	// 建立 request
	 	$request = $client->createRequest('POST', 'https://api-3t.sandbox.paypal.com/nvp', [

	 		'config'	=>	[
	 			'stream'	=>	true
	 		]
	 	]);
	 		
	 	
	 	$body = $request->getBody();

	 	$body->replaceFields(
	 		[
	 			'USER'		=>	'huangc770216_api1.163.com',
				'PWD'		=>	'1368327303',
				'SIGNATURE'	=>	'A3YwMG26ZPCah7erlKgLaXRPQxVwAOGprmfUvuvaQvmzZUcrjTfef5n7',
				'METHOD'	=>	'CreateRecurringPaymentsProfile',
				'VERSION'	=>	'115',		 		
	 		]

	 		
	 	);

	 	
	 	// var_dump($request->getScheme());
	 	// $response = $request->getConfig();

	 	// var_dump(get_class($response));
	 	// var_dump($body->getFields());
	 	// $request
	 	// var_dump($request->getBody()->getFields());
	 	// var_dump($request->getBody()->getContents());
	 }


	 public function testPhpStringFunction()
	 {
	 	$stack = 'asdkfjasdfasSuccess';

	 	var_dump(strripos($stack, 'success'));
	 	
	 }

	 public function testSaveNewSubscriptionData()
	 {

	 	$this->marTestIncomplete(); 

	 	$data = array (
	 		"title"			=>  "I-BWCG491Y486S", 
  			"PROFILEID"		=>	"I-BWCG491Y486S",
			"PROFILESTATUS"	=>	"ActiveProfile",
			"TIMESTAMP"		=>	"2014-07-07T09:10:06Z",
			"CORRELATIONID"	=>	"a83b17e2870a",
			"ACK"			=>	"Success",
			"VERSION"		=>	"115",
			"BUILD"			=>	"11457922"
  		);

	 	

	 	$_obj = new Subscription;

	 	// $_obj->title = "I-BWCG491Y486S";
	 	// $_obj->PROFILEID = "I-BWCG491Y486S";
	 	// $_obj->PROFILESTATUS = "ActiveProfile";
	 	// $_obj->TIMESTAMP = "2014-07-07T09:10:06Z";

	 	$_obj->fill($data);

	 	// var_dump($_obj->title);

	 	// $_obj->title = 'I-BWCG491Y486S';

	 	$_obj->save();

	 	// $_obj->save();

	 	var_dump(DB::table('paypal_subscription')->get());

	}
}