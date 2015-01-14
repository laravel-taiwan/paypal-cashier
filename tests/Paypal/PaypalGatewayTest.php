<?php 

use \Mockery as m;
use Carbon\Carbon;
use Beyond\PaypalCashier\Customer;
use Beyond\PaypalCashier\PlanInterface;
use Beyond\PaypalCashier\Plan;
use Beyond\PaypalCashier\BillableTrait;
use Beyond\PaypalCashier\BillableInterface;
use Beyond\PaypalCashier\Subscription;
use Beyond\PaypalCashier\Account;
use Beyond\PaypalCashier\PaypalGateway;
use Beyond\Core\BaseEloquentModel;

class PaypalGatewayTest extends TestCase
{
	public function setUp()
	{
		parent::setUp();

		StubUser::flushEventListeners();
		StubUser::boot();

		Customer::flushEventListeners();
		Customer::boot();

		Account::flushEventListeners();
		Account::boot();

		Subscription::flushEventListeners();
		Subscription::boot();

		$this->seed();
	}

	public function tearDown()
	{
		m::close();
		$this->setUp();
	}

	/**
	 * 測試建立新的 customer，順便測試 billable 跟 user 之間的
	 *
	 */ 
	public function testGatewayCreateNewCustomer()
	{
		
		$stubAccount = Account::create([
			'title'	=>	'sampleAcc'
		]);

		$stubPlan = m::mock('StubPlan');

		$paypalGateway = new PaypalGateway($stubAccount, $stubPlan);

		$customer = $paypalGateway->createPaypalCustomer();

		$this->assertFalse($customer->account()->get()->isEmpty());

	}

	/**
	 * 測試不使用 uid 來取得 customer
	 */ 
	public function testGatewayGetPaypalCustomerWithNoUidProvided()
	{
		
		$stubAccount = m::mock('Beyond\Module\Cashier\PaypalCashier\Account[getPaypalCustomerId]');

		$gateway = new Beyond\Module\Cashier\PaypalCashier\PaypalGateway($stubAccount, 'daily');

		$stubAccount->shouldReceive('getPaypalCustomerId')->once()->andReturn('1234');

		$gateway->getPaypalCustomer();

	}

	/**
	 * 提供測試 credit card 資訊
	 */ 
	public function creditCardProvider()
	{
		$cardInfo = array(
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
	 * 測試創建 paypal account
	 *
	 * @dataProvider creditCardProvider
	 */ 
	public function testPaypalGatewayCreateSubscription($creditCardInfo)
	{
		$plan = Plan::find(1);

		$account = Account::find(1);

		$gateway = new PaypalGateway($account, 'monthly');

		$gateway->create($creditCardInfo);

		$info = $account->customer->with('subscription.plan')->get();

		$info_arr = $info->toArray();

		$this->assertEquals('monthly', $info_arr[0]['subscription'][0]['plan']['title']);
	
	}

	/**
	 * 建立測試 subscription
	 */ 
	public function createPaypalGateway()
	{	
		$account = new Account;

		$account->save();

		$gateway = new PaypalGateway($account, 'monthly');

		return $gateway;
	}	

	/**
	 * 測試取消 subscription，customer 的 cancel subscription 應該會被呼叫一次
	 *
	 * @dataProvider creditCardProvider
	 */ 
	public function testPaypalGatewayCancelSubscription($cardInfo)
	{

		$account = m::mock('Beyond\Module\Cashier\PaypalCashier\Account[getPaypalSubscription, setSubscriptionEndDate, deactivatePaypal, saveBillableInstance]');

		$account
			->shouldReceive('getPaypalSubscription')
			->once()
			->andReturn('1234');

		$account
			->shouldReceive('deactivatePaypal')
			->once()
			->andReturn($account);		

		$account
			->shouldReceive('saveBillableInstance')
			->once()
			->andReturn(true);


		$gateway = m::mock('Beyond\Module\Cashier\PaypalCashier\PaypalGateway[getPaypalCustomer, getSubscriptionEndTimeStamp]', array($account, 'monthly'));
		$gateway->shouldAllowMockingProtectedMethods();

		$gateway
			->shouldReceive('getPaypalCustomer')
			->once()
			->andReturn($customer = m::mock('Beyond\Module\Cashier\PaypalCashier\Customer'));

		// 為什麼會被叫兩次？
		$gateway
			->shouldReceive('getSubscriptionEndTimeStamp')
			->once()
			->andReturn('timestamp');

		// 確認有調用到這個方法
		$customer
			->shouldReceive('cancelSubscription')
			->once()
			->with(m::type('string'));

		$account
			->shouldReceive('setSubscriptionEndDate')
			->once()
			->with(m::type('string'))
			->andReturn(true);

		$gateway->cancel();
	}

	/**
	 * 測試 mock stdClass 
	 */ 
	public function testMockStd()
	{
		$customer = m::mock('stdClass');

		$customer->subscription = (object) ['plan'	=>	(object) ['id'	=>	1]];

		var_dump($customer->subscription->plan->id); 
	}

	/**
	 * 測試 paypal gateway 暫停 subscription
	 *
	 * @todo 用 mockery 來測
	 * @dataProvider creditCardProvider
	 */ 
	public function testPaypalGatewaySuspendSubscription($cardInfo)
	{
		// $_ns = 'Beyond\Module\Cashier\PaypalCashier\\';

		// $account = m::mock($_ns.'Account[setSubscriptionEndDate, setPaypalIsActive, saveBillableInstance]');

		// $account->shouldReceive('setPaypalIsActive')->once()->with(false)->andReturn($account);

		// $account->shouldReceive('saveBillableInstance')->once();

		// $account->shouldReceive('setSubscriptionEndDate')->once()->with(m::type('string'));

		// $gateway = m::mock($_ns.'PaypalGateway[getPaypalCustomer, getSubscriptionEndTimeStamp]', array($account, 'monthly'));

		// $gateway->shouldAllowMockingProtectedMethods();

		// $gateway->shouldReceive('getPaypalCustomer')->once()->andReturn($customer = m::mock($_ns.'Customer'));

		// $gateway->shouldReceive('getSubscriptionEndTimeStamp')->once()->with($customer)->andReturn(m::type('string'));		

		// $customer->shouldReceive('suspendSubscription')->once()->with('1234');

		// $gateway->suspend();
		$gateway = $this->createPaypalGateway();

		$gateway->create($cardInfo);

		$account = Account::find(3);

		$account->subscription()->suspend();

		$this->assertFalse($account->paypalIsActive());

		$this->assertInstanceOf('Carbon\Carbon', $account->subscription_ends_at);
	}

	/**
	 * 測試 swap plan
	 *
	 * @dataProvider creditCardProvider
	 */ 
	public function testGatewaySwapPlan($cardInfo)
	{
		
		$account = Account::find(1);

		$gateway = new PaypalGateway($account, 'monthly');

		$gateway->create($cardInfo);

		// swap to a new subscription
		$account->reload()->subscription('yearly')->swap($cardInfo);

		// account 正在關注的 plan 應該為 yearly
		$this->assertEquals('yearly' ,$account->reload()->plan);

		$_subscriptions = DB::table('paypal_subscription')->get();

		// 第一個 subscription 狀態應該為 CancelledProfile
		$_sub1 = $_subscriptions[0];
		$this->assertEquals('CancelledProfile', $_sub1->profileStatus);

		// 第二 subscription 狀態應該為 ActiveProfile
		$_sub2 = $_subscriptions[1];
		$this->assertEquals('ActiveProfile', $_sub2->profileStatus);
	}

	/**
	 * 測試 reactivate 一個 cancelled subscription 。 預期是要沒辦法 reactivate
	 * 
	 * @dataProvider creditCardProvider
	 * @expectedException Exception
	 */ 
	public function testGatewayResumeACancelledSubscription($cardInfo)
	{
		$account = Account::find(1);

		$gateway = new PaypalGateway($account, 'monthly');

		$gateway->create($cardInfo);

		$account->subscription()->cancel();

		$account->reload()->subscription()->resume();	

		// var_dump(DB::table('account')->get());
	}

	/**
	 * test reactivate suspended subscription profile
	 *
	 * @dataProvider creditCardProvider
	 */ 
	public function testGatewayResumeASuspendedSubscription($cardInfo)
	{
		$account = Account::find(1);

		$gateway = new PaypalGateway($account, 'monthly');

		$gateway->create($cardInfo);

		$account->subscription('monthly')->suspend();

		$account->reload()->subscription('monthly')->resume();	

		$_subscription = DB::table('paypal_subscription')->get()[0];

		$this->assertEquals('ActiveProfile', $_subscription->profileStatus);
	}

	/**
	 * 測試更新付款信用卡
	 *
	 * @dataProvider creditCardProvider
	 */ 
	public function testGatewayUpdateCreditCard($cardInfo)
	{
		$account = Account::find(1);

		$gateway = new PaypalGateway($account, 'monthly');

		$gateway->create($cardInfo);

		$gateway->updateCard(array(
			'CREDITCARDTYPE'	=>	'visa',
			'ACCT'		=>	'4479091807941789',
			'EXPDATE'	=>	'042018', 
			'FIRSTNAME'	=>	'some ppl',
			'LASTNAME'	=>	'yay', 
			'STREE'		=>	'some street',
			'CITY'		=>	'taipei', 
			'STATE'		=>	'taipei', 
			'COUNTRYCODE'	=>	'886', 
			'ZIP'		=>	'10466', 
		));

		$subscription = $gateway->getPaypalSubscription();

		$subscriptionInfo = $subscription->getSubscriptionInfo();

		$this->assertEquals('some ppl', $subscriptionInfo['FIRSTNAME']); 

		$this->assertEquals('yay', $subscriptionInfo['LASTNAME']); 
	}
}

class StubUser extends Illuminate\Database\Eloquent\Model
{
	protected $table = 'users';

	/**
	 * user 跟 account 為 one-to-many
	 */ 
	public function account()
	{
		return $this->belongsToMany('Beyond\Module\Cashier\PaypalCashier\Account', 'user_accounts', 'account_id', 'user_id');
	}
}



class StubPlan implements PlanInterface{}

class StubAccount implements BillableInterface
{
	use Beyond\PaypalCashier\BillableTrait;
}

class StubCustomer
{
	public function isEmpty()
	{
		return null;
	}
}

class StubHasOne
{
	public function get()
	{
		return 'get';
	}
}