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

    'credentials'   =>  [
        'client_id'     => 'AW3K_xAvssx_6NKlwND8AkOdilveb2t1n9qtcrc5DHxfvnnrME3u9uPqI9gv',
        'client_secret' => 'EJRSABDIeN15U2zw32Ai0YlB2IruSjRetDDG2eiK7MwqoWR6mDlgZ5xXaOq2'
    ],

    'api_context_settings'  =>  [
        'mode' => 'sandbox',
        'log.LogEnabled' => true,
        'log.FileName' => '../PayPal.log',
        'log.LogLevel' => 'FINE',
        'validation.level' => 'log',
        'cache.enabled' => true,
    ]
);