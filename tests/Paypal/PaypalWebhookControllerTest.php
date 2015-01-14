<?php 

use \Mockery as m;
use Beyond\PaypalCashier\Account;
use Beyond\PaypalCashier\WebhookController;
use Illuminate\Filesystem\Filesystem;
use GuzzleHttp\Client;
use GuzzleHttp\Query;

class PaypalWebhookControllerTest extends TestCase
{
	public function setUp()
	{
		parent::setUp();

		$this->seed();
	}

	public function tearDown()
	{
		m::close();
		$this->setUp();
	}

	public function creditCardProvider()
	{
		$cardInfo = array(
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
		);

		return array(
			array($cardInfo)
		);
	}

	/**
	 * 
	 * 1. 建立 profile - ok
	 * 2. 查看 log 是否有寫入 - ok
	 * 3. 檢查 log 跟建立的 profile 是否相同 - ok
	 *
	 * @dataProvider creditCardProvider
	 */ 
	public function testGetIpnMessageAfterProfileCreated($cardInfo)
	{
		// var_export($cardInfo);
 		$account = new Account;

 		$account->subscription('daily')->create($cardInfo);

 		$subscription = $account->subscription()->getPaypalSubscription();

 		// var_dump($subscription);
 		// var_export($subscription);
 		var_dump($subscription->getSubscriptionInfo());
	}

	public function filesystemProvider()
	{
		$filesystem = new Filesystem;

		return array(
			array($filesystem)
		);
	}

	/**
	 * 解析 ipn message
	 *
	 * @dataProvider filesystemProvider
	 */ 
	public function testParseIpnMessage($filesystem)
	{
		
		$responseStr = $filesystem->get(__DIR__.'/IpnMsg/SampleIpnMsg.txt');

		$responseArr = $this->parseIpnMessage($responseStr);
		
		$this->assertTrue(is_array($responseArr));

		$this->assertNotEmpty($responseArr['recurring_payment_id']);
	}

	/**
	 * 測試使用 webhook controller 解析 ipn message
	 */ 
	public function testWebhookParseIpnMessage()
	{
		$_sampleMsg = $this->messageProvider('Completed');

		$webhook = new Beyond\Module\Cashier\PaypalCashier\WebhookController;
		// var_dump($_sampleMsg);

		// $webhook = m::mock('Beyond\Module\Cashier\PaypalCashier\WebhookController');

		// $webhook->shouldAllowMockingProtectedMethods();

		// $webhook
		// 	->shouldReceive('parseMessageToArray')
		// 	->once()
		// 	->with($_sampleMsg)
		// 	->andReturn('something');

		$arr = $webhook->parseMessageToArray($_sampleMsg);

		var_dump($arr);

	}

	/**
	 *
	 * @dataProvider filesystemProvider
	 */ 
	public function testVerifyIpnMessage($filesystem)
	{
		// 取得 ipn message
		$msg = $filesystem->get(__DIR__.'/IpnMsg/SampleIpnMsg.txt');

		// parse message into array
		$msgArr = $this->parseIpnMessage($msg);

		// var_dump($msgArr);

		$client = new Client;

		// composer a get guzzle request
		$request = $client->createRequest('GET', 'https://www.sandbox.paypal.com/cgi-bin/webscr', [
			'stream'	=>	true
		]);

		$query = $request->getQuery();

		// assign $msg to query object
		$this->assignFieldsToQueryObj($query, $msgArr);

		// var_dump($request->getUrl());
		// send
		$response = $client->send($request);

		$validity = $response->getBody()->getContents();

		$this->assertEquals('VERIFIED', $validity);

		// 分析 paypal 回傳的 response
	}

	/**
	 * 將 paypal ipn message 轉換成 array
	 *
	 * @param $content
	 */ 
	public function parseIpnMessage($content)
	{
		if($content !== '')
		{
			$_str = urldecode($content);

			$_arr = explode('&', $_str);

			$responseArr = [];

			foreach($_arr as $key=>$value)
			{
				// var_dump($key);
				$_temp = explode('=', $value);

				$_trimmedKey = trim($_temp[0]);
				// var_dump($_temp[0])

				$responseArr[$_trimmedKey] = $_temp[1];
			}
		}

		return $responseArr;
	}

	/**
	 * 將 ipn fields assign 到 GuzzleHttp\Query object 中
	 *
	 * @param GuzzleHttp\Query
	 * @param array msgArr
	 */ 
	public function assignFieldsToQueryObj($query, $msgArr)
	{
		
		$query['cmd'] = '_notify-validate';

		foreach($msgArr as $key => $value)
		{
			$query[trim($key)]	=	$value;
		}
	}

	/**
	 * 測試調用 handlePendingTransaction
	 */ 
	public function testCallPendingTransactionHandler()
	{	
		$filesystem = new Filesystem;

		// 調用測試用 ipn message, 其 payment status 為 reversed
		$sampleMsg = $filesystem->get(__DIR__.'/IpnMsg/SampleIpnMessagePending.txt');

		// 將此 sampleMsg 解析為 array
		$_msgArr = $this->parseIpnMessage($sampleMsg);

		if(array_key_exists('payment_status', $_msgArr))
		{
			$method = 'handle'.$_msgArr['payment_status'].'Transaction';

			if(method_exists($this, $method))
			{
				$_catched = $this->$method($_msgArr);
			}

			$this->assertEquals('Pending handled', $_catched);
		}
	}

	/**
	 * 測試調用 handleReversedTransaction
	 */ 
	public function testCallReversedTransactionHandler()
	{
		$filesystem = new Filesystem;

		// 調用測試用 ipn message, 其 payment status 為 reversed
		$sampleMsg = $filesystem->get(__DIR__.'/IpnMsg/SampleIpnMessageReversed.txt');

		// 將此 sampleMsg 解析為 array
		$_msgArr = $this->parseIpnMessage($sampleMsg);


		// 要是 payment status 這個鍵值存在
		if(array_key_exists('payment_stats', $_msgArr))
		{
			$method = 'handle'.$_msgArr['payment_status'].'Transaction';

			if(method_exists($this, $method))
			{
				$_catched = $this->$method($_msgArr);
			}

			$this->assertEquals('Reversed handled', $_catched);
		}

		
	}

	/**
	 * @todo sample message 需要改
	 */ 
	public function testCallCompletedTransactionHandler()
	{
		$filesystem = new Filesystem;

		// 調用測試用 ipn message, 其 payment status 為 reversed
		$sampleMsg = $filesystem->get(__DIR__.'/IpnMsg/SampleIpnMessageCompleted.txt');

		// 將此 sampleMsg 解析為 array
		$_msgArr = $this->parseIpnMessage($sampleMsg);

		// 要是 payment status 這個鍵值存在
		if(array_key_exists('payment_stats', $_msgArr))
		{
			$method = 'handle'.$_msgArr['payment_status'].'Transaction';

			if(method_exists($this, $method))
			{
				$_catched = $this->$method($_msgArr);
			}

			$this->assertEquals('Completed handled', $_catched);
		}


	}

	/**
	 * @param array $_msgArr
	 */ 
	public function handlePendingTransaction($_msgArr)
	{
		return $_msgArr['payment_status']."\040handled";
	}

	/**
	 * @param array $_msgArr
	 */ 
	public function handleReversedTransaction($_msgArr)
	{
		return $_msgArr['payment_status']."\040handled";
	}

	/**
	 * @param array $_msgArr
	 */ 
	public function handleCompletedTransaction($_msgArr)
	{
		return $_msgArr['Payment_status']."\040handled";
	}

	/**
	 * 提供測試用 ipn message
	 *
	 * @return string $sampleMsg
	 */ 
	public function messageProvider($status)
	{
		$filesystem = new Filesystem;

		$sampleMsg = $filesystem->get(__DIR__.'/IpnMsg/SampleIpnMessage'.$status.'.txt');

		return $sampleMsg;
	}

	/**
	 * 測試依照 paymet status 呼叫對應的 reversed handler，
	 */ 
	public function testWebHookControllerHandleReversedHook()
	{

		$fakeMsg = $this->messageProvider('Reversed');

		// var_dump($fakeMsg);

		$_msgArr = $this->parseIpnMessage($fakeMsg);

		// mock WebhookController
		$webhook = m::mock('StubController[getPayload, handleReversed]');

		$webhook->shouldAllowMockingProtectedMethods();

		$webhook->shouldReceive('getPayload')->once()->with('array')->andReturn($_msgArr);

		$webhook->shouldReceive('handleReversed')->once()->andReturn('reverse handled');

		$webhook->handleWebhook();
	}

	/**
	 * 測試依照 paymet status 呼叫對應的 pending handler，
	 */ 
	public function testWebHookControllerHandlePendingHook()
	{
		$fakeMsg = $this->messageProvider('Pending');

		// var_dump($fakeMsg);

		$_msgArr = $this->parseIpnMessage($fakeMsg);

		// mock WebhookController
		$webhook = m::mock('StubController[getPayload, handlePending]');

		$webhook->shouldAllowMockingProtectedMethods();

		$webhook->shouldReceive('getPayload')->once()->with(m::type('string'))->andReturn($_msgArr);

		$webhook->shouldReceive('handlePending')->once()->andReturn('pending handled');

		$webhook->handleWebhook();
	}

	/**
	 * 測試依照 paymet status 呼叫對應的 pending handler，
	 */ 
	public function testWebHookControllerHandleCompletedHook()
	{
		$fakeMsg = $this->messageProvider('Completed');

		// 因為 complete message 不是從 recurring payment 來的所以要合成 recurring_payment_id
		$_msgArr = array_merge( $this->parseIpnMessage($fakeMsg), ['recurring_payment_id'	=>	'1234'] );

		// mock WebhookController
		$webhook = m::mock('StubController[getPayload, handleCompleted]');

		$webhook->shouldAllowMockingProtectedMethods();

		$webhook->shouldReceive('getPayload')->once()->with(m::type('string'))->andReturn($_msgArr);

		$webhook->shouldReceive('handleCompleted')->once()->andReturn('completed handled');

		$webhook->handleWebhook();
	}

	/**
	 * 測試取得 billable interface instance
	 */ 
	public function testGetBillableInterfaceInstance()
	{
		$account = new Account;

		$account->paypal_subscription = '1234';

		$account->save();

		$webhook = new StubController;

		$billable = $webhook->getBillable('1234');

		$this->assertInstanceOf("Beyond\Module\Cashier\PaypalCashier\Account", $billable);
	}

	/**
	 * 測試 handle reversed，paypal gateway 中的 suspend 方法會被呼叫一次
	 */
	 public function testWebhookHandleReversed()
	 {
	 	$payload = [
	 		'recurring_payment_id'	=>	'1234'
	 	];

	 	$webhook = m::mock('Beyond\Module\Cashier\PaypalCashier\WebhookController[getBillable]');

	 	$webhook
	 		->shouldReceive('getBillable')
	 		->once()
	 		->with(m::type('string'))
	 		->andReturn($billable = m::mock('WebhookStubAccount'));

	 	$billable
	 		->shouldReceive('subscription')
	 		->once()
	 		->andReturn($gateway = m::mock('Beyond\Module\Cashier\PaypalCashier\PaypalGateway'));

	 	$gateway
	 		->shouldReceive('suspend')
	 		->once();

	 	$webhook->handleReversed($payload);
	 } 

	 /**
	  * 測試儲存 ipn message
	  */ 
	 public function testWebhookStoreIpnMessage()
	 {
	 	$_msg = $this->messageProvider('Reversed');

	 	$_msgArr = $this->parseIpnMessage($_msg);

	 	$webhook = new StubController;

	 	$webhook->storeMessage($_msgArr);

		$_record = DB::table('ipn_logs')->get();

		$this->assertFalse(empty($_record));
	}
}


class StubController extends WebhookController
{
	public function handleReversed($payload){}
	public function handlePending($payload){}
	public function handleCompleted($payload){}
}

class WebhookStubAccount
{
	use Beyond\PaypalCashier\BillableTrait;
}