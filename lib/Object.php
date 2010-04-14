<?php
abstract class Zuora_Object
{
    const TYPE_NAMESPACE = 'http://object.api.zuora.com/';
    
    protected $zType = 'zObject';
    
    protected $_data = array();
        
	/**
     * 
     * @param $name string
     * @param $value mixed
     * @return void 
     */
    public function __set($name, $value)
    {
        $this->_data[$name] = $value;
    }
    
    public function getSoapVar()
    {
        return new SoapVar(
            (array)$this->_data,
            SOAP_ENC_OBJECT,
            $this->zType,
            self::TYPE_NAMESPACE
        );
    }
}
