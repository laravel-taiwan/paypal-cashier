<?php

use Beyond\PaypalCashier\Plan;
use Illuminate\Database\Seeder;

class PaypalPlanTableSeeder extends Seeder {

    public function run()
    {

        $plan = Plan::create([
            'title'                         =>  'daily',
            'name'                          =>  'daily',
            'slug'                          =>  'daily',
            'plan_name'                     =>  'daily',
            'total_billing_cycle'           =>  2,
            'billing_period'                =>  'Day',
            'billing_frequency'             =>  1,
            'hasTrial'                      =>  false,
            'initamt'                       => '5',
            'AUTOBILLOUTAMT'                => 'AddToNextBilling',
            // 'trial_total_billing_cycle'      =>  1,
            // 'trial_billing_period'           =>  'SemiMonth',
            // 'trial_billing_frequency'        =>  1,
            'amount'                        =>  '20'
        ]);


        $plan = Plan::create([
            'title'                         =>  'monthly',
            'name'                          =>  'monthly',
            'slug'                          =>  'monthly',
            'plan_name'                     =>  'monthly',
            'total_billing_cycle'           =>  12,
            'billing_period'                =>  'Month',
            'billing_frequency'             =>  1,
            'hasTrial'                      =>  false,
            'AUTOBILLOUTAMT'                => 'AddToNextBilling',
            // 'trial_total_billing_cycle'      =>  1,
            // 'trial_billing_period'           =>  'SemiMonth',
            // 'trial_billing_frequency'        =>  1,
            'amount'                        =>  '20'
        ]);


        $plan = Plan::create([
            'title'                         =>  'yearly',
            'name'                          =>  'yearly',
            'slug'                          =>  'yearly',
            'plan_name'                     =>  'yearly',
            'total_billing_cycle'           =>  1,
            'billing_period'                =>  'Year',
            'billing_frequency'             =>  1,
            'hasTrial'                      =>  true,
            'trial_total_billing_cycle'     =>  1,
            'trial_billing_period'          =>  'SemiMonth',
            'trial_billing_frequency'       =>  1,
            'amount'                        =>  '10'
        ]);
    }

}
