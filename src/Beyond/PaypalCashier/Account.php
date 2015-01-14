<?php namespace Beyond\PaypalCashier;

use Beyond\PaypalCashier\BillableTrait;
use Beyond\PaypalCashier\BillableInterface;
use Beyond\PaypalCashier\PaypalGateway;
use Beyond\Core\BaseEloquentModel;
use Rhumsaa\Uuid\Uuid;

class Account extends BaseEloquentModel implements BillableInterface
{
	use BillableTrait;

	protected $table = 'account';

	/**
	 * 在 database 中試用期結束時間
	 *
	 * @param array
	 */ 
	protected $dates = ['trial_ends_at', 'subscription_ends_at'];

	protected $fillable = array(
		'title',
		'name',
		'slug',
		'uid',
	);

	/**
	 * 預設 title 為 uid
	 *
	 * @return void
	 */ 
	public function __construct()
	{
		$uuid1 = static::generateUuid1();

		$this->fill(['title'	=>	$uuid1]);
	}

	/**
	 * 產生 uuid1
	 *
	 * @return string
	 */ 
	public static function generateUuid1() 
	{
		return (string)Uuid::uuid1();
	}
}