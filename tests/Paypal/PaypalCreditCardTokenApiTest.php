<?php

use GuzzleHttp\Client;
use GuzzleHttp\Post\PostFile;

class PaypalCreditCardTokenApiTest extends TestCase
{
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
	 *
	 *
	 * @dataProvider guzzleClientProvider
	 */ 
	public function testPaypalRestfulFirstCall($client)
	{
		$request = $client->createRequest('POST', 'https://api.sandbox.paypal.com/v1/oauth2/token', [
			// 'Authorization'	=>	'Bearer ASExehBlunSTszcSEhc__w7P81AFACksp2WaMZ1u0LlB6TCpDF6j8whNZLed:EOvU8hB8XRJzfdwDYzJ6m-nZnWpmXgpQktqYIEfbvFi4VuPW8JXylxMKF7fU',
			'auth' =>  ['EOJ2S-Z6OoN_le_KS1d75wsZ6y0SFdVsY9183IvxFyZp','EClusMEUk8e9ihI7ZdVLF5cZ6y0SFdVsY9183IvxFyZp'],
			'headers'	=>	[
				'Content-Type'		=>	'application/json',
				'Accept'			=>	'application/json',
				'Accept-Language' 	=>	'en_US',
				// 'Authorization'		=>	'Bearer EOJ2S-Z6OoN_le_KS1d75wsZ6y0SFdVsY9183IvxFyZp','EClusMEUk8e9ihI7ZdVLF5cZ6y0SFdVsY9183IvxFyZp',
				// 'grant_type'		=>	'client_credentials'
			],

			'body'	=>	[
				'grant_type'	=>	'client_credentials'
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

		$string ='';
		$response = $client->send($request);

		$body = $response->getBody();
		
		while (!$body->eof()) {
    		$string .= $body->read(1024);
		}
		var_export($string);
		// echo $response->getBody();
		// $response_arr = json_decode($response->getBody()->getContents(), true);

		// $this->assertNotEmpty($response_arr['access_token']);
		// var_dump($response_arr['access_token']);
	}

	/**
	 * 取得 paypal access token
	 *
	 * @param GuzzleHttp/Client
	 */ 
	public function getAccessToken($client)
	{
		$request = $client->createRequest('POST', 'https://api.sandbox.paypal.com/v1/oauth2/token', [

			'auth'	=>	['ASExehBlunSTszcSEhc__w7P81AFACksp2WaMZ1u0LlB6TCpDF6j8whNZLed', 'EOvU8hB8XRJzfdwDYzJ6m-nZnWpmXgpQktqYIEfbvFi4VuPW8JXylxMKF7fU'],
			'headers'	=>	[
				'Accept'			=>	'application/json', 
				'Accept-Language'	=>	'en_US'
			],

			'body'	=>	[
				'grant_type'	=>	'client_credentials'
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

		$response_arr = json_decode($response->getBody()->getContents(), true);

		return $response_arr['access_token'];
		// var_dump($response_arr['access_token'])
		// var_dump($response_arr['access_token']);
	}


	/**
	 * 測試呼叫 paypal credit card token api
	 * 
	 * @dataProvider guzzleClientProvider
	 */
	public function testPaypalStoreAndGetCreditCardTokenApi($client)
	{

		$_accessToken = $this->getAccessToken($client);

		// var_dump($_accessToken);

		$request = $client->createRequest('POST', 'https://api.sandbox.paypal.com/v1/vault/credit-card', [
			// 'auth' =>  ['ASExehBlunSTszcSEhc__w7P81AFACksp2WaMZ1u0LlB6TCpDF6j8whNZLed', 'EOvU8hB8XRJzfdwDYzJ6m-nZnWpmXgpQktqYIEfbvFi4VuPW8JXylxMKF7fU'],
			'headers'	=>	[
				'Content-Type'	=>	'application/json',
				'Authorization'	=>	"Bearer {$_accessToken}",
			],

			'json'	=>	[
				'payer_id'		=>	'sample1234', 
				'type'			=>	'visa', 
				'number'		=>	'4390755230449356', 
				'expire_month'	=>	'05', 
				'expire_year'	=>	'2018',
				'first_name'	=>	'chiheng', 
				'last_name'		=>	'Huang'

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

		$response_arr =json_decode( $response->getBody()->getContents(), true );

		// var_dump($response_arr);
		// die;
		$_creditCardId = $response_arr['id'];
		// var_dump($response_arr); 

		// 成功儲存 credit card 資訊
		$this->assertEquals('ok', $response_arr['state']);
		
		// 嘗試使用 accessToken 取得 credit card info
		$request = $client->createRequest('GET', "https://api.sandbox.paypal.com/v1/vault/credit-card/{$_creditCardId}", [
			'headers'	=>	[
				'Content-Type'	=>	'application/json',
				'Authorization'	=>	"Bearer {$_accessToken}"
			],

			'stream'	=>	 true
		]);

		$response = $client->send($request);

		$response_arr = json_decode($response->getBody()->getContents(), true);

		$this->assertNotEmpty($response_arr['number']);
	}
}