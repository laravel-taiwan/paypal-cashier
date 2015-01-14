<?php namespace Beyond\PaypalCashier;

use DateTime;
use Carbon\Carbon;
use Beyond\PaypalCashier\PlanInterface;
use Beyond\PaypalCashier\PaypalCreditCard;

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

trait BillableTrait{

	/**
	 * 建立 user 跟 customer 之間的關係。one-to-one relationship
	 *
	 * @return \Illuminate\Database\Eloquent\Relations\HasOne
	 */ 
	public function customer()
	{
		return $this->hasOne('Beyond\Module\Cashier\PaypalCashier\Customer', 'uid', 'customer_uid');
	}

	/**
	 * 跟 account 跟 user 的關係為 many-to-many
	 *
	 * @todo 這邊先使用 StubUser 來測試，之後再換回來。
	 */ 
	public function user()
	{
		return $this->belongsToMany('StubUser', 'user_accounts', 'user_id', 'account_id');
	}

	/**
	 * 取得跟此 account 關聯的 user
	 *
	 * @return Beyond\Users\User
	 */ 
	public function getUser()
	{
		return $this->user()->get()[0];
	}

	/**
	 * paypal gateway
	 *
	 * @param mixed 
	 * @return Beyond\Module\Cashier\PaypalCashier\PaypalGateway
	 */ 
	public function subscription($plan = null)
	{
		return new PaypalGateway($this, $plan);
	}

	/**
	 * 取得會顯示在 invoice 上面的名稱
	 *
	 * @return string
	 */ 
	public function getBillableName()
	{
		$user = $this->getUser();

		return $user->email;
	}

	/**
	 * 寫入 db 
	 */ 
	public function saveBillableInstance()
	{
		$this->save();
	}

	/**
	 * 更新付款的信用卡
	 * $account->updateCard()
	 * 
	 * @param array $cardInfo
	 * @return void
	 */ 
	public function updateCard(array $cardInfo)
	{
		$this->subscription()->updateCard($cardInfo);
	}

	/**
	 * 此 account 是否在 trial 階段
	 *
	 * @return bool
	 */
	 public function onTrial()
	 {
	 	if ( ! is_null($this->getTrialEndDate()))
		{
			return Carbon::today()->lt($this->getTrialEndDate());
		}
		else
		{
			return false;
		}
	 } 

	 /**
	  * 判斷此 account 是否在 grace period 期間。
	  * 所謂的 grace period 就是用戶還可以使用服務但是他已經取消了關注。他在下一個 billing period 時還是可以繼續使用
	  *
	  * @return boolean 
	  */ 
	 public function onGracePeriod()
	 {
	 	if ( ! is_null($endsAt = $this->getSubscriptionEndDate()))
		{
			return Carbon::now()->lt(Carbon::instance($endsAt));
		}
		else
		{
			return false;
		}
	 }

	 /**
	  * 判斷此 account 是否存在 active subscription
	  * 1. 如果 onGracePeriod 則判斷為 active
	  * 2. 如果 onTrial 則判斷為 active
	  * 3. 本身為 active
	  * 若非以上則為 false
	  * 
	  * @return bool
	  */ 
	 public function subscribed()
	 {
	 	return $this->paypalIsActive() || $this->onGracePeriod() || $this->onTrial();
	 }

	/**
	 * 判斷 account 是否已過期 
	 * 1. 非 active
	 * 2. 非 on grace period
	 * 3. 非 on trial period
	 *
	 * @return bool
	 */
	public function expired()
	{
		return ! $this->subscribed();
	}

	/**
	 * 判斷此 account 是否已經 cancel
	 * 1. 此 account 為 paypal customer
	 * 2. paypal is not active
	 *
	 * @return bool
	 */ 
	public function cancelled()
	{
		return $this->readyForBilling() && !$this->isPaypalActive();
	}

	/**
	 * 判斷有沒有 subscribe 過
	 * false: 從來沒有 subscribe 過
	 * 
	 * @return bool
	 */ 
	public function everSubscribed()
	{
		return $this->readyForBilling();
	}

	/**
	 * @todo
	 */ 
	public function requiresCardUpFront()
	{

	}

	/**
	 * 判斷此 account 是否為 paypal customer
	 *
	 * @return bool
	 */ 
	public function readyForBilling()
	{
		return ! is_null($this->customer_id);
	}

	/**
	 * 當 cancel 此 subscription 時，此 subscription 也會 deactivate
	 *
	 * @return Beyond\Module\Cashier\PaypalCashier\Account
	 */ 
	 public function deactivatePaypal()
	 {
	 	$this->setPaypalIsActive(false);

	 	$this->profileId = null;

	 	return $this;
	 }

	 /**
	  * 取得 subscription 結束日期
	  *
	  * @param \DateTime | NULL
	  */ 
	 public function getSubscriptionEndDate()
	 {
	 	return $this->subscription_ends_at;
	 }

	 /**
	  * 設定 subscription 結束日期
	  *
	  * @return \DateTime | NULL
	  */ 
	 public function setSubscriptionEndDate($date)
	 {
	 	$this->subscription_ends_at = $date;

	 	return $this;
	 }

	 /**
	  * 取得試用結束日期
	  *
	  * @return DateTime
	  */ 
	 public function getTrialEndDate()
	 {
	 	return $this->trial_ends_at;
	 }

	 /**
	  * 設定試用結束日期
	  *
	  * @param \DateTime|null  $date
	  * @return \Laravel\Cashier\BillableInterface
	  */ 
	 public function setTrialEndDate($date)
	 {
	 	$this->trial_ends_at;

	 	return $this;
	 }

	/**
	 * 判斷此 account 是否 paypal active
	 *
	 * @return bool
	 */ 
	public function paypalIsActive()
	{
		return $this->paypal_active;
	}

	/**
	 * 設定 paypal is active
	 *
	 * @param $active 
	 */ 
	public function setPaypalIsActive($active = true)
	{
		$this->paypal_active = $active;

		return $this;
	}

	 /**
	  * 取得信用卡物件
	  *
	  * @return Beyond\Module\Cashier\PaypalCashier\CreditCard
	  */ 
	 public function getNewCreditCard()
	 {
	 	// return new PaypalCreditCard;
	 }

	/**
	 * 判斷此 account 是否在關注指定的 plan
	 * $account->onPlan('mothly') | $account->onPlan($plan)
	 *
	 * @todo 應該依照 plan id 來判斷而非 plan 的名稱
	 * @param Beyond\Module\Cashier\PaypalCashier\PlanInterface | string
	 * @return false
	 */ 
	public function onPlan($plan)
	{
		if($plan instanceof PlanInterface) $plan = $plan->title;

		// 比對 plan title
		return ($this->plan == $plan) || false;
	}

	/**
	 * 取得信用卡
	 *
	 * @var Illuminate\Support\Collection
	 */ 
	public function creditCards()
	{

	}

	/**
	 * 取得預設信用卡
	 *
	 * @var Beyond\Module\Cashier\PaypalCashier\CreditCard
	 */ 
	public function getDefaultCreditCard()
	{

	}

	/**
	 * 設定此 account 關注的 plan
	 *
	 * @param Beyond\Module\Cashier\PaypalCashier\PlanInterface
	 */ 
	public function setPaypalPlan($plan)
	{
		$this->plan = $plan;

		return $this;
	}

	/**
	 * 取得此 account 關注的 plan
	 *
	 * @return string
	 */ 
	public function getPaypalPlan()
	{
		return $this->plan;
	}

	/**
	 * 設定 paypal subscription 的 profile id 到此 account 
	 *
	 * @param Beyond\Module\Cashier\PaypalCashier\Subscription
	 */ 
	public function setPaypalSubscription($profileId)
	{
		// var_dump('dd');
		// var_dump($profileId);

		$this->paypal_subscription = $profileId;

		return $this;
	}

	/**
	 * 回傳 profile id
	 *
	 * @return string
	 */
	 public function getPaypalSubscription()
	 {
	 	return $this->paypal_subscription;
	 } 

	 /**
	  * 設定 customer id
	  * 
	  * @param string $customerId 
	  */ 
	 public function setPaypalCustomerId($customer_uid)
	 {
	 	$this->customer_uid = $customer_uid;

	 	return $this;
	 }

	 /**
	  * 取得 customer uid，在 stripe 為 getStripeId
	  *
	  * @return string
	  */ 
	 public function getPaypalCustomerId()
	 {
	 	return $this->customer_uid;
	 }
}

