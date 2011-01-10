<?php
class Zuora_Amendment extends Zuora_Object
{
    protected $zType = 'Amendment';

    public function getSoapVar()
    {
        if (isset($this->RatePlanData))
        {
          $data = array_merge((array)$this->_data, array('RatePlanData'=>$this->RatePlanData->getSoapVar()));
          return new SoapVar(
            $data,
            SOAP_ENC_OBJECT,
            $this->zType,
            self::TYPE_NAMESPACE
          );
        }
        else
        {
          parent::getSoapVar();
        }
    }
}
