<?php namespace Beyond\PaypalCashier;
/*
     .o8                                                         .o8
    "888                                                        "888
     888oooo.   .ooooo.  oooo    ooo  .ooooo.  ooo. .oo.    .oooo888
     d88' `88b d88' `88b  `88.  .8'  d88' `88b `888P"Y88b  d88' `888
     888   888 888ooo888   `88..8'   888   888  888   888  888   888
     888   888 888    .o    `888'    888   888  888   888  888   888
     `Y8bod8P' `Y8bod8P'     .8'     `Y8bod8P' o888o o888o `Y8bod88P" Inc.
                         .o..P'
                         `Y8P'
 */

/**
 * @author Bryan Huang
 */ 

use Beyond\Core\BaseEloquentModel;

class CreditCard extends BaseEloquentModel implements CreditCardInterface
{
	/**
	 * table 名稱
	 *
	 * @var string
	 */ 
	protected $table = 'credit_card';

}
