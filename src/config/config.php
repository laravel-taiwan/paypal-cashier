<?php

return array(

	'service'	=>	[

		/*
		| EloquentBillableRepository 設定檔
		--------------------------------------
		| 1. 設定實作 BillableInterface 的 model
		| 
		|
		|
		*/
		'paypal'	=>	[

			'model'	=> 'Beyond\PaypalCashier\Account'

		]		
	],

	// 'restful_base_url'	=>	[

	// 	'testing'	=>	''

	// ]
);