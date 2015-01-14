<?php

class StubUser extends Illuminate\Database\Eloquent\Model
{
	protected $table = 'users';

	/**
	 * user 跟 account 為 one-to-many
	 */ 
	public function account()
	{
		return $this
			->belongsToMany('Beyond\Module\Cashier\PaypalCashier\Account', 'user_accounts', 'account_id', 'user_id');
			// ->withTimestamps();
	}
}