<?php

use Beyond\PaypalCashier\ApiContextGenerator;

class ApiContextGeneratorTest extends TestCase
{
    public function test_generate_api_context_instance()
    {
        $client_id = 'AW3K_xAvssx_6NKlwND8AkOdilveb2t1n9qtcrc5DHxfvnnrME3u9uPqI9gv';
        $client_secret = 'EJRSABDIeN15U2zw32Ai0YlB2IruSjRetDDG2eiK7MwqoWR6mDlgZ5xXaOq2';

        $apiContext = ApiContextGenerator::make($client_id, $client_secret);

        $this->assertInstanceOf('PayPal\Rest\ApiContext', $apiContext);
    }

}