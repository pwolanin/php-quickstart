<?php
class Zuora_ProductRatePlanChange extends Zuora_Object
{
    protected $zType = 'ProductRatePlanChange';

    public function __construct()
    {
        $this->_data = array(
            'ProductRatePlanId'=>null,
        );
    }
}
