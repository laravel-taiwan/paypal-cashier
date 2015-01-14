<?php

use GuzzleHttp\Client;

class PaypalRecurringPaymentTest extends TestCase
{

	/**
	 * paypal api credential - username
	 *
	 * @var string
	 */
	protected $user = 'huangc770216_api1.163.com';

	/**
	 * paypal api credential - pwd
	 *
	 * @var string
	 */
	protected $pwd = '1368327303';

	/**
	 * Paypal api version
	 *
	 * @var string
	 */
	protected $version = '115';

	/**
	 * paypal api credential - signature
	 *
	 * @var string
	 */
	protected $signature = 'A3YwMG26ZPCah7erlKgLaXRPQxVwAOGprmfUvuvaQvmzZUcrjTfef5n7';

	public function setUp()
	{
		parent::setUp();
	}

	public function guzzleClientProvider()
	{
		$client = new Client();

		return array(
			array($client)
		);
	}

	/**
	 * 測試呼叫 paypal recurring payment api
	 *
	 * @dataProvider guzzleClientProvider
	 */
	public function testGetRecurringProfileId($client)
	{
		$timeStamp = time()+date('Z');

		$request = $client->createRequest('POST', 'https://api-3t.sandbox.paypal.com/nvp', [
			'body'	=>	[
				'USER'		=>	$this->user,
				'PWD'		=>	$this->pwd,
				'SIGNATURE'	=>	$this->signature,
				'METHOD'	=>	'CreateRecurringPaymentsProfile',
				'VERSION'	=>	'115',
				/*
				 *	Recurring payment info start	
				 */
				'PROFILESTARTDATE'	=>	gmdate("Y-m-d H:i:s",$timeStamp),
				'DESC'				=>	'subscribe on shit',
				'TOTALBILLINGCYCLES'=>	0,
				'BILLINGPERIOD'		=>	'SemiMonth', 
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
				

			], 

			'config'	=>	
			[
				'curl'	=>	
				[
				 	CURLOPT_SSL_VERIFYPEER	=> 0,
	            	CURLOPT_RETURNTRANSFER	=> 1,
	            	CURLOPT_VERBOSE			=> 1	
				]
			],

			'stream'	=>	true
		]);

		$response = $client->send($request);	

		var_dump(urldecode($response->getBody()->getContents()));
		// $response_str = $response->getBody()->getContents();

		// $this->assertTrue( !!stripos($response_str, 'Success') );
	}

	/**
	 * 嘗試取得 recurring profile id
	 */
	public function getPaypalRecurringProfileId($client)
	{
		$timeStamp = time()+date('Z');

		$request = $client->createRequest('POST', 'https://api-3t.sandbox.paypal.com/nvp', [
			'body'	=>	[
				'USER'		=>	$this->user,
				'PWD'		=>	$this->pwd,
				'SIGNATURE'	=>	$this->signature,
				'METHOD'	=>	'CreateRecurringPaymentsProfile',
				'VERSION'	=>	'115',
				/*
				 *	Recurring payment info start	
				 */
				'PROFILESTARTDATE'	=>	gmdate("Y-m-d H:i:s",$timeStamp),
				// 'PROFILESTARTDATE'	=>	'2014-08-2 03:00:00',
				'DESC'				=>	'subscribe on shit',
				'TOTALBILLINGCYCLES'=>	0,
				'BILLINGPERIOD'		=>	'Month', 
				'BILLINGFREQUENCY'	=>	1, 
				// 'TRIALBILLINGPERIOD'=>	'Month', // 試用期一個 cycle 是一個月
				// 'TRIALBILLINGFREQUENCY'=>	1, 	// 計費頻率
				// 'TRIALTOTALBILLINGCYCLES'=> 1,  // 幾個 cycle
				
				'AMT'				=>	'10',
				'MAXFAILEDPAYMENTS'	=>	3,

				'IPADDRESS'			=>	'220.136.43.59',
				'CREDITCARDTYPE'	=>	'visa',
				'ACCT'		=>	'4390755230449356',
				'EXPDATE'	=>	'052018', 
				'FIRSTNAME'	=>	'chiheng',
				'LASTNAME'	=>	'huang', 
				'STREE'		=>	'some street',
				'CITY'		=>	'taipei', 
				'STATE'		=>	'taipei', 
				'COUNTRYCODE'	=>	'886', 
				'ZIP'		=>	'10466', 
				

			], 

			'config'	=>	
			[
				'curl'	=>	
				[
				 	CURLOPT_SSL_VERIFYPEER	=> 0,
	            	CURLOPT_RETURNTRANSFER	=> 1,
	            	CURLOPT_VERBOSE			=> 1	
				]
			],

			'stream'	=>	true
		]);

		$response = $client->send($request);	

		$_str = urldecode($response->getBody()->getContents());

		$_arr = explode('&', $_str);

		$response_arr = [];

		foreach($_arr as $value)
		{

			$_temp = explode('=', $value);

			$response_arr[$_temp[0]] = $_temp[1];

		
			// var_dump($__temp);
			// $response_arr = 

			// array_merge($response_arr, $_temp);
		}

		var_dump($response_arr);
		// $response_arr = json_decode( $response->getBody()->getContents(), true );

		return $response_arr['PROFILEID'];
	}

	/**
	 * 測試取消 recurring payment
	 *
	 * @dataProvider guzzleClientProvider
	 */
	public function testCancelRecurringProfile($client)
	{	
		$profileId = $this->getPaypalRecurringProfileId($client);

		// var_dump($profileId);

		$request = $client->createRequest('POST', 'https://api-3t.sandbox.paypal.com/nvp', [
			'body'	=>	[
				'USER'		=>	$this->user,
				'PWD'		=>	$this->pwd,
				'SIGNATURE'	=>	$this->signature,
				'METHOD'	=>	'ManageRecurringPaymentsProfileStatus',
				'VERSION'	=>	'115',
				/*
				 *	Recurring payment info start	
				 */
				'PROFILEID'	=>	"{$profileId}",
				'ACTION'	=>	'cancel'


			], 

			'config'	=>	
			[
				'curl'	=>	
				[
				 	CURLOPT_SSL_VERIFYPEER	=> 0,
	            	CURLOPT_RETURNTRANSFER	=> 1,
	            	CURLOPT_VERBOSE			=> 1	
				]
			],

			'stream'	=>	true
		]);

		$response = $client->send($request);	

		// var_dump(urldecode($response->getBody()->getContents()));

		$this->assertTrue(!!strripos($response->getBody()->getContents(), 'Success'));
	
	}

	/**
	 * 取得 Recurring profile info
	 *
	 * @dataProvider guzzleClientProvider
	 */
	public function testGetReucrringProfileInfo($client)
	{
		$profileId = $this->getPaypalRecurringProfileId($client);

		// var_dump($profileId);
		$request = $client->createRequest('POST', 'https://api-3t.sandbox.paypal.com/nvp', [
			'body'	=>	[
				'USER'		=>	$this->user,
				'PWD'		=>	$this->pwd,
				'SIGNATURE'	=>	$this->signature,
				'METHOD'	=>	'GetRecurringPaymentsProfileDetails',
				'VERSION'	=>	'115',
				/*
				 *	Recurring payment info start	
				 */
				'PROFILEID'	=>	"{$profileId}",

			], 

			'config'	=>	
			[
				'curl'	=>	
				[
				 	CURLOPT_SSL_VERIFYPEER	=> 0,
	            	CURLOPT_RETURNTRANSFER	=> 1,
	            	CURLOPT_VERBOSE			=> 1	
				]
			],

			'stream'	=>	true
		]);

		$response = $client->send($request);

		// var_dump(urldecode($response->getBody()->getContents()));

		$_str = urldecode($response->getBody()->getContents());

		$_arr = explode('&', $_str);

		$response_arr = [];

		foreach($_arr as $value)
		{

			$_temp = explode('=', $value);

			$response_arr[$_temp[0]] = $_temp[1];
		}

		var_dump($response_arr);
		// SuccessWithWarning
		$this->assertTrue(is_array($response_arr));
		// var_dump(strpos($response_arr['ACK'], 'uccess'));
		// $this->assertTrue( !!strpos($response_arr['ACK'], 'Success'));
		// var_dump($response_arr);
	}


	/**
	 * 延長 recurring payment cycle
	 * 
	 * @dataProvider guzzleClientProvider
	 */
	public function testExtendingRecurringPayment($client)
	{
		$profileId = $this->getPaypalRecurringProfileId($client);

		$request = $client->createRequest('POST', 'https://api-3t.sandbox.paypal.com/nvp', [
			'body'	=>	[
				'USER'		=>	$this->user,
				'PWD'		=>	$this->pwd,
				'SIGNATURE'	=>	$this->signature,
				'METHOD'	=>	'UpdateRecurringPaymentsProfile',
				'VERSION'	=>	'115',
				/*
				 *	Recurring payment info start
				 * @todo 把 cycle 設為 12 會出錯	
				 */
				'PROFILEID'	=>	"{$profileId}",
				'ADDITIONALBILLINGCYCLES'	=>	11,


			], 

			'config'	=>	
			[
				'curl'	=>	
				[
				 	CURLOPT_SSL_VERIFYPEER	=> 0,
	            	CURLOPT_RETURNTRANSFER	=> 1,
	            	CURLOPT_VERBOSE			=> 1	
				]
			],

			'stream'	=>	true
		]);

		// var_dump(get_class($request));
		$response = $client->send($request);

		var_dump($response->getBody()->getContents());
	}

}
