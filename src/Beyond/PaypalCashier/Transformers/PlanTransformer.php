<?php namespace Beyond\PaypalCashier\Transformers;

use PaypPal\Api\Plan as PaypalPlan;

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
 *
 * @todo add typehint
 * This class provides a uniformed transforming definations between "Paypal\Api\Plan" and  "Beyond\PaypalCashier\Plan"
 */
class PlanTransformer
{
    public static function transform($plan)
    {
        return array(
            'plan_id'       =>  $plan->getId(),
            'name'          =>  $plan->getName(),
            'type'          =>  $plan->getType(),
            'state'         =>  $plan->getState(),
            'description'   =>  $plan->getDescription(),
        );
    }
}