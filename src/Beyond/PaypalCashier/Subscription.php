<?php namespace Beyond\PaypalCashier;

use GuzzleHttp\Client;
use GuzzleHttp\Message\Request;
use Beyond\Core\BaseEloquentModel as BaseModel;

/*
| Subscription
--------------------------------------
| 註記：
| 	- paypal 中的 recurring paymet 就是 profile
| 	- 需要用到 guzzle 跟 paypal 做溝通
| 	- 應該要把有關 request 跟 config 的部分抽出來
| 	- 計算 init payment
| 使用說明：
| 1. $subscription->createSubscription($params) ->	需要 profileId 在 params 中
| 2. $subscription->cancelSubscription();	->	不需要 profileId
| 3. $subscription->getSubscriptionInfo();	->	不需要 profileId
| 4. $subscription->saveSubscriptionInfo();	->	不需要 profileId
*/
// class Subscription extends BaseModel
class Subscription extends BaseModel
{

	/**
	 * 表單名稱
	 *
	 * @var string
	 */ 
	protected $table = 'paypal_subscription';

	/**
	 * 可填入的欄位
	 *
	 * @todo 修改
	 * @var array
	 */ 
	protected $fillable = array(
		'title',
		'name', 
		'slug',
		'desc',
		'PROFILEID', 
		'PROFILESTATUS', // 存 paypal 回來的 response
		'profileStatus', // 為了要更新 db 的 profileStatus...
		'TIMESTAMP',
		'CORRELATIONID',
		'SUBSCRIPTIONID', // 一次性付款的 id
		'ACK', 
		'VERSION',
		'BUILD'
	);

	protected $guarded = array();

	/**
	 * Instance of GuzzleHttp/Client
	 *
	 * @var GuzzleHTTP/Client
	 */ 
	protected $client;

	/**
	 * Instance of GuzzleHTTP/Message/Request
	 *
	 * @var GuzzleGTTP/Message/Request
	 */
	 protected $request; 

	/**
	 * paypal 的 api url
	 *
	 * @todo 可能要放到 config 中
	 * @var string
	 */ 
	protected static $apiUrl = 'https://api-3t.sandbox.paypal.com/nvp';

	/**
	 * 跟 api 溝通總是要有 credential 吧
	 * 1. username
	 * 2. password
	 * 3. signature
	 *
	 * @todo 一定要放到 config 中
	 * @var array
	 */ 
	protected static $credentials = [
		'USER'		=>	'huangc770216_api1.163.com',
		'PWD'	=>	'1368327303',
		'SIGNATURE'	=>	'A3YwMG26ZPCah7erlKgLaXRPQxVwAOGprmfUvuvaQvmzZUcrjTfef5n7'
	];

	/**
	 * paypal 提供對 recurrent payment profile 的操作
	 *
	 * @todo 抽到別的 class 中
	 * @var array 
	 */
	 protected static $services = [
		'create'	=>	'CreateRecurringPaymentsProfile',
		'update'	=>	'UpdateRecurringPaymentsProfile',
		'status'	=>	'ManageRecurringPaymentsProfileStatus',
		'get'		=>	'GetRecurringPaymentsProfileDetails'
	]; 

	/**
	 * profile 狀態
	 *
	 * @param $profileStatus
	 */
	 protected static $profileStatus = array(
	 	'cancel'		=>	'CancelledProfile',		
	 	'suspend'		=>	'SuspendedProfile',			
	 	'reactivate'	=>	'ActiveProfile',
	 	'pending'		=>	'PendingProfile',
	 	'expired'		=>	'ExpiredProfile',
	 ); 

	/**
	 * paypal api 版本，modify profile 實不需要使用到，但是還是 specify 一下會好一點; 
	 *
	 * @todo 抽到別的 class 中
	 * @var string 	
	 */ 
	protected $apiVersion = '115';
		
	/**
	 * 初始化 Subscription 跟 Client
	 *
	 * @todo 有可能會從 service provider 注入 $client 實例	
	 * @todo 有可能會從 service provider 注入 $request 實例
	 */ 
	public function __construct()
	{
		$this->client = new Client;
	}

	/**
	 * 提供新的 Subscription 實例
	 *
	 * @return $this
	 */ 
	public static function newSubscription()
	{
		return new static;
	}

	/**
	 * 跟 Beyond\Module\Cashier\PaypalCashier\Customer 是 one-to-one
	 *
	 *
	 */ 
	public function customer()
	{
		return $this->belongsTo('Beyond\Module\Cashier\PaypalCashier\Customer', 'id', 'customer_id');
	}

	/**
	 * 跟 Beyond\Module\PaypalCashier\Cashier\Plan
	 *
	 * @return 
	 */ 
	public function plan()
	{
		return $this->hasOne('Beyond\Module\Cashier\PaypalCashier\Plan', 'subscription_id', 'id');
	}

	/**
	 * 初始化 guzzle request 物件
	 *
	 * @param string $action either "create" or "update"
	 * @param array $param || string $param 
	 */ 
	protected function initRequest($action, $param = null)
	{

		// 1. 取得一個 guzzle request 物件
		// 2. setup 這個 request 物件 (加上設定)
		// 3. 將 $param 加入到這個 request 中
		$this->makeNewRequest();

		$keys = $this->prepareKeys($action);

		$this->applyOptions($keys, $param);
	}

	/**
	 * 實例化一個新的 request 物件
	 *
	 * @todo 這個 config 應該要抽出
	 */ 
	protected function makeNewRequest()
	{	
		$this->request = $this->client->createRequest('POST', Subscription::$apiUrl, [
			'config'	=>	[
				'stream'	=> true,
				'curl'		=>	[
						CURLOPT_SSL_VERIFYPEER	=> 0,
		            	CURLOPT_RETURNTRANSFER	=> 1,
		            	CURLOPT_VERBOSE			=> 1	
	            ]
			]
		]);
	}

	/**
	 * 準備跟 paypal api 溝通的必要訊息
	 *
	 * 1. method
	 * 2. api version
	 * 3. credentials
	 * @return array
	 */ 
	protected function prepareKeys($action)
	{
		$method = $this->getApiMethod($action);

		$apiVersion = $this->getApiVersion();

		$_needs = [
			'METHOD'	=>	$method,
			'VERSION'	=>	$apiVersion
		];

		// 應該要因應不同的 action 生成不同的 need，
		// 1. get
		// 2. cancel
		// 3. update 
		// 外部不用帶 profileid 進來

		if($action !== 'create') 
		{
			$profileId = $this->loadProfileId();
		}
		
		$_needs =  (isset($profileId) && !is_null($profileId)) ? array_merge($_needs, ['PROFILEID'	=>	$profileId] ) : $_needs;

		$config = array_merge(Subscription::$credentials, $_needs);

		return $config;
	}

	/**
	 * 讀取 profile id
	 *
	 * @return string 
	 */ 
	protected function loadProfileId()
	{
		return $this->reload()->profileId;
	}

	/**
	 * 將必要的資訊填入到 request 物件中
	 *
	 * @param array $config
	 * @param array $param
	 */ 
	public function applyOptions($config, $param = null)
	{
		// 1. 要是 $param 為陣列代表外部要執行 create 或 save 
		// 2. 要是 $param 為字串代表外部要執行 cancel，suspend 或 reactivate
		if(is_string($param) && $param !== '') $param = $this->generateProfileStatus($param);

		$_fields = (!is_null($param)) ? array_merge($config, $param) : $config;
		
		$this->request->getBody()->replaceFields($_fields);
	}

	/**
	 * 產生出 profile 狀態的陣列
	 *
	 * @param string $param
	 * @return array $_statusArr | NULL
	 */ 
	protected function generateProfileStatus($param)
	{
		if(array_key_exists($param, Subscription::$profileStatus))
		{
			$_statusArr = ['ACTION'	=>	$param];

			return $_statusArr;
		}
		return NULL;
	}

	/**
	 * 取得 paypal 相關的 api 方法
	 * @todo 新的 Exception
	 * @param string $action
	 */ 
	protected function getApiMethod($action)
	{
		if(array_key_exists($action, Subscription::$services))
		{
			return Subscription::$services[$action];
		}
		else
		{
			throw new \Exeception('specified api not provided');
		}
	}

	/**
	 * 取得 ApiVersion
	 *
	 * @return string
	 */ 
	protected function getApiVersion()
	{
		return $this->apiVersion;
	}

	/**
	 * 取得 request
	 *
	 * @return GuzzleHttp/Message/Request;
	 */ 
	public function getRequest()
	{
		return $this->request;
	}

	/**
	 * 負責解析 response 成陣列，並回傳結果
	 *
	 * @todo 更名
	 * @return array $responseArr
	 */ 
	protected function handleResponse($responseStr)
	{
		$responseArr = $this->parseResponse($responseStr);

		if(array_key_exists('ACK', $responseArr))
		{
			
			// 判斷是否成功
			if( strripos($responseArr['ACK'], 'Success') > -1)
			{
				return $responseArr;
			}
			else
			{
				$this->collectErrorMessages($responseArr);
			}
		}
	}

	/**
	 * 建立新的 paypal recurring payment profile
	 *
	 * @todo 新的 Exception
	 * @param array  建立 profile 必要的資訊
	 * @return array $responseArr
	 */ 
	public function createSubscription(array $param)
	{
		// 初始化 request
		$this->initRequest('create', $param);

		// 傳送 response
		$response = $this->sendRequest();

		// 解析 response
		$parsedResponse = $this->handleResponse($response);

		// var_dump($parsedResponse);

		// 初始化此 subscription
		if(!$this->createProfile($parsedResponse))
		{
			throw new \Exception('fail saving subscription info to database');
		}
		else
		{
			return $this->reload();
		}
	}

	/**
	 * 加入 title 以提供 BaseEloquentModel 生成 slug 跟 name
	 *
	 * @param array $parsedResponse 從 paypal 回傳的 response，其 index 為 uppercase
	 * @return array $_fields;
	 */ 
	protected function createProfile($parsedResponse)
	{
		$_fields = array_merge( $parsedResponse, ['title'	=>	$parsedResponse['PROFILEID']]);

		$this->fill($_fields);

		return $this->save() || false;
	}

	/**
	 * 修改 paypal recurring payment profile
	 *
	 * @todo 檢查 profile id 是否存在，如果不存在就建立新的 profile id
	 * @param array $param
	 */ 
	public function saveSubscription(array $param)
	{
		$this->initRequest('update', $param);

		$response = $this->sendRequest();

		$parsedResponse = $this->handleResponse($response);

		return $this->reload();
	}

	/**
	 * 增加此 subscription cycle
	 *
	 * @param integer $increment
	 */ 
	public function increaseCycle($increment)
	{
		if(is_int($increment))
		{	
			$this->saveSubscription(['ADDITIONALBILLINGCYCLES'	=>	$increment]);
		}
		else
		{
			throw new \Exception('invalid increment variable type');
		}
	}

	/**
	 * 減少此 subscription cycle
	 *
	 * @param integer $decrement
	 */ 
	public function decreaseCycle($decrement)
	{
		if(is_int($increment))
		{	
			$this->saveSubscription(['ADDITIONALBILLINGCYCLES'	=>	$increment]);
		}
		else
		{
			throw new \Exception('invalid increment variable type');
		}
	}

	/**
	 * 更新此 subscription 付款的信用卡
	 *
	 * @var array $cardInfo
	 */ 
	public function updateCard($cardInfo)
	{
		if(is_array($cardInfo))
		{
			$this->saveSubscription($cardInfo);
		}
		else
		{
			throw new \Exception('invalid credit card info type');
		}
	}

	/**
	 * 取消 paypal recurring payment profile
	 *
	 * @param array $param
	 */ 
	public function cancel()
	{
		$this->initRequest('status', 'cancel');

		$response = $this->sendRequest();

		$parsedResponse = $this->handleResponse($response);

		$this->updateProfileStatus('cancel');

		return $this->reload();
	}

	/**
	 * 暫停 paypal recurring payment profile
	 *
	 * @return $this
	 */ 
	public function suspend()
	{
		$this->initRequest('status', 'suspend');

		$response = $this->sendRequest();

		$parsedResponse = $this->handleResponse($response);

		$this->updateProfileStatus('suspend');

		return $this->reload();
	}

	/**
	 * 重新啟用 profile，需要將 profileStatus 欄位設定為 activeProfile
	 *
	 * @return $this
	 */ 
	public function reactivate()
	{
		$this->initRequest('status', 'reactivate');

		$response = $this->sendRequest();

		$parsedResponse = $this->handleResponse($response);

		$this->updateProfileStatus('reactivate');

		return $this->reload();
	}

	/**
	 * 更新 profile status 
	 * 1. cancel
	 * 2. suspend
	 * 3. reactive
	 *
	 * @param string $status
	 */ 
	protected function updateProfileStatus($status)
	{
		$_status = Subscription::$profileStatus[$status];

		$this->update(['profileStatus'	=>	$_status]);
	}

	/**
	 * 取得 profile 資訊
	 *
	 * @param string $profileId
	 * @return array
	 */ 
	public function getSubscriptionInfo()
	{
		
		$this->initRequest('get');

		$response = $this->sendRequest();

		$parsedResponse = $this->handleResponse($response);

		return $parsedResponse;
	}


	/**
	 * 蒐集錯誤資訊
	 *
	 * Error:
	 * 1. L_ERRORCODE0
	 * 2. L_SHORTMESSAGE0
	 * 3. L_LONGMESSAGE0
	 * 4. L_SEVERITYCODE0
	 *
	 * @param array $responseArr
	 */ 
	protected function collectErrorMessages($responseArr)
	{
		$_errMsg = sprintf("Paypal error code %s, %s", $responseArr['L_ERRORCODE0'], $responseArr['L_LONGMESSAGE0']);

		throw new \Exception($_errMsg);
	}

	/**
	 * 發送 request 到 paypal api
	 *
	 * @return string;
	 */ 
	protected function sendRequest()
	{	
		$response = $this->client->send($this->getRequest());

		return $response->getBody()->getContents();
	}

	/**
	 * 將 response 解析成陣列
	 *
	 * @param string $response
	 * @return array 
	 * @throws \Exception 
	 */ 
	protected function parseResponse($responseStr)
	{	
		if($responseStr !== '')
		{
			$_str = urldecode($responseStr);

			$_arr = explode('&', $_str);

			$responseArr = [];

			foreach($_arr as $value)
			{

				$_temp = explode('=', $value);

				$responseArr[$_temp[0]] = $_temp[1];
			}

			return $responseArr;
		}
		else
		{
			throw new \Exception('empty response string');
		}
	}
}