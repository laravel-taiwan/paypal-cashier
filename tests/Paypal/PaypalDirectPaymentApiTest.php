<?php 

use GuzzleHttp\Client;
use GuzzleHttp\Post\PostFile;

class PaypalDirectPaymentApiTest extends TestCase
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

	public function tearUp()
	{
		$this->setUp();
	} 

	/**
	 * Provide guzzle client instance
	 */
	public function guzzleClientProvider()
	{
		$client = new Client();

		return array(
			array($client)
		);
	}

	/**
	 * Test making the first call to paypal api 
	 *
	 * @dataProvider guzzleClientProvider
	 */
	public function testFirstCallToApi($client)
	{		
		
		$request = $client->createRequest('POST', 'https://api-3t.sandbox.paypal.com/nvp', [
			'body'	=>	[
				'USER'		=>	$this->user,
				'PWD'		=>	$this->pwd,
				'SIGNATURE'	=>	$this->signature,
				'METHOD'	=>	'DoDirectPayment',
				'VERSION'	=>	'115',
				'PAYMENTACTION'	=>	'SALE',
				'IPADDRESS'	=>	'220.136.43.59',
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
				'AMT'		=>	'8'

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

		// $response_arr = explode('&', $response->getBody->getContents());
		$this->assertTrue(!!strripos($response->getBody()->getContents(), 'SUCCESS'));
		
	}

	/**
	 *
	 *
	 */
	public function testCreateDateFormat()
	{	
		$timeStamp = time()+date('Z');

		var_dump(gmdate("Y-m-d H:i:s",$timeStamp));
	}

	public function testCallWithCurl()
	{
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, 'https://api-3t.sandbox.paypal.com/nvp');
		curl_setopt($ch, CURLOPT_VERBOSE, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		// curl_setopt($ch, CURLOPT_POSTFIELDS, $this->query_str);
		curl_setopt($ch,CURLOPT_TIMEOUT,30); 
		$res = curl_exec($ch);
		curl_close($ch);

		var_dump($res); 
	}

}