<?php 

use Beyond\PaypalCashier\Account;
use Illuminate\Database\Seeder;
// Composer: "fzaninotto/faker": "v1.4.0"
// use Faker\Factory as Faker;

class AccountTableSeeder extends Seeder {

    public function run()
    {
        // var_dump('account table');
        // die;
        Account::create([
            'title' =>  'sample account',
            'name'  =>  'sample account '
        ]);

        Account::create([
            'title' =>  'sample account2',
            'name'  =>  'sample account2 '
        ]);
    }

}
