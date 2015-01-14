<?php namespace Beyond\PaypalCashier;

use DateTime;
use Carbon\Carbon;
use Beyond\PaypalCashier\Customer;
use Beyond\PaypalCashier\PlanInterface;
use Beyond\PaypalCashier\BillableInterface;
use Beyond\Core\BaseEloquentModel;


/*
| Paypal Gateway
--------------------------------------
|
| $user->account()->subscription('plan')->cancel() : 取消特定關注
| $user->subscription('plan')->cancel() : 取消此 user 的所有關注
| $user->subscription('plan')->create($param) : 建立一個新的關注
| $user->subscription('plan')->resume() : reactivate 一個關注
| $user->subscription('plan1')->swap('plan2', 2) // 代議
| $user->subscription('plan')->increment($quantity) : 延長關注 plan 的時間
| $user->subscription('plan')->decrement($quantity)	: 減少關注 plan 的時間
| $user->subscription('plan')->cancelNow() : 現在馬上 cancel subscription
| $user->subscription('plan')->cancelAtEndOfPeriod() : 在這個 cycle 結束後 cancel subscription
| $user->subscription('plan')->planId() : 取得這個 subscription 正在關注的 plan id
| $user->subscription('plan')->updateCard() : 更新此 plan 的 billing info
| 1. cancel
| 2. create
|
| 
*/

class PaypalGateway
{

	/**
	 * Instance of Beyond\Module\Cashier\PaypalCashier\BillableInterface
	 * user model 實作 BillableInterface
	 * 
	 * @var Beyond\Module\Cashier\PaypalCashier\BillableInterface
	 */ 
	protected $billable;

	/**
	 * Instance of Beyond\Module\Cashier\PaypalCashier\PlanInterface
	 *
	 * @var Beyond\Module\Cashier\PaypalCashier\PlanInterface
	 */ 
	protected $plan;

	/**
	 * 初始化 paypal gateway
	 *
	 * @param BillableInterface 
	 * @param Beyond\Module\Cashier\PlanInterface | string $plan
	 */ 
	public function __construct(BillableInterface $billable, $plan = NULL)
	{
		$this->init($billable, $plan);
	}

	/**
	 * initialize gateway
	 *
	 * @param $billable
	 * @return string $plan
	 */ 
	protected function init($billable, $plan)
	{
		$this->billable = $billable;

		$this->plan = ($plan instanceOf PlanInterface) ? $plan : $this->fetchPlan($plan);
	}

	/**
	 * 解析 plan instance
	 *
	 * @param string $plan
	 * @return Beyond\Module\Cashier\PlanInterface $plan
	 * @throws \Exception
	 */ 
	protected function fetchPlan($plan)
	{
		if(is_string($plan))
		{
			$plan =  Plan::getPlan($plan);

			return $plan;

		}
	}

 	/**
 	 * 取得 paypal customer
 	 *
 	 * @param string $uid
 	 * @return Beyond\Module\Cashier\PaypalCashier\Customer
 	 */ 
 	public function getPaypalCustomer($uid = null)
 	{ 		
 		// getPaypalId 會回傳 customer id
 		$uid = !is_null($uid) ? $uid  : $this->billable->getPaypalCustomerId();  

 		// 使用 uid 來取得 customer 物件，此 customer 物件必定與此 billable 有關聯
 		return $this->customer = Customer::retrieve($uid);
 	}

	/**
	 * 判斷此 user 是否為 customer 
	 * 
	 * @return boolean
	 */ 
	protected function isCustomer()
	{
		return !is_null($this->billable->paypal_subscription) ? true : false;
	}

	/**
	 * 建立 paypal recurring payment subscription。
	 * $user->subscription('plan')->create($cardInfo); // 帶入信用卡資訊
	 *
	 * @todo onSubscribing 需要完成
	 * @param array $cardInfo 信用卡資訊
	 * @param array $option   其他額外資訊
	 * @param Beyond\Module\Cashier\PaypalCashier $customer 
	 * @return void
	 */ 
	public function create(array $cardInfo, $customer = null)
	{
		// 要是 $customer 沒有 pass 進來
		if(!$customer)
		{
			// 要是此 user 還沒有建立 paypal customer 則建立 customer，並建立關聯
			if(!$this->isCustomer()) $customer = $this->createPaypalCustomer();
		}

		$_needs = array_merge($cardInfo, $this->buildPayload());
		
		// 建立新的 subscription
		$subscription = $customer->createSubscription($_needs);

		// 建立 subscription 跟 plan 的關聯
		$subscription->plan()->save($this->plan);

		// 建立 customer 跟 plan 的關聯
		$customer->subscription()->save($subscription);

		// 將剛建立好的 subscription 資訊儲存在 account 中
		$this->updateLocalPaypalData($customer, $subscription);	
	}

	/**
	 * 更新 account 中關於此 subscription 的資訊
	 *
	 * @param Beyond\Module\Cashier\PaypalCashier\Customer
	 * @param Beyond\Module\Cashier\PaypalCashier\Subscription
	 */ 
	public function updateLocalPaypalData($customer, $subscription)
	{
		$this->billable
			->setPaypalPlan($this->plan->getName())
			->setPaypalSubscription($subscription->profileId)
			->setPaypalCustomerId($customer->uid)
			->setTrialEndDate(NULL)
			->setPaypalIsActive();

		$this->billable->saveBillableInstance();
	}

	/**
	 * 建立 subscription 時需要的 info 
	 *
	 * @todo 將 MAXFAILEDPAYMENTS 拉到其他 config 中
	 * @return array 
	 */ 
	public function buildPayload()
	{

		$payload = array(
			// 'PROFILESTARTDATE'	=>	'2014-08-01T10:00:00Z',//$this->getDateTime(),
			'PROFILESTARTDATE'	=>	$this->getDateTime(),
			'DESC'				=>	$this->plan->getPlanName(), // DESC 為 plan 的名稱
			'BILLINGPERIOD'		=>	$this->plan->getBillingPeriod(),
			'BILLINGFREQUENCY'	=>	$this->plan->getBillingFrequency(),
			'AMT'				=>	$this->plan->getAmount(),
			'INITAMT'			=>	$this->plan->getInitAmount(),
			'MAXFAILEDPAYMENTS'	=>	3 
		);

		if($this->plan->hasTrial())
		{
			$trialInfo = $this->plan->fetchTrialInfo();

			$payload = array_merge($payload, $trialInfo);
		}


		return $payload;
	}

	/**
	 * 取得目前時間以提供 
	 *
	 * @todo 用 carbon 來產生時間
	 * @return string
	 */ 
	protected function getDateTime()
	{
		$timeStamp = time()+date('Z');

		return gmdate("Y-m-d H:i:s",$timeStamp);
	}

	/**
	 * 增加 subscription 的 cycle 
	 * $account->subscription('monthly')->increment(1);
	 *
	 * @param integer $quantity
	 * @return void
	 */ 
	public function increment($quantity)
	{
		// 取得此 plan 的 total billing cyle
		$cycles = $this->plan->getTotalBillingCycle(); 

		// 將此 cycle 乘上 quantity 
		$increment = $quantity * $cycles;

		$customer = $this->getPaypalCustomer();

		$subscription = $customer->findSubscription($this->billable->getPaypalSubscription());

		$subscription->increaseCycle($increment);
		
	}

	/**
	 * 減少 subscription 的 cycle
	 * $account->subscription('monthly')->decrement(1);
	 *
	 * @todo 要是存在的 cycle 比減去的少會出錯
	 * @param integer $quantity
	 * @return void
	 */ 
	public function decrement($quantity)
	{
		// 取得此 plan 的 total billing cyle
		$cycles = $this->plan->getTotalBillingCycle(); 

		// 將此 cycle 乘上 quantity 
		$decrement = $quantity * $cycles;

		$customer = $this->getPaypalCustomer();

		$subscription = $customer->findSubscription($this->billable->getPaypalSubscription());

		$subscription->decreaseCyle($decrement);
	}

	/**
	 * 轉換到其他 plan。
	 * 更新此 plan 內容
	 * $account->subscription('yearly')->swap();
	 * 
	 * @todo trial period 算法
	 * @param array $cardInfo 新用卡資訊
	 */
	 public function swap($cardInfo)
	 {
	 	// 要是此 account 沒有關注此 plan。
	 	if(!$this->billable->onPlan($this->plan->getName()))
	 	{
	 		// 取得 customer
	 		$customer = $this->getPaypalCustomer();

	 		// 取消 subscription
	 		$customer->cancelSubscription($this->billable->getPaypalSubscription()) ;

	 		// 建立一個新的 subscription
	 		$this->create($cardInfo, $this->customer);
	 	} 	
	 } 

	/**
	 * 取消 subscription，有兩種
	 * 1. 馬上取消 subscription
	 * 2. 是否要記錄 cancel 的日期？要 updated_at
	 * 取消的 subscription 沒有辦法還原。
	 * $account->subscription()->cancel()
	 * 
	 * if(!$account->subscribed()) $account->subscription('monthly')->create();
	 * @return void
	 */ 
	public function cancel()
	{
		// 取得 subscription
		$customer = $this->getPaypalCustomer();

		$customer->cancelSubscription($this->billable->getPaypalSubscription());

		// 設定 subscription 結束時間
		$this->billable->setSubscriptionEndDate(

			$this->getSubscriptionEndTimeStamp($customer)

		);

		// 狀況模擬：取消 credit 資訊
		// 1. 清空 credit credit 資訊
		// 2. confirm 修改
		// 3. cancel 此 account
		// 4. on grace period
		// 5. 到期
		// 6. 填入新的 credit card 資訊
		// 7. confirm change
		// 8. 建立新的 subscription

		// 連 account 的 subscription profileid 一起清空
		// 此 profileid 已經 cancel，必須重新建立新的 subscription
		$this->billable->deactivatePaypal()->saveBillableInstance();		

		
	}

	/**
	 * 暫停 subscription。可以還原。
	 *
	 * @return void
	 */ 
	public function suspend()
	{
		// 取得跟此 account 對應的 customer 
		$customer = $this->getPaypalCustomer();

		// 記錄 suspend 日期，到 account 中的 subscription_ends_at 以提供判斷		
		// 要取得此 subscription 的下次請款的時間來作為 subscription end date
		// 在 suspend 之前要先取得 next billing date，因為在 suspend 之後就沒辦法取得此資訊。
		$this->billable->setSubscriptionEndDate(

			$this->getSubscriptionEndTimeStamp($customer)

		);
		
		// 狀況模擬：取消 credit 資訊
		// 1. 清空 credit credit 資訊
		// 2. confirm 修改
		// 3. suspend 此 account
		// 4. on grace period
		// 5. 到期
		// 6. 填入新的 credit card 資訊
		// 7. confirm change
		// 8. reactivate 此 subscription

		// 暫停 subscription
		$customer->suspendSubscription($this->billable->getPaypalSubscription());

		// 設定此 account 為 inactive
		$this->billable->setPaypalIsActive(false)->saveBillableInstance();

	}

	/**
	 * 取得 subscription 下次請款日期。當建立完 recurring payment 的同時(plan 為非 semi month)。 
	 * paypal 需要數小時的時間來請款，在這之前顯示的 "next billing date" 都是為當天的日期。
	 * 這不代表 paypal api 失靈了。當請款成功後才會看到 "next billing date" 更新為下個月一號。
	 * 
	 * @param Beyond\Module\Cashier\PaypalCashier
	 * @return 
	 */ 
	protected function getSubscriptionEndTimeStamp($customer)
	{
		$subscription = $customer->findSubscription($this->billable->getPaypalSubscription());

		return $this->getSubscriptionNextBillingDate($subscription);

	}

	/**
	 * 取得 subscription 下次付款時間
	 *
	 * @param Beyond\Module\Cashier\PaypalCashier\Subscription
	 * @return \DateTime
	 */ 
	protected function getSubscriptionNextBillingDate($subscription)
	{
		$subscriptionInfo = $subscription->getSubscriptionInfo();

		$subscription_end_date = $subscriptionInfo['NEXTBILLINGDATE'];

		$_parsed = substr($subscription_end_date, 0, strripos($subscription_end_date,'t'));

		$_dateArr = explode('-', $_parsed);

		$carbonized = Carbon::createFromDate($_dateArr[0], $_dateArr[1], $_dateArr[2]);

		return $carbonized;
	}

	/**
	 * 重新啟動 subscription。將指定的 subscription change status to active status
	 *
	 * $user->subscription()->resume();
	 * @todo 有錯誤，需要修改
	 */ 
	public function resume()
	{
		// 取得 customer 
		$customer = $this->getPaypalCustomer();

		$subscription = $customer->resume($this->billable->getPaypalSubscription());

		// setPaypalIsActive(true);
		// paypal subscription 的值應該還存在，可是再 set 一次
		// set subscription end date 
		// 儲存 billable instance
		$this
			->billable
			->setPaypalSubscription($subscription->profileId)
			->setSubscriptionEndDate(NULL)
			->setPaypalIsActive(true)
			->saveBillableInstance();
	}

	/**
	 * 取得目前關注的 subscription
	 *
	 * @return Beyond\Module\Cashier\PaypalCashier\Subscription
	 */ 
	public function getPaypalSubscription()
	{
		$customer = $this->getPaypalCustomer();

		$subscription = $customer->findSubscription($this->billable->getPaypalSubscription());

		return $subscription;
		
	}

	/**
	 * 更新指定 subscription 付款的信用卡。如何找到正確的 subscription 並取消？
	 * 
	 * @param array $cardInfo
	 */ 
	public function updateCard($cardInfo)
	{
		// 取得此 account 對應的 customer
		$customer = $this->getPaypalCustomer();

		$customer->updateCard($this->billable->getPaypalSubscription(), $cardInfo);

	}	

	/**
	 * 取得 plan id
	 *
	 * @todo 還未測試
	 * @return string
	 */
	public function planId()
	{
		$customer = $this->getStripeCustomer();

		if (isset($customer->subscription))
		{
			return $customer->subscription->plan()->get()[0]->id;
		}
	}

	/**
	 * 建立新的 paypal customer
	 *
	 * @param array $cardInfo
	 */ 
	public function createPaypalCustomer()
	{
		// 建立新的 customer
		$customer = Customer::newCustomer();

		return $customer->reload();
	}
}