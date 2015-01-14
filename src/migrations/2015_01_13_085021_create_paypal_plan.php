<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreatePaypalPlan extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('paypal_plan', function(Blueprint $table)
		{
			$table->increments('id');
			$table->string('title');
			$table->string('name');
			$table->string('slug');
			$table->string('plan_name')->nullable();
			$table->integer('total_billing_cycle')->nullable();
			$table->string('billing_period')->nullable();
			$table->integer('billing_frequency')->nullable();
			$table->boolean('hasTrial')->nullable();
			$table->integer('trial_total_billing_cycle')->nullable();
			$table->string('trial_billing_period')->nullable();
			$table->integer('trial_billing_frequency')->nullable();
			$table->string('AUTOBILLOUTAMT')->nullable();
			$table->string('initamt')->nullable();
			$table->float('amount')->nullable();
	
			$table->integer('subscription_id')->nullable();
			$table->timestamps();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('paypal_plan');
	}

}
