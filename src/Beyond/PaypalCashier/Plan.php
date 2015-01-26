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

use Illuminate\Database\Eloquent\Model;
use Beyond\PaypalCashier\Transformers\PlanTransformer;
use PayPal\Api\PaymentDefinition;
use PayPal\Api\MerchantPreferences;
use PayPal\Api\Plan as PaypalPlan;

/**
 * @author Bryan Huang
 * @version 0.0.0
 *
 * ＠todo 寫一個 autoSycPlan 方法來偵測 Beyond\PaypalCashier\Plan 跟 Paypal\Api\Plan 屬性是否相同
 * 將 plan 資訊存入到 Database 中
 * $plan = new Plan // Beyond\Paypal\Plan
 * $plan->setPaymentDefinitions([$paymentDefination]);
 * $plan->setMerchantPreference($merchantPreferences)
 * $plan->save() // create request rest api, save plan info to database
 */
class Plan extends Model
{

    protected $table = 'paypal_plans';

    /**
     * {@inheritdoc}
     *
     * @param array $fillable
     */
    protected $fillable = array(
        'plan_id',
        'name',
        'description',
        'type',
        'state'
    );

    /**
     * Instance of Paypal\Api\Plan
     *
     * @var Paypal\Api\Plan
     */
    protected $paypalPlan;

    /**
     * Construct.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = array())
    {
        // init model attributes
        parent::__construct($attributes);

        $this->setSdkPlan(
            $this->getNewPaypalPlan($attributes)
        );

    }

    /**
     * Get the instance of Paypal\Api\Plan.
     *
     * @param array $attributes
     * @return Paypal\Api\PaypalPlan
     */
    public function getNewPaypalPlan(array $attributes = array()/*, $sync = TRUE*/)
    {

        $paypalPlan = new PaypalPlan;

        if(!empty($attributes))  $this->initPaypalPlan($paypalPlan, $attributes);

        return $paypalPlan;
    }

    /**
     * Set up PayPal\Api\Plan according to
     *
     * @param Paypal\Api\PaypalPlan
     * @param array $attributes
     * @return void
     */
    protected function initPaypalPlan($paypalPlan, $attributes)
    {
        foreach ($attributes as $index => $value) {
            $method = ucfirst($index);
            if (method_exists($paypalPlan, "set" . $method)) {
                $paypalPlan->{"set{$method}"}($value);
            }
        }
    }

    /**
     * Initialize a new Beyond\PaypalCashier\Plan.
     *
     * @param array $attributes
     * @return Beyond\PaypalCashier\Plan
     */
    public function newPlan(array $attributes = array())
    {
        // 取得新的 Paypal\Api\PaypalPlan
        $paypalPlan = $this->getNewPaypalPlan($attributes);

        $newPlan = $this->newInstance($attributes);

        $newPlan->setSdkPlan($paypalPlan);

        return $newPlan;
    }

    /**
     * Set Paypal\Api\Plan object.
     *
     * @param Paypal\Api\Plan
     */
    public function setSdkPlan($paypalPlan)
    {
        $this->paypalPlan = $paypalPlan;
    }

    /**
     * Get Paypal sdk Plan instance.
     *
     * @return Paypal\Api\Plan
     */
    public function getSdkPlan()
    {
        return $this->paypalPlan;
    }

    /**
     * Set payment defination object. Used for chain calling.
     *
     * @param PayPal\Api\PaymentDefination
     * @return Beyond\PaypalCashier\Plan
     */
    public function withPaymentDefinations(PaymentDefinition $paymentDefination)
    {
        $this->paypalPlan->setPaymentDefinitions([$paymentDefination]);

        return $this;
    }

    /**
     * Set merchant preference object Used for chain calling.
     *
     * @param Paypal\Api\MerchantPreference
     * @return Beyond\PaypalCashier\Plan
     */
    public function withMerchantPreferences(MerchantPreferences $merchantPreference)
    {
        $this->paypalPlan->setMerchantPreferences($merchantPreference);

        return $this;
    }

    /**
     * Set the name of the plan.
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->getSdkPlan()->setName($name);
    }

    /**
     * Get the name of the plan.
     *
     * @return string $name
     */
    public function getName()
    {
        return $this->getSdkPlan()->getName();
    }

    /**
     * Set the description of the plan.
     *
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->getSdkPlan()->setDescription($description);
    }

    /**
     * Get the description of the plan
     *
     * @return string $description
     */
    public function getDescription()
    {
        return $this->getSdkPlan()->getDescription();
    }

    /**
     * Set the type of the plan.
     *
     * @param string $type
     */
    public function setType($type)
    {
        $this->getSdkPlan()->setType($type);
    }

    /**
     * Get the type of the plan.
     *
     * @return string $type
     */
    public function getType()
    {
        return $this->getSdkPlan()->getType();
    }

    /**
     * Get the unique id of the plan returned by Paypal restful api when plan has been successfully created.
     *
     * @return string $id
     */
    public function getId()
    {
        return $this->getSdkPlan()->getId();
    }

    /**
     * Get the state of current plan.
     *
     * @return string
     */
    public function getState()
    {
        return $this->getSdkPlan()->getState();
    }

    /**
     * Create a new plan.
     *
     * $plan = new Plan([...]); // new Plan instance
     *
     * - 只有將基本的 plan 資料存入到 database 中。並沒有使用 Paypal restful 建立 plan
     * $plan->save();
     * $plan->createPlan($apiContext) // 這樣才會建立
     *
     * - 建立
     * $plan->withPaymentDefination(...)->withMerchantPreference(...)->createPlan($apiContext);
     *
     * @todo Handle errors
     * @param Paypal\Rest\ApiContext
     * @return Beyond\PaypalCashier\Plan
     */
    public function createPlan($apiContext)
    {
        // request api 取得 plan.
        $plan = $this->getSdkPlan()->create($apiContext);

        // 轉換資料格式.
        $attributes = PlanTransformer::transform($plan);

        // fillin attributes and save into database.
        $this->fill($attributes)->save();

        // renew sdkPlan
        $this->setSdkPlan($plan);

        return $this;
    }

    /**
     * Update plan information. It seems one the state can be updated. Thus, as of Database, we only need to update "state"
     *
     * $plan = Plan::find(1);
     * $plan->updatePlan($patchRequest, $apiContext);
     *
     * $plan; //existing plan
     *
     * @param PayPal\Api\PatchRequest
     * @param Paypal\Api\ApiContext
     * @param boolean If $sync is set to true, plan instance will be renewed along with database.
     * @return boolean
     */
    public function updatePlan($patchRequest, $apiContext, $sync = TRUE)
    {
        // request paypal restful api, get updated Paypal\Api\Plan
        $updateStatus = $this->getSdkPlan()->update($patchRequest, $apiContext);

        if($updateStatus and $sync)
        {
            // retrieve plan by plan id, Beyond\PaypalCashier\Plan is returned.
            $plan = $this->getByPlanId($this->getId(), $apiContext);

            // @todo 如果要同步 model database 必定會動到。
            $this->fill($plan->toArray())->save();

            $this->setSdkPlan($plan->getSdkPlan());

        }

        return $updateStatus;
    }

    /**
     * Get plan by unique plan id.
     *
     * $plan = new Plan
     *
     * $plan->getByPlanId($id, $apiContext)
     * 兩種做法：
     *  1. $plan 本身 filling attributes
     *  2. 建立新的 plan 再 filling attributes - Laravel 使用這種方法
     *
     * @param string $id
     * @param Beyond\PaypalCashier\ApiContext
     * @return Beyond\PaypalCashier\Plan
     */
    public function getByPlanId($id, $apiContext)
    {
        // 使用 Paypal\Api\Plan 中的 static method get.
        $sdkPlan = forward_static_call_array([$this->getSdkPlan(), 'get'], [$id, $apiContext]);

        // 取得回傳的 Paypal\Api\Plan 後, 我們需要 initialize Beyond\PaypalCashier\Plan
        $attributes = PlanTransformer::transform($sdkPlan);

        // get a new instance of Beyond\PaypalCashier\Plan
        $plan = $this->newPlan($attributes);

        // set sdk plan
        $plan->setSdkPlan($sdkPlan);

        return $plan;
    }
}