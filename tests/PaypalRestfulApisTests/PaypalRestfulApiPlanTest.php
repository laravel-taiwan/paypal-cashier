<?php

use GuzzleHttp\Client;
use PayPal\Api\Plan;
use PayPal\Api\PaymentDefinition;
use PayPal\Api\MerchantPreferences;
use PayPal\Api\Currency;
use PayPal\Api\ChargeModel;

class PaypalRestfulApiPlanTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
    }

    /**
     * Guzzle client provider.
     *
     * @return GuzzleHttp\Client;
     */ 
    protected function guzzleClientProvider()
    {   
        return new Client;
    }

    /**
     * 取得 paypal access token
     *
     * @param GuzzleHttp/Client
     */ 
    public function getAccessToken($client)
    {
        $request = $client->createRequest('POST', 'https://api.sandbox.paypal.com/v1/oauth2/token', [

            'auth'  =>  ['ASExehBlunSTszcSEhc__w7P81AFACksp2WaMZ1u0LlB6TCpDF6j8whNZLed', 'EOvU8hB8XRJzfdwDYzJ6m-nZnWpmXgpQktqYIEfbvFi4VuPW8JXylxMKF7fU'],
            'headers'   =>  [
                'Accept: application/json', 
                'Accept-Language: en_US'
            ],

            'body'  =>  [
                'grant_type'    =>  'client_credentials'
            ],

            'config'    =>  
            [
                'curl'  =>  
                [
                    CURLOPT_SSL_VERIFYPEER  => 0,
                    CURLOPT_RETURNTRANSFER  => 1,
                    CURLOPT_VERBOSE         => 1    
                ]
            ],

            'stream'    =>  true
        ]);

        $response = $client->send($request); 

        $response_arr = json_decode($response->getBody()->getContents(), true);

//        var_dump($response_arr);
//        die;
        return $response_arr['access_token'];
    }

    /**
     * Test paypal sdk create plan
     *
     */
    public function test_paypal_sdk_create_plan()
    {
        // Create a new instance of Plan object
        $plan = new Plan();

        // # Basic Information
        // Fill up the basic information that is required for the plan
        $plan->setName('T-Shirt of the Month Club Plan')
            ->setDescription('Template creation.')
            ->setType('fixed');

        // # Payment definitions for this billing plan.
        $paymentDefinition = new PaymentDefinition();

        // The possible values for such setters are mentioned in the setter method documentation.
        // Just open the class file. e.g. lib/PayPal/Api/PaymentDefinition.php and look for setFrequency method.
        // You should be able to see the acceptable values in the comments.
        $paymentDefinition->setName('Regular Payments')
            ->setType('REGULAR')
            ->setFrequency('Month')
            ->setFrequencyInterval("2")
            ->setCycles("12")
            ->setAmount(new Currency(array('value' => 100, 'currency' => 'USD')));

        // Charge Models
        $chargeModel = new ChargeModel();
        $chargeModel->setType('SHIPPING')
            ->setAmount(new Currency(array('value' => 10, 'currency' => 'USD')));

        $paymentDefinition->setChargeModels(array($chargeModel));

        $merchantPreferences = new MerchantPreferences();
        // $baseUrl = getBaseUrl();
        // ReturnURL and CancelURL are not required and used when creating billing agreement with payment_method as "credit_card".
        // However, it is generally a good idea to set these values, in case you plan to create billing agreements which accepts "paypal" as payment_method.
        // This will keep your plan compatible with both the possible scenarios on how it is being used in agreement.
        $merchantPreferences->setReturnUrl("http://www.return.com")
            ->setCancelUrl("http://www.cancel.com")
            ->setAutoBillAmount("yes")
            ->setInitialFailAmountAction("CONTINUE")
            ->setMaxFailAttempts("0")
            ->setSetupFee(new Currency(array('value' => 1, 'currency' => 'USD')));


        $plan->setPaymentDefinitions(array($paymentDefinition));
        $plan->setMerchantPreferences($merchantPreferences);

// For Sample Purposes Only.
        $request = clone $plan;

//        var_dump(get_class($request));
//        die;
// ### Create Plan
        try {
            $output = $plan->create($apiContext);
        } catch (Exception $ex) {


//            ResultPrinter::printError("Created Plan", "Plan", null, $request, $ex);
            exit(1);
        }

//        ResultPrinter::printResult("Created Plan", "Plan", $output->getId(), $request, $output);

        var_dump($output);
        die;
//        return $output;
    }

    /**
     * Test use paypal restful api to create a plan
     *
     *
     *
     * Tests:
     *      1.
     */ 
    public function test_create_a_plan()
    {
        $client = $this->guzzleClientProvider();
        
        $accessToken = $this->getAccessToken($client);


        $request = $client->createRequest(
            'POST',
            'https://api.sandbox.paypal.com/v1/payments/billing-plans', [
            'headers'   =>  [
                'Content-Type'	=>	'application/json',
                'Authorization'	=>	"Bearer {$accessToken}",
            ],

            'json'  =>   [
                "name"          =>  "T-Shirt of the Month Club Plan",
                "description"   =>  "Template creation.",
                "type"          =>  "FIXED",
                "payment_definitions"   =>  [
                    "name"  =>  "Regular Payments",
                    "type"  =>  "REGULAR",
                    "frequency" =>  "MONTH",
                    "frequency_interval"    =>   "1",
                    "amount"=> [
                        "value" =>  "100",
                        "currency"  => "USD"
                    ],
                    "cycles"    =>  "12",
                    "charge_models" =>  [
                        [
                            "type"  =>  "SHIPPING",
                            "amount"    =>  [
                                "value" => "10",
                                "currency"=> "USD"
                            ]
                        ],
                        [
                            "type"  => "TAX",
                            "amount"=> [
                                "value"=> "12",
                                "currency"=> "USD"
                            ]
                        ]
                    ]
                ],
                "merchant_preferences"  => [
                    "setup_fee"=> [
                        "value"=> "1",
                        "currency"=> "USD"
                    ],
                    "return_url"=> "http://www.return.com",
                    "cancel_url"=> "http://www.cancel.com",
                    "auto_bill_amount"=> "YES",
                    "initial_fail_amount_action"=> "CONTINUE",
                    "max_fail_attempts"=> "3"
                ]
            ],
            'config'	=>
			[
                'curl'	=>
                    [
                        CURLOPT_SSL_VERIFYPEER	=> 0,
                        CURLOPT_RETURNTRANSFER	=> 1,
                        CURLOPT_VERBOSE			=> 1
                    ]
            ],
        ]);

//        echo $request->getBody();
//        die;
        // create a plan
        $response = $client->send($request);
//
//        $response_arr =json_decode( $response->getBody()->getContents(), true );
//
//        var_dump($response_arr);

    }
}