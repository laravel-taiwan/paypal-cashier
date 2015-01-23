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
        $this->registerPlanRepository();
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

    protected function registerPlanRepository()
    {
        $this->app->bindShared('Beyond\PaypalCashier\PlanRepository', function($app){

            $client_id = $app['config']->get('paypal-cashier::credentials.client_id');

            $client_secret = $app['config']->get('paypal-cashier::credentials.client_secret');

            $apiContextConfig = $app['config']->get('paypal-cashier::api_context_settings');

            $apiContext = ApiContextGenerator::make($client_id, $client_secret, $apiContextConfig);

            return new PlanRepository(new Plan ,$apiContext, $apiContextConfig);
        });
    }

	protected function registerEloquentBillableRepository()
	{

		$this->app->bindShared('Beyond\PaypalCashier\BillableRepositoryInterface', function($app){

			return new EloquentBillableRepository;

		});
	}
}