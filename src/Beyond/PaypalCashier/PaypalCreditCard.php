<?php namespace Beyond\PaypalCashier;

use Beyond\PaypalCashier\CreditCardInterface;
use Beyond\Core\BaseEloquentModel;
use GuzzleHttp\Client;

/*
     .o8                                                         .o8
    "888                                                        "888
     888oooo.   .ooooo.  oooo    ooo  .ooooo.  ooo. .oo.    .oooo888
     d88' `88b d88' `88b  `88.  .8'  d88' `88b `888P"Y88b  d88' `888
     888   888 888ooo888   `88..8'   888   888  888   888  888   888
     888   888 888    .o    `888'    888   888  888   888  888   888
     `Y8bod8P' `Y8bod8P'     .8'     `Y8bod8P' o888o o888o `Y8bod88P" Inc.
                         .o..P'
                         `Y8P'
 */

/**
 * @author Bryan Huang
 */ 

/*
| paypal credit card
--------------------------------------
| - PaypalCreditCard 繼承 CreditCard 而 CreditCard 則繼承 BaseEloquentModel
| - paypalCreditCard 中的 save 方法會 override 
| $paypalCreditCard = new PaypalCreditCard 
|
| 
| $paypalCreditCard->save()
|
*/

/**
 * @todo 跟 user 作關聯
 * @todo 可切換 sandbox url
 */ 
class PaypalCreditCard extends CreditCard 
{


	/**
	 * 允許集體存取的欄位
	 *
	 * @var array
	 */ 
	protected $fillable = array(
		'payer_id',
		'number',
		'type',
		'expire_month', 
		'expire_year', 
		'first_name', 
		'last_name'
	);

	/**
	 * paypal 的 api url
	 *
	 * @todo 要放到 config 中
	 * @var string
	 */ 
	protected static $restApiBaseUrl = 'https://api.sandbox.paypal.com/v1';

	/**
	 *
	 * @todo 要放到 config 中
	 * @var array
	 */ 
	protected static $restApiCredentials = array(
		'client_id'	=>	'ASExehBlunSTszcSEhc__w7P81AFACksp2WaMZ1u0LlB6TCpDF6j8whNZLed',
		'secret'	=>	'EOvU8hB8XRJzfdwDYzJ6m-nZnWpmXgpQktqYIEfbvFi4VuPW8JXylxMKF7fU',
	);

	/**
	 * 提供的 paypal restful api 服務
	 *
	 * @todo 抽到 config 中實做
	 */ 
	protected static $restApiServices = array(
		'Oauth'			=>	'/oauth2/token',
		'CreditCard'	=>	'/vault/credit-card',
	);

	/**
	 * GuzzleGttp\Client 的實例 
	 *
	 * @var GuzzleHttp\Client
	 */ 
	protected $client;

	/**
	 * 初始化 guzzle client
	 */ 
	public function __construct()
	{
		$this->client = new Client;
	}

	/**
	 * 儲存信用卡資訊到 database 中
	 * 
	 * @override
	 */ 
	public function save(array $options = array())
	{
		// 取得 credit card token
		$accessToken = $this->getAccessToken();

		// 在儲存之前先跟 paypal 做溝通並拿到 credit card token
		$creditCardToken = $this->getCreditCardToken($accessToken);
		
		// 清空 attributes
		$this->setRawAttributes([]);

		$this->paypal_credit_card_token = $creditCardToken;

		// 儲存 credit card token 到 db
		parent::save($options);
	}

	/**
	 * 取得 credit card token
	 *
	 * @param string $accessToken
	 */ 
	protected function getCreditCardToken($accessToken)
	{

		// $accessToken = $this->getAccessToken();

		// 初始化取得 access token 的 request
		$tokenRequest = $this->initRestfulRequest('CreditCard', $accessToken);

		$creditCardResponse = $this->client->send($tokenRequest);

		$creditCardResponseArr = $this->parseResponse($creditCardResponse->getBody()->getContents());
	
		return $creditCardResponseArr['id'];
	}

	/**
	 * 取得 access token
	 *
	 * 1. 初始化 request
	 * 2. 發送 request
	 */ 
	protected function getAccessToken()
	{
		// 建立新的 request
		$request = $this->initRestfulRequest('Oauth', PaypalCreditCard::$restApiCredentials);

		$response = $this->client->send($request);

		$parsedResponse = $this->parseResponse($response->getBody()->getContents());

		// var_dump($parsedResponse);
		// die;
		return $parsedResponse['access_token']; 

	}

	/**
	 * 解析從 rest api 回傳的資料
	 *
	 * @param string $response
	 * @throws \Exception
	 */ 
	protected function parseResponse($response)
	{
		$_info = json_decode($response, true);

		if(json_last_error() === JSON_ERROR_NONE)
		{
			return $_info;
		}
		else
		{
			throw new \Exception('fail retrieving paypal rest api access token, response not json parsable');
		}
	}

	/**
	 * 初始化取得 access token 的 request 
	 *
	 * @todo 拉到別的 class 中做掉
	 * @param string $action
	 * @param array $options | string
	 * @return GuzzleHttp\Message\Request
	 */ 
	protected function initRestfulRequest($action, $options = null)
	{

		$_url = $this->getServiceUrl($action);

		$_needs = $this->{"get{$action}Needs"}($options);

		// var_dump(PaypalCreditCard::$restApiBaseUrl.$_url);
		
		$request = $this->client->createRequest('POST', PaypalCreditCard::$restApiBaseUrl.$_url, $_needs);
	
		return $request;
	}

	/**
	 * 每一種 paypal restful service 都有其對應的 uri。取出此 service uri
	 *
	 * @param string $action
	 * @return string
	 * @throws \Exception
	 */ 
	protected function getServiceUrl($action)
	{
		if(array_key_exists($action, PaypalCreditCard::$restApiServices))
		{
			return PaypalCreditCard::$restApiServices[$action];
		}
		else
		{
			throw new \Exception('specified not provided');
		}
	}

	/**
	 * 取得產生 guzzle oauth request 必須的資訊.
	 *
	 * @return array 
	 */ 
	protected function getOauthNeeds($credentials)
	{
		return [

			'auth' =>  [$credentials['client_id'], $credentials['secret']],
			
			'headers'	=>	[
				'Content-Type: application/json',
				'Accept: application/json',
				'Accept-Language: en_US',	
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
			'body'		=>[
				'grant_type'	=>	'client_credentials'
			],
			'stream'	=>	true
		];
	}

	/**
	 * 取得產生 paypal credit card api guzzle request 必須的資訊
	 *
	 * @param string $accessToken;
	 * @return array 
	 */ 
	protected function getCreditCardNeeds($accessToken)
	{

		$cardInfo = $this->getCardInfo();

		// var_dump($cardInfo);
		// die;

		if(empty($cardInfo)) throw new \Exception('card information can not be empty');

		$needs = [		
			'headers'	=>	[
				"Content-Type"	=>	"application/json",
				"Authorization"	=>	"Bearer {$accessToken}",
			],
			// 'headers'	=>	[
			// 	"Content-Type: application/json",
			// 	"Authorization: Bearer {$accessToken}",
			// ],

			'json'	=>	$cardInfo, 

			'config'	=>	[
				'curl'	=>	
				[
				 	CURLOPT_SSL_VERIFYPEER	=> 0,
					CURLOPT_RETURNTRANSFER	=> 1,
					CURLOPT_VERBOSE			=> 1	
				]
			],

			'stream'	=>	true
		];
	
		return $needs;
	}

	protected function getCardInfo()
	{
		return $this->getAttributes();
	}
}