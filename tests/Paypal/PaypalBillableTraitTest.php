<?php

use Mockery as m;
use Carbon\Carbon;
use Beyond\Core\BaseEloquentModel;
use Beyond\Users\User;
use Beyond\PaypalCashier\Account;
use Beyond\PaypalCashier\BillableTrait;

require (__DIR__.'/../seeders/AccountTableSeeder.php');
require (__DIR__.'/../seeders/PaypalPlanTableSeeder.php');
/**
 * @group paypalCashier
 */ 
class PaypalBillableTraitTest extends TestCase
{

	// protected $wantMigrate = false;

	public function setUp()
	{
		parent::setUp();

		Eloquent::unguard();

		// package migration
		Artisan::call('migrate', array('--bench' => 'beyond/paypal-cashier'));

		// execute package seeders
		Artisan::call('db:seed', array('--class'	=>	'AccountTableSeeder'));
		Artisan::call('db:seed', array('--class'	=>	'PaypalPlanTableSeeder'));
	}

	public function tearDown()
	{
		m::close();
	}

	public function testGetBillableName()
	{
		// 建立一個 user 
		$user = TraitStubUser::create([
			'first_name'  => 'Starck',
            'last_name'   => 'Lin',
            'password'    => 'password',
            'email'       => 'starck@beyond.com.tw',
            'description' => 'Founder / Art Director / Developer',
		]);

		// 建立一個 account
		$account = new TraitStubAccount;

		$account->saveBillableInstance();

		// user 跟 account 建立關係
		$account->user()->save($user);

		// 使用 billable 的 get billable name 方法
		$billableName = $account->getBillableName();

		$this->assertEquals('starck@beyond.com.tw', $billableName);
		
	}

	/**
	 * 測試更新 paypal credit card
	 */ 
	public function testBillableTraitUpdateCard()
	{
		$_cardInfo = [
			'card_type'	=>	'visa',
			'number'	=>	'1234',
		];

		$account = m::mock('TraitStubAccount[subscription]');

		$account
			->shouldReceive('subscription')
			->once()
			->andReturn($gateway = m::mock('Beyond\Module\Cashier\PaypalCashier\PaypalGateway'));

		$gateway
			->shouldReceive('updateCard')
			->once()
			->with($_cardInfo);

		$account->updateCard($_cardInfo);
	}

	/**
	 * 測試 trial end 功能
	 */ 
	public function testTrialEnd()
	{
		$account = new Account;

		$account->trial_ends_at = Carbon::today()->addDays(14);

		$account->save();

		$this->assertTrue($account->onTrial());

	}

	public function testOnGracePeriod()
	{	
		$account = new Account;

		$account->subscription_ends_at = Carbon::createFromDate('2015', '12', '25');

		$account->save();

		$this->assertTrue($account->onGracePeriod());
	}

	public function testBillableTraitSubscribed()
	{
		$account = new Account;

		// 1. 測試設置 account is active 為 false
		$account->paypal_active = true;

		$account->save();

		$this->assertTrue($account->subscribed());
		
		// 2. 測試設置 account is on grace period
		$account->paypal_active = false;

		$account->subscription_ends_at = Carbon::today()->addWeeks(2);

		$account->save();

		$this->assertTrue($account->subscribed());

		// 測試設置 account is on trial period
		$account->paypal_active = false;

		$account->subscription_ends_at = Carbon::today()->yesterday();

		$account->trial_ends_at = Carbon::today()->addWeeks(2);

		$account->save();

		$this->assertTrue($account->subscribed());
	}

	public function testReadyForBilling()
	{
		$account = new Account;

		$account->customer_id = 'somecustomer';

		$this->assertTrue($account->readyForBilling());

		$account->customer_id = NULL;

		$this->assertFalse($account->readyForBilling());
	}
}

class TraitStubAccount extends Illuminate\Database\Eloquent\Model
{
	protected $table = 'account';

	use Beyond\PaypalCashier\BillableTrait;
}

class TraitStubUser extends Beyond\Users\User{}
