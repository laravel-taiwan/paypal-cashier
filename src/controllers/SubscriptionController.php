<?php namespace Beyond\PaypalCashier\Controllers;

use Illuminate\Routing\Controller as BaseController;
use Beyond\PaypalCashier\Subscription;
use Carbon\Carbon;



class SubscriptionController extends BaseController{

    /**
     * Instance of Plan Repository
     *
     * @var Beyond\PaypalCashier\PlanRepository
     */
    protected $repo;

    /**
     * Sample plan id.
     *
     * @var
     */
    protected $samplePlanId = 'P-53R82169JN2163459DQ4RFBY';

    /**
     * Create agreement.
     *
     * @return void
     */
    public function createAgreement()
    {
        // find existing plan by plan id
        $planRepo = \App::make('Beyond\PaypalCashier\PlanRepository');

        $plan = $planRepo->getPlanById($this->samplePlanId);


        // create a new agreement
        $subscription = new Subscription([
            'name'          =>  'sample agreement',
            'description'   =>  'sample description',
            'start_date'    =>  Carbon::now()->addDay()->format('Y-m-d\TH:i:s\Z')
        ]);

        // apply agreement settings
//        $this->applySubscriptionSettings($subscription);

        // send agreement,
    }

    /**
     * Apply subscription settings.
     *
     * @param Beyond\PaypalCashier\Subscription
     */
    protected function applySubscriptionSettings($subscription)
    {
//        $subscription->setPlan($plan);
//        $subscription->setPayer($payer);
//        $subscription->setShippingAddress($shippingAddress);
//        $subscription->setLinks();

    }
}