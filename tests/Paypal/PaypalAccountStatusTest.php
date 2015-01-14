<?php
use GuzzleHttp\Client;

class PaypalAccountStatusTest extends TestCase
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

	public function tearDown()
	{
		$this->setUp();
	}

	public function fundedCreditCardProvider()
	{
		$cardInfo = array(
			'IPADDRESS'			=>	'220.136.43.59',
			'CREDITCARDTYPE'	=>	'visa',
			'ACCT'		=>	'4479091807941789',
			'EXPDATE'	=>	'042018', 
			'FIRSTNAME'	=>	'lots of money',
			'LASTNAME'	=>	'rich man', 
			'STREE'		=>	'some street',
			'CITY'		=>	'taipei', 
			'STATE'		=>	'taipei', 
			'COUNTRYCODE'	=>	'886', 
			'ZIP'		=>	'10466', 
		);

		return array(
			array($cardInfo)
		);
	}

	public function notFundedCreditCardProvider()
	{
		$cardInfo = array(
			'IPADDRESS'			=>	'220.136.43.59',
			'CREDITCARDTYPE'	=>	'visa',
			'ACCT'		=>	'4032036351251739',
			'EXPDATE'	=>	'042018', 
			'FIRSTNAME'	=>	'no money',
			'LASTNAME'	=>	'poor man', 
			'STREE'		=>	'some street',
			'CITY'		=>	'taipei', 
			'STATE'		=>	'taipei', 
			'COUNTRYCODE'	=>	'886', 
			'ZIP'		=>	'10466', 
		);

		return array(
			array($cardInfo)
		);
	}

	public function partiallyFundedCreditCardProvider()
	{
		$cardInfo = array(
			'IPADDRESS'			=>	'220.136.43.59',
			'CREDITCARDTYPE'	=>	'visa',
			'ACCT'		=>	'4032037590282972',
			'EXPDATE'	=>	'072019', 
			'FIRSTNAME'	=>	'five dollar',
			'LASTNAME'	=>	'partial man', 
			'STREE'		=>	'some street',
			'CITY'		=>	'taipei', 
			'STATE'		=>	'taipei', 
			'COUNTRYCODE'	=>	'886', 
			'ZIP'		=>	'10466', 
		);

		return array(
			array($cardInfo)
		);
	}

	public function getRequestBody()
	{
		$timeStamp = time()+date('Z');

		return [
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
			'BILLINGPERIOD'		=>	'Month', 
			'BILLINGFREQUENCY'	=>	1, 
			'TRIALBILLINGPERIOD'=>	'SemiMonth', // 試用期一個 cycle 是一個月
			'TRIALBILLINGFREQUENCY'=>	1, 	// 計費頻率
			'TRIALTOTALBILLINGCYCLES'=> 1,  // 幾個 cycle
			
			'INITAMT'			=>	'5',
			'AMT'				=>	'10',
			'MAXFAILEDPAYMENTS'	=>	3,
		];
	}

	/**
	 * @dataProvider fundedCreditCardProvider
	 */ 
	public function testAccountStatusWithFundedCreditCard($cardInfo)
	{
		
		$client = new Client;

		// var_dump(get_class($client));

		$body = array_merge($this->getRequestBody(), $cardInfo);

		$request = $client->createRequest('POST', 'https://api-3t.sandbox.paypal.com/nvp', [

			'body'	=>	$body, 

			'verify'	=>	true,

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

		var_dump($response->getBody()->getContents());
	}

	/**
	 * @dataProvider notFundedCreditCardProvider
	 */ 
	public function testAccountStatusWithNotFundedCreditCard($cardInfo)
	{
		$client = new Client;

		$body = array_merge($this->getRequestBody(), $cardInfo);

		$request = $client->createRequest('POST', 'https://api-3t.sandbox.paypal.com/nvp', [

			'body'	=>	$body, 

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

		var_dump($response->getBody()->getContents());
	}

	/**
	 * @dataProvider partiallyFundedCreditCardProvider
	 */ 
	public function testAccountStatusWithPartiallyFundedCreditCard($cardInfo)
	{
		$client = new Client;

		$body = array_merge($this->getRequestBody(), $cardInfo);

		$request = $client->createRequest('POST', 'https://api-3t.sandbox.paypal.com/nvp', [

			'body'	=>	$body, 

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

		var_dump($response->getBody()->getContents());
	}
}