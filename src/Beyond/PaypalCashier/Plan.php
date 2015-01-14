<?php namespace Beyond\PaypalCashier;

use Beyond\Core\BaseEloquentModel;
use Beyond\PaypalCashier\PlanInterface;

class Plan extends BaseEloquentModel implements PlanInterface
{
	/**
	 * @var string
	 */ 
	protected $table = 'paypal_plan';

	/**
	 * Allow to mass assignment
	 *
	 * @var array
	 */ 	
	protected $fillable = array(
		'title',
		'name',
		'slug',
		'plan_name',
		'total_billing_cycle',
		'billing_period',
		'billing_frequency',
		'initamt',
		'hasTrial',
		'trial_total_billing_cycle',
		'trial_billing_period',
		'trial_billing_frequency'
	);

	/**
	 * 跟 subscription 的關聯為 one-to-many
	 *
	 * @return Illuminate\Database\Eloquent\Relations\BelongsToMany
	 */ 
	public function subscription()
	{
		return $this->belongsToMany('Beyond\Module\Cashier\PaypalCashier\Subscription', 'id', 'subscription_id');
	}

	/**
	 * 取得 plan name
	 * 
	 * @return string
	 */ 
	public function getPlanName()
	{
		return $this->plan_name;
	}	

	/**
	 * 取得 total billing cycle
	 *
	 * @return string
	 */ 
	public function getTotalBillingCycle()
	{
		return $this->total_billing_cycle;
	}

	/**
	 * 取得 billing period
	 * 1. month
	 * 2. year
	 *
	 * @return string
	 */ 
	public function getBillingPeriod()
	{
		return $this->billing_period;
	}

	/**
	 * 取得 billing frequency
	 *
	 * @return string
	 */ 
	public function getBillingFrequency()
	{
		return $this->billing_frequency;
	}

	/**
	 * 判斷此 plan 是否有 trial period
	 *
	 * @return boolean
	 */ 
	public function hasTrial()
	{
		return $this->hasTrial;
	}

	/**
	 * 取得起始的一次性付款數額
	 *
	 * @todo 這個數額應該經由計算得出而不是直接 return
	 * @return string 
	 */ 
	public function getInitAmount()
	{
		return $this->initamt;
	}

	/**
	 * 取得 amount
	 *
	 * @return float 
	 */ 
	public function getAmount()
	{
		return $this->amount;
	}

	/**
	 * 獲取 trial info
	 *
	 * @return array
	 */ 
	public function fetchTrialInfo()
	{
		return array(
			'TRIALBILLINGPERIOD'=>	$this->trial_billing_period, // 試用期一個 cycle 是一個月
			'TRIALBILLINGFREQUENCY'=> $this->trial_billing_frequency, 	// 計費頻率
			'TRIALTOTALBILLINGCYCLES'=> $this->trial_total_billing_cycle,  // 幾個 cycle
		);
	}

	/**
	 * 取得指定的 plan，取得第一個 符合的 plan
	 *
	 * @param Beyond\Module\Cashier\PaypalCashier\PlanInterface
	 */ 
	public static function getPlan($name)
	{
		return static::where('plan_name', $name)->first();
	}
}