<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreatePaypalSubscription extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('paypal_subscription', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('customer_id')->nullable();
			$table->string('title');
			$table->string('slug')->nullable();
			$table->string('name')->nullable();
			$table->string('desc')->nullable();
			$table->string('profileId')->nullable();
			$table->string('profileStatus')->nullable();
			$table->string('transactionId')->nullable();
			$table->string('timeStamp')->nullable();
			$table->string('correlationId')->nullable();
			$table->string('ack')->nullable();
			$table->string('version')->nullable();
			$table->string('build')->nullable();
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
		Schema::drop('paypal_subscription');
	}

}
