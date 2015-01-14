<?php namespace Beyond\PaypalCashier;

use Beyond\PaypalCashier\Subscription;
use Beyond\Core\BaseEloquentModel;
use Rhumsaa\Uuid\Uuid;

/*
| Customer class 
--------------------------------------
| Customer 為一個 Eloquent model 跟 user 之間為 one-to-one
| 
| 
| $customer = new Customer 
| $account = $customer->account()->get()[0]; 選擇 account
| $customer = Customer::retrieve($uid);
| $account->createSubscription($params) : $params 為 credit card 資訊
| $customer->cancelSubscription($profileId) : 
| $customer->suspoendSubscription($profileId)
| $customer->reactivateSubscription($profileId)
| $customer->updateSubscription($params)
| $customer->saveSubscription($params)
| $customer->findSubscription($profileId)
*/
class Customer extends BaseEloquentModel
{
	/**
	 * paypal customer 使用的資料表單
	 *
	 * @var string 
	 */ 
	protected $table = 'paypal_customer';

	/**
	 * @var array
	 */ 
	protected $fillable = array(
		'title',
		'name',
		'slug',
		'uid'
	);

	/**
	 * Instance of Beyond\Module\Cashier\PaypalCashier\Subscription
	 *
	 * @var Beyond\Module\Cashier\PaypalCashier\Subscription
	 */ 
	public $subscription;

	/**
	 * @uses Beyond\Module\Cashier\PaypalCashier\Subscription
	 */ 
	public function __construct()
	{
		$this->init();
	}

	/**
	 * 在 dabase 中有記錄
	 * 1. title
	 * 2. slug
	 * 3. name
	 */ 
	public function init()
	{
		$uuid1 = static::generateUuid1();
		
		$this->fill(['title'	=>	$uuid1, 'uid'	=>	$uuid1]);
	}

	/**
	 * 取得一個新的 customer
	 *
	 * @todo 需不需要 save ?
	 * @return Beyond\Module\Cashier\PaypalCashier\Customer
	 */ 
	public static function newCustomer()
	{
		$_newCustomer = new static;

		$_newCustomer->save();

		return $_newCustomer->reload();
	}

	/**
	 * 產生 Uuid1 編碼
	 *
	 * @return string uuid
	 */ 
	protected static function generateUuid1()
	{
		return (string)Uuid::uuid1();
	}

	/**
	 * 跟 user 做關聯。one-to-one relations
	 *
	 * @var \Illuminate\Database\Eloquent\Relations\HasOne
	 */ 
	public function account()
	{
		return $this->belongsTo('Beyond\Module\Cashier\PaypalCashier\Account', 'uid', 'uid');
	}

	/**
	 * 跟 subscription 做關聯。 one-to-many relations
	 *
	 * @return Illuminate\Database\Eloquent\Relations\HasMany
	 */ 
	public function subscription()
	{
		return $this->hasMany('Beyond\Module\Cashier\PaypalCashier\Subscription', 'customer_id', 'id');
	}

	/**
	 * 使用指定的 $profileId 取得 subscription。並將 $subscription 屬性設定為此 *subscription 
	 * 在 paypal customer 中的 $id 為 stripe customer 物件中的屬性。
	 * 
	 * 每個 customer 都會有一個自己的 $uid，用此 uid 跟 user 做關聯。可以依照此 uid 來找到 account
	 *
	 * @param string $uid
	 * @return Beyond\Users\User | NULL
	 */ 
	public static function retrieve($uid)
	{
		$customer = static::where('uid', $uid)->get();

		return !$customer->isEmpty() ? $customer[0] : NULL;
	}

	/**
	 * 取得當前 paypal subscription 的 id 
	 *
	 * @return string || null
	 */ 
	public function getPaypalSubscription()
	{
		return $this->subscription ? $this->subscription->profileId : null;
	}

	/**
	 * 從此 Customer 擁有的 subscriptions 中尋找指定的 subscription 
	 *
	 * @todo 還無法測試，因為 user 還沒寫
	 * @param string $profileId	|| NULL
	 */ 
	public function findSubscription($profileId) 
	{
		$_result = $this->subscription()->where('profileId', $profileId)->get();

		return $this->subscription =  !$_result->isEmpty() ? $_result[0] : NULL;

	}

	/**
	 * 使用者建立新的 subscription
	 *
	 * @param $param 
	 * @param Beyond\Module\Cashier\PaypalCashier\Subscription 
	 */ 
	public function createSubscription(array $param)
	{
		// 取得一個新的 subscription 
		$this->subscription = $subscription = Subscription::newSubscription()->createSubscription($param);
		
		// 建立 customer 跟此 subscription 的關聯
		$this->subscription()->save($this->subscription);

		// 設定此 subscription 為現在這個 customer 正在操作的 subscription
		return $this->getNewlyRelatedSubscription($this->subscription);
	}

	/**
	 * 取得最新關聯的 subscription
	 *
	 * @return Beyond\Module\Cashier\PaypalCashier\Subscription
	 */ 
	protected function getNewlyRelatedSubscription($subscription)
	{

		$_result = $this->reload()->subscription()->where('id', $subscription->id)->get();

		if($_result->isEmpty()) throw new Exception('relation to subscription has failed to establish');
		
		return $_result[0];
	}

	/**
	 * 取消 subscription
	 *
	 * @param $profileId paypal profile id
	 */ 
	public function cancelSubscription($profileId)
	{
		$target = $this->findSubscription($profileId);

		if(!is_null($target)) $target->cancel();

		return $target->reload();
		
	}

	/**
	 * 暫停 subscription
	 *
	 * @param string $profileId
	 * @return Beyond\Module\Cashier\PaypalCashier\Customer
	 */ 
	public function suspendSubscription($profileId)
	{
		$target = $this->findSubscription($profileId);

		if(!is_null($target)) $target->suspend();

		return $target->reload();
	}

	/**
	 * 更新 subscription
	 *
	 * @param array $params 指定更新的資料
	 */
	 public function updateSubscription(array $params)
	 {
	 	if(is_null($this->subscription))
	 	{
	 		if($this->profileIdKeyExists($params)) 
	 		{
	 			$this->findSubscription($params[$value]);
	 		}
	 		else
	 		{
	 			throw new \Exception('no subscription or profile id is specified');
	 		}
	 	}

	 	return $this->subscription->saveSubscription($params);
	 } 

	 /**
	  * 取得此 subscription 的詳細資訊
	  *
	  * @return array
	  */ 
	 public function getSubscriptionInfo()
	 {
	 	return $this->subscription->getSubscriptionInfo();
	 }

	 /**
	  * 更新信用卡資訊
	  *
	  * @param string $profileId
	  * @param array credit card info
	  */ 
	 public function updateCard($profileId, $cardInfo)
	 {
	 	$subscription = $this->findSubscription($profileId);

	 	$subscription->updateCard($cardInfo);
	 }

	 /**
	  * 重新啟用此 customer 關注的 subscription
	  *
	  * @param string $profileId
	  * @return Beyond\Module\Cashier\PaypalCashier\Subscription
	  */ 
	 public function resume($profileId)
	 {
	 	// 使用 findSubscription 取得 subscription
	 	$subscription = $this->findSubscription($profileId);

		// resume subscription
	 	$subscription->reactivate();

	 	return $subscription->reload();
	 	
	 }


	 /**
	  * 這是一個工具方法，找出 profileid 這個鍵值是否存在 array 中
	  *
	  * @param array
	  * @return string $value || boolean
	  */ 
	 private function profileIdKeyExists(array $param)
	 {
	 	$_keys= array_keys($param);

	 	foreach($_keys as $value)
	 	{
	 		if(preg_match("/$value/i", $value)) return $value;
	 	}

	 	return false;
	 }
}