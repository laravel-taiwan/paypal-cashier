<?php

use Beyond\PaypalCashier\Account;

/**
 * @group paypalCashier
 */ 
class BillableRepositoryTest extends TestCase
{
	public function setUp()
	{
		parent::setUp();
	}

	public function tearDown()
	{
		$this->setUp();
	}

	public function testFindCashierModelByProfileId()
	{
		// create new account
		$account = new Account;
		// set its subscription id
		$account->paypal_subscription = '1234';
		// save instance
		$account->save();

		$repo = \App::make('Beyond\PaypalCashier\BillableRepositoryInterface');

		$model = $repo->find('1234');

		$this->assertInstanceOf('Beyond\PaypalCashier\Account', $model);

		$this->assertEquals('1234', $model->paypal_subscription);
		
	}
}