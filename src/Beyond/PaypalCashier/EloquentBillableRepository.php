<?php namespace Beyond\PaypalCashier;

use Beyond\PaypalCashier\BillableRepositoryInterface;
use Illuminate\Support\Facades\Config;

/**
 * 1. 實例化實作 BillableInterface 的物件
 * 2. 依照 profileid 找到對應的 billable 物件(Beyond\Module\Cashier\PaypalCashier\Account)
 */ 
class EloquentBillableRepository implements BillableRepositoryInterface
{

	/**
	 * 依照 profile id 找到對應的 billable instance
	 *
	 * @param string $profileId 
	 */ 
	public function find($profileId)
	{

		// config 中設定的 model 為 Beyond\Module\Cashier\PaypalCashier\Account
		$model = $this->createCashierModel(\Config::get('paypal-cashier::service.paypal.model'));

		return $model->where('paypal_subscription', $profileId)->first();
	}

	/**
	 * 實例化實作 billable interface 的 model
	 *
	 * @return Beyond\Module\Cashier\PaypalCashier\BillableInterface
	 */ 
	public function createCashierModel($class)
	{

		$model = new $class;

		if ( ! $model instanceof BillableInterface)
		{
			throw new \InvalidArgumentException("Model does not implement BillableInterface.");
		}

		return $model;
	}
}