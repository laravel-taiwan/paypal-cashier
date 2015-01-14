<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAccount extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('account', function(Blueprint $table)
		{
			$table->increments('id');
			$table->string('title')->nullable();
			$table->string('name')->nullable();
			$table->string('slug')->nullable();
			$table->string('uid')->nullable();
			$table->string('plan')->nullable();
			$table->string('paypal_subscription')->nullable(); 
			$table->boolean('paypal_active')->nullable();
			$table->string('customer_uid')->nullable();
			$table->timestamp('trial_ends_at')->nullable();
			$table->timestamp('subscription_ends_at')->nullable();
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
		Schema::drop('account');
	}

}
