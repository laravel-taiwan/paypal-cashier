<?php namespace Beyond\PaypalCashier\Transformers;

class SubscriptionTransformer
{
    public static function transform($agreement)
    {
        return array(
            'subscription_id'   =>  $agreement->getId(),
            'name'              =>  $agreement->getName(),
            'description'       =>  $agreement->getDescription(),
            'start_date'        =>  $agreement->getStartDate()
        );
    }
}