<?php 

use Beyond\PaypalCashier\PaypalCreditCard;

/**
 * @group paypalCashier
 */ 
class PaypalCreditCardTest extends TestCase
{
	public function setUp()
	{
		parent::setUp();

		// migrate package table
		Artisan::call('migrate', array('--bench'	=>	'beyond/paypal-cashier'));

	}

	public function tearDown()
	{
		$this->setUp();
	}

	/**
	 * 提供測試 credit card 資訊
	 */ 
	public function creditCardProvider()
	{
		$cardInfo = array(
			'payer_id'		=>	'sample1234', 
			'type'			=>	'visa', 
			'number'		=>	'4390755230449356', 
			'expire_month'	=>	'05', 
			'expire_year'	=>	'2018',
			'first_name'	=>	'chiheng', 
			'last_name'		=>	'Huang'
		);

		return array(
			array($cardInfo)
		);
	}

	/**
	 * 測試儲存 credit card info 
	 *
	 * @dataProvider creditCardProvider
	 */ 
	public function testGetCreditCardTokenAndSaveToDb($cardInfo)
	{

		$creditCard = new PaypalCreditCard;

		$creditCard->fill($cardInfo);

		$this->assertEquals($creditCard['payer_id'], $creditCard->getAttributes()['payer_id']);
		
		$creditCard->save();

		$cardInfo = DB::table('credit_card')->get();

		$this->assertNotEmpty($cardInfo[0]->paypal_credit_card_token);
	}
}