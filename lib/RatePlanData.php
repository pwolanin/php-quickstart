<?php
class Zuora_RatePlanData extends Zuora_Object
{
    const TYPE_NAMESPACE = 'http://api.zuora.com/';
    
    protected $zType = 'RatePlanData';
    
    /**
     * @var Zuora_RatePlan
     */
    public $zRatePlan;
    
    /**
     * @var array
     */
    private $_ratePlanChargeObjects = array();
    
    public function __construct(Zuora_RatePlan  $zRatePlan = null)
    {
        if (isset($zRatePlan)) {
            $this->zRatePlan = $zRatePlan;
        } else {
            $this->zRatePlan = new Zuora_RatePlan();
        }
    }
    
    public function addRatePlanCharge(Zuora_RatePlanCharge $zRatePlanCharge)
    {
        $this->_ratePlanChargeObjects[] = $zRatePlanCharge;
    }
    
    public function getSoapVar()
    {
        $ratePlanChargeObjects = array();
        foreach ($this->_ratePlanChargeObjects as $object) {
            $ratePlanChargeObjects[] = $object->getSoapVar();
        }
        return new SoapVar(
            array(
                'RatePlan'=>$this->zRatePlan->getSoapVar(),
                'RatePlanCharge'=>$ratePlanChargeObjects,
            ),
            SOAP_ENC_OBJECT,
            $this->zType,
            self::TYPE_NAMESPACE
        );
    }
}
