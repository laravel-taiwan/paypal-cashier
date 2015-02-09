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

use PayPal\Handler\OauthHandler;
use PayPal\Rest\ApiContext;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Api\PaymentDefinition;
use PayPal\Api\MerchantPreferences;
use PayPal\Api\Currency;
use PayPal\Api\ChargeModel;

/**
 * Controller 不直接使用 Beyond\PaypalCashier\Plan, 而是使用 PlanRepository.
 *
 * 1. $planRepo->create($plan, $paymentDefination, $merchantPreference) // instance of Paypal\Rest\Plan
 * 2. $planRepo->getNewPlan()->withPaymentDefination($paymentDefination)->withMerchantPreference($merchantPreference)->save()
 * 3. $planRepo->create($plan, $paymentDefination, $merchantPreference);
 * 4. $planRepo->update($plan, $paymentDefination, $merchantPreference);
 * 5. $planRepo->getWithId('...');
 * 6. $planRepo->getWithName('...');
 * 5. $planRepo->deletePlanWithId('...')
 *
 */
class PlanRepository
{

    /**
     * Instance of Beyond\PaypalCashier\Plan
     *
     * @var Beyond\PaypalCashier\Plan
     */
    protected $plan;

    /**
     * Instance of Paypal\Rest\ApiContext
     *
     * @todo move to BaseManager
     * @var Paypal\Rest\ApiContext
     */
    protected $apiContext;

    /**
     * Construct.
     *
     * @todo TypeHint Beyond\PaypalCashier\PlanInterface
     * @todo Pass in restful credentials instead of ApiContext
     * @param Beyond\PaypalCashier\Plan
     * @param Paypal\Rest\ApiContext
     */
    public function __construct(Plan $plan, ApiContext $apiContext)
    {
        $this->plan = $plan;

        // 移到 PaypalBaseRepository
        $this->setApiContext($apiContext);

        $this->apiContext = $apiContext;
    }

    /**
     * Set ApiContext for requesting rest api.
     *
     * @todo move to BasePaypalRepository
     * @param Paypal\Rest\ApiContext
     */
    public function setApiContext($apiContext)
    {
        $this->apiContext = $apiContext;
    }

    /**
     * Get ApiContext object.
     *
     * @todo move to BasePaypalRepository
     * @return Paypal\Rest\ApiContext
     */
    public function getApiContext()
    {
        return $this->apiContext;
    }

    /**
     * Set api context config.
     *
     * @todo move to Beyond\PaypalCashier\BaseManager
     */
    public function setApiContextConfig(array $config = array())
    {
        $this->apiContext->setConfig($config);
    }

    /**
     * Get new plan.
     *
     * @param array $attributes
     * @return Beyond\PaypalCashier\Plan
     */
    public function getNewPlan(array $attributes)
    {
        return $this->plan = $this->plan->newPlan($attributes);
    }

    /**
     * Create new plan. I want to fallow the create pattern as laravel does.
     *
     * $plan = [
     *  'name'          =>  '...',
     *  'description'   =>  '...',
     *  'type'          =>  '...',
     * ];
     *
     *
     * @todo $plan type hint Beyond\PaypalCashier\PlanInterface
     *
     * @param Beyond\PaypalCashier\Plan | array $plan
     * @param Paypal\Api\PaymentDefination
     * @param Paypay\Api\MerchantPreference
     *
     * @return Beyond\PaypalCashier\Plan
     */
    public function createPlan($plan, $paymentDefination = NULL, $merchantPreference = NULL)
    {
        // if $plan is provided in the form of array, we first get new plan.
        if(is_array($plan))
        {
            $plan = $this->getNewPlan($plan);
        }

        if(!is_null($paymentDefination)) $plan->withPaymentDefinations($paymentDefination);

        if(!is_null($merchantPreference)) $plan->withMerchantPreferences($merchantPreference);

        $plan->createPlan($this->getApiContext());

        return $this->plan = $plan;
    }

    /**
     * Update current plan.
     *
     * $repo->updatePlan($plan, $patchRequest); Beyond\PaypalCashier\Plan
     *
     * @param Beyond\PaypalCashier\Plan
     * @param PayPal\Api\PatchRequest
     * @return Beyond\PaypaCashier\Plan
     */
    public function updatePlan($plan, $patchRequest)
    {
        // update request
        $plan = $plan->updatePlan($patchRequest, $this->getApiContext());

        return $plan;
    }

    /**
     * Get plan by plan uid.
     *
     * @param string $id
     */
    public function getPlanById($id)
    {
        return $this->plan->getByPlanId($id, $this->getApiContext());
    }
}