<?php namespace Beyond\PaypalCashier;

/*
| WebHook Controller
--------------------------------------
| 1. 接收 paypal ipn message
| 2. 可被其他 controller 繼承
| 3. pending 	- subscription is under review，會附帶 pending_reason 欄位
| 5. reversed	- 交易失敗或 payment 被拒絕。
| 6. completed	- 交易成功
---------------------------------------
| handlers:
|
| 1. handlePending	 -	用來處理 pending transaction
| 2. handleReversed	 - 	用來處理 reversed transaction
| 3. handleCompleted - 	用來處理 completed transaction
---------------------------------------
| todo:
| 1. verify ipn message. Ipn message format: https://developer.paypal.com/docs/classic/ipn/integration-guide/IPNandPDTVariables/#id091EB080EYK
|
*/
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Routing\Controller;

class WebhookController extends Controller
{
	/**
	 * Instance of Illuminate\Http\Request
	 *
	 * @var Illuminate\Http\Request
	 */ 
	protected $request;

	protected $logFilename = '/logs/ipn_logs.log';
	// protected $logFilename = '/ipn_logs.log';

	public function __construct()
	{
		$this->request = \Request::instance();
	}

	/**
	 * 接收從 paypal ipn server 回傳的訊息
	 * paypal 官方建議使用 txn_type 的欄位來決定下一步的動作
	 * 1. recurring_payment 		: recurring payment 請款已經收到
	 * 		- 檢查 $payment['payment_status'] 欄位
	 * 		- 
	 * 2. recurring_payment_expired : recurring payment 過期
	 * 3. recurring_payment_failed  : recurring payment 失敗，應該 suspend
	 * 		- 嘗試請款失敗
	 * 4. recurring_payment_created : recurring payment 已建立
	 * 		-  檢查 $payload['init_payment_status']
	 * 		 	 
	 * 
	 * @return void
	 */ 
	public function handleWebhook()
	{	

		$this->logResult();
		// 取得 array 格式的 ipn message
		$payload = $this->getPayload('array');

		// @todo 轉換成 json 格式，使用 DB::table 來儲存此 ipn message
		$this->storeMessage($payload);

		if(array_key_exists('payment_status', $payload))
		{
			// 依照狀況不同調用不同的 handler
			// $method = 'handle'.studly_case($payload['txn_type']);
			// 1. handleCompleted
			// 2. handleReversed
			// 3. handlePending

			$method = 'handle'.$payload['payment_status'];

			if(method_exists($this, $method))
			{
				$this->{$method}($payload);
			}
			else
			{
				$this->missingMethod();
			}
		}
		
	} 

	/**
	 * Payment_status 為 Reversed，應該依照 profile id 取得對應的 subscription 並 suspend 此 subscription
	 *
	 * @param array $payload
	 * @return void
	 */ 
	public function handleReversed($payload)
	{
		$billable = $this->getBillable($payload['recurring_payment_id']);
		$billable->subscription()->suspend();
	}	

	/**
	 * 將 ipn message 轉換成 array 格式
	 *
	 * @param string $type 指定轉換的格是 json or array
	 * @return array  | json
	 */ 
	protected function getPayload($type)
	{
		// 取得 stream content
		$content = $this->request->getContent();

		$_arr = $this->parseMessageToArray($content);

		if($type == 'array')return $_arr;
		
		if($type == 'json')return json_encode($_arr);
	}

	/**
	 * 儲存 ipn message 到 database 中
	 *
	 * @param array $payload
	 * @return void
	 */ 
	public function storeMessage($payload)
	{
		$_json = json_encode($payload);

		if(array_key_exists('recurring_payment_id', $payload))
		{
			\DB::table('ipn_logs')->insert(array(

				'profile_id'	=>	$payload['recurring_payment_id'],

				'message'		=>  $_json
			));
		}
	}

	/**
	 * 將 message 解析成 array 形式
	 *
	 * @param string $content
	 * @return array $responseArr
	 */ 
	public function parseMessageToArray($content)
	{
		if($content !== '')
		{
			$_str = urldecode($content);

			$_arr = explode('&', $_str);

			$responseArr = [];

			foreach($_arr as $key=>$value)
			{
				$_temp = explode('=', $value);

				$_trimmedKey = trim($_temp[0]);

				$responseArr[$_trimmedKey] = $_temp[1];
			}
		}

		return $responseArr;
	}

	/**
	 * 取得 BillableInterface instance
	 *
	 * @param string $profileId
	 * @return Beyond\Module\Cashier\PaypalCashier\Account
	 */ 
	public function getBillable($profileId)
	{
		return \App::make('Beyond\Module\Cashier\PaypalCashier\BillableRepositoryInterface')->find($profileId);
	}

	/**
	 * Handle calls to missing methods on the controller
	 *
	 * @return Symfony\Component\HttpFoundation\Response
	 */ 
	public function missingMethod($parameters = array())
	{
		return new Response;
	}

	protected function logResult()
	{
		$content = var_export($this->request->getContent()."\r\n", true);

		$this->writeResult($content);
	}

	/**
	 * 測試用，將 result 寫入到 IpnMessageLog 檔案中
	 *
	 * @param string
	 */ 
	protected function writeResult($content)
	{
		$path = storage_path().$this->logFilename;

		// $path = public_path().$this->logFilename;

		try {

			// 檢查 ipn_logs.log 檔案是否存在
			// 若不存在則建立一個新的 logs file
			if(!file_exists($path))
			{
				@touch($path);			
			}

			$filesystem = \App::make('files');
			$filesystem->prepend($path, $content);

		} catch( \Exception $error ) {

			\Log::error($error);

		}
		
	}
}