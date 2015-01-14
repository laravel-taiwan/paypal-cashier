<?php namespace Beyond\PaypalCashier;

use Beyond\PaypalCashier\EloquentBillableRepository;
use Illuminate\Support\ServiceProvider;

class PaypalCashierServiceProvider extends ServiceProvider
{
	/**
	 * Register cashier components.
	 *
	 * @return void
	 */ 
	public function register()
	{
		// bind share BillableInterface
		$this->registerEloquentBillableRepository();

	}

	/**
	 * Boot cashier components.
	 *
	 * @return void
	 */ 
	public function boot()
	{
		$this->package('beyond/paypal-cashier');
	}

	protected function registerEloquentBillableRepository()
	{

		$this->app->bindShared('Beyond\PaypalCashier\BillableRepositoryInterface', function($app){

			return new EloquentBillableRepository;

		});
	}
}