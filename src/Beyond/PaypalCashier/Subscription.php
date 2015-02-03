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

//use Beyond\Core\BaseEloquentModel as BaseModel;
use Illuminate\Database\Eloquent\Model;
use PayPal\Api\Agreement;
use Beyond\PaypalCashier\Transformers\SubscriptionTransformer;

/**
 * @author Bryan Huang
 * @version 0.0.0
 *
 * Paypal 的 subscription 是 agreement. 這邊使用 Paypal\Api\Agreement 來實作.
 * 每一種對 restful api 的操作都需要用到 $apiContext 物件，Subscription 本身不生成 $apiContext 物件
 * 由 SubscriptionRepository 傳送進來。
 *
 */
 class Subscription extends Model
 {

     /**
      * {@inheritdoc}
      *
      * @var array
      */
     protected $fillable = array(
         'subscription_id',
         'name',
         'description',
         'start_date'
     );

     /**
      * {inheritdoc}
      *
      * @var string
      */
    protected $table = "paypal_subscription";

     /**
      * Instance of Paypal\Api\Agreement.
      *
      * @var Paypal\Api\Agreement
      */
     protected $paypalAgreement;

     /**
      * Instance of Beyond\PayalCashier\Plan
      *
      * @var Beyond\PaypalCashier\Plan
      */
     protected $plan;

     /**
      * Instance of PayPal\Api\Payer
      *
      * @var PayPal\Api\Payer
      */
     protected $payer;

     /**
      * Instance of PayPal\Api\ShippingAddress.
      *
      * @todo deprecate, should be included in "Beyond\PaypalCashier\Customer" object
      * @var PayPal\Api\ShippingAddress
      */
     protected $shippingAddress;

     /**
      * Construct.
      *
      * @param array $attributes
      */
    public function __construct(array $attributes = array())
    {
        parent::__construct($attributes);

        $this->setSdkSubscription(
            $this->getNewPaypalSubscription($attributes)
        );

    }

     /**
      * Set sdk subscription.
      *
      * @param Paypal\Api\Agreement
      */
     public function setSdkSubscription($agreement)
     {
        $this->paypalAgreement= $agreement;
     }

     /**
      * Get sdk subscription.
      *
      * @return Paypal\Api\Agreement
      */
     public function getSdkSubscription()
     {
        return $this->paypalAgreement;
     }

     /**
      * Instantiate a new instance of PayPal\Api\Agreement.
      *
      * @param array $attributes
      * @return Paypal\Api\Agreement
      */
     protected function getNewPaypalSubscription(array $attributes = array())
     {
         $paypalAgreement = new Agreement();

         if(!empty($attributes))
         {
             $this->initPaypalAgreement($paypalAgreement, $attributes);
         }

         return $paypalAgreement;
     }

     /**
      * Initialize paypal agrement.
      *
      * @param Paypal\Api\PaypalAgreement
      * @param array $attributes
      */
     protected function initPaypalAgreement($paypalAgreement, $attributes)
     {
         foreach ($attributes as $index => $value) {

             $method = ucfirst(studly_case($index));

             if (method_exists($paypalAgreement, "set" . $method)) {
                 $paypalAgreement->{"set{$method}"}($value);
             }
         }
     }

     /**
      * Initialize a new Beyond\PaypalCashier\Subscription.
      *
      * @todo 思考一下是否要設定
      * @param array $attributes
      * @return Beyond\PaypalCashier\Plan
      */
     public function newSubscription(array $attributes = array())
     {
         // 取得新的 Paypal\Api\Agreement
         $agreement = $this->getNewPaypalSubscription($attributes);

         $paypalSubscription = $this->newInstance($attributes);

         $paypalSubscription->setSdkSubscription($agreement);

         return $paypalSubscription;
     }

     /**
      * Get Paypal agreement id.
      *
      * @return string $id
      */
     public function getId()
     {
         return $this->getSdkSubscription()->getId();
     }

     /**
      * Set Paypal agreement id.
      *
      * @param string $id
      */
     public function setId($id)
     {
        $this->getSdkSubscription()->setId($id);
     }

     /**
      * Get the name of the agreement.
      *
      * @return string
      */
     public function getName()
     {
         return $this->getSdkSubscription()->getName();
     }

     /**
      * Get the description of the agreement.
      *
      * @return string
      */
     public function getDescription()
     {
         return $this->getSdkSubscription()->getDescription();
     }

     /**
      * Get the start date of the agreement.
      *
      * @return string
      */
     public function getStartDate()
     {
         return $this->getSdkSubscription()->getStartDate();
     }

     /**
      * Set plan.
      *
      * @param Beyond\PaypalCashier\Plan
      */
     public function setPlan($plan)
     {
        // 會這樣實作是為了解決 sdk 中的 bug.
         // 取得 plan id
         $planId = $plan->getSdkPlan()->getId();

         // 建立一個新的 sdk plan instance
         $newSdkPlanInstance = $plan->getNewPaypalPlan()->setId($planId);

         $this->getSdkSubscription()->setPlan($newSdkPlanInstance);
     }

     /**
      * Get plan.
      *
      * @return Beyond\PaypalCashier\Plan
      */
     public function getPlan()
     {
        return $this->getSdkSubscription()->getPlan();
     }

     /**
      * Set payer.
      *
      * @todo param change to Beyond\PaypalCashier\Customer
      * @param PayPal\Api\Payer
      */
     public function setPayer($payer)
     {
        $this->getSdkSubscription()->setPayer($payer);
     }

     /**
      * Get payer.
      *
      * @return PayPal\Api\Payer
      */
     public function getPayer()
     {
        return $this->getSdkSubscription()->getPayer();
     }

     /**
      * Set shipping address.
      *
      * @todo param change to Beyond\PaypalCashier\Customer
      * @param PayPal\Api\ShippingAddress
      */
     public function setShippingAddress($shippingAddress)
     {
        $this->getSdkSubscription()->setShippingAddress($shippingAddress);
     }

     /**
      * Get shipping address.
      *
      * @return PayPal\Api\ShippingAddress
      */
     public function getShippingAddress()
     {
        return $this->getSdkSubscription()->getShippingAddress();
     }

     /**
      * Get links.
      *
      * @return
      */
     public function getLinks()
     {
        return $this->getSdkSubscription()->getLinks();
     }

     public function getState()
     {
         return $this->getSdkSubscription()->getState();
     }

     /**
      * Create subscription.
      *
      * $subscription = new Subscription([
      *     'name'          =>  '...'
      *     'description'   =>  '...'
      *     'startDate'     =>  Carbon::...
      * ])
      *
      * $subscription->setPlan($plan);
      * $subscription->setPayer($payer);
      * $subscription->createSubscription($apiContext);
      *
      * 在 Agreement 建立好的那一刻，其 state 就已經為 Active。不需要再另外 activate
      *
      *
      * @param PayPal\Rest\ApiContext
      * @return Beyond\PaypalCashier\Subscription
      */
    public function createSubscription($apiContext)
    {
        $agreement = $this->getSdkSubscription()->create($apiContext);

        // 先將 Agreement 跟 Subscription 的格式統一
        $attributes = SubscriptionTransformer::transform($agreement);


        // 將此 instance 使用 $attributes 初始化
        $this->fill($attributes);

        // 存入 DB
        $this->save();

        return $this;
    }

     /**
      * Get subscription by id.
      *
      * @param Paypal\Api\ApiContext
      * @param string $id
      * @return Beyond\PaypalCashier\Subscription
      */
     public function getBySubscriptionId($id, $apiContext)
     {

        // 使用 Paypal\Api\Subscription 中的 static method get.
        $sdkSubscription = forward_static_call_array([$this->getSdkSubscription(), 'get'], [$id, $apiContext]);

        // 統一屬性資料
        $attributes = SubscriptionTransformer::transform($sdkSubscription);

        $paypalSubscription  = $this->newSubscription($attributes);

        $paypalSubscription->setSdkSubscription($sdkSubscription);

        return $paypalSubscription;
     }
 }

