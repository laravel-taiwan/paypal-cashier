<?php

Route::get('/newAgreement', [
    'uses'  =>  'Beyond\PaypalCashier\Controllers\SubscriptionController@createAgreement',
]);