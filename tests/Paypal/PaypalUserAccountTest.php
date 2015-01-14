<?php

use Beyond\Module\Cashier\PaypalCashier\Account;
use Beyond\Module\Cashier\PaypalCashier\Customer;
use \StubUser;


/*
| user 跟 accounts 關係測試
--------------------------------------
| 1. account = new Account;
| 	 $user->account()->get();	
|
|
*/

class PaypalUserAccountTest extends TestCase
{
	public function setUp()
	{
		parent::setUp(); 

		$this->seed();
	}

	public function testInitAccount()
	{
		$account = new Account;

		$this->assertInstanceOf('Beyond\Module\Cashier\PaypalCashier\Cashier\Account', $account);
	}

	/**
	 * 測試 account 跟 user 做關聯
	 */ 
	public function testAccountRelateToUser()
	{
		$user = StubUser::find(1);

		$account = Account::create([
			'title'	=>	'some title'
		]);

		$user->account()->save($account);

		$user = $account->user()->get()[0];

		$this->assertNotEmpty($user->email);

		$account = $user->account()->get()[0];

		$this->assertNotEmpty($account->title);
		
	}

	public function testGetCustomerViaAccount()
	{
		$customer = new Customer;

		$customer->title = 'sample customer';

		$customer->save();

		$customerUid = $customer->uid;

		$account = new Account;

		$account->setPaypalCustomerId($customerUid);

		$account->save();

		$customer = $account->customer()->first();

		$this->assertEquals('sample customer', $customer->title);
		
	}

	public function testAccountCreateSubscription()
	{
		$account = new Account;

		$gateway = $account->subscription('monthly');

	}

}

