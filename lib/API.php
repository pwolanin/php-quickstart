<?php
/**
 * Zuora PHP Library
 *  
 * This class implements singleton pattern and allows user to call
 * any of Zuora's API Calls except for login which will be called
 * automatically prior to any other call
 */

require_once 'Object.php';
require_once 'Account.php';
require_once 'Amendment.php';
require_once 'Contact.php';
require_once 'Debug.php';
require_once 'PaymentMethod.php';
require_once 'Product.php';
require_once 'ProductRatePlan.php';
require_once 'ProductRatePlanCharge.php';
require_once 'RatePlan.php';
require_once 'RatePlanCharge.php';
require_once 'RatePlanData.php';
require_once 'SubscribeRequest.php';
require_once 'Subscription.php';
require_once 'SubscriptionData.php';
require_once 'Usage.php';
require_once 'Invoice.php';
require_once 'SubscribeOptions.php';
require_once 'Payment.php';
require_once 'InvoicePayment.php';

class Zuora_API
{
    /**
     * Singleton instance
     *
     * Marked only as protected to allow extension of the class. To extend,
     * simply override {@link getInstance()}.
     *
     * @var Zuora_API
     */
    protected static $_instance = null;
    
    protected static $_config = null;
    
    /**
     * Soap Client
     * 
     * @var SoapClient
     */
    protected $_client;
    
    /**
     * 
     * @var SoapHeader
     */
    protected $_header;

    protected $_endpoint = null;
    
	/**
     * Constructor
     *
     * Instantiate using {@link getInstance()}; API is a singleton
     * object.
     *
     * @return void
     */
    protected function __construct($config)
    {
        self::$_config = $config;

        $this->_client = new SoapClient(self::$_config->wsdl, 
            array(
                'soap_version'=>SOAP_1_1,
                'trace'=>1,
            )
        );
    }

    
    public function login($username, $password)
    {
        if ($this->_endpoint){
            $this->setLocation($this->_endpoint);
        }
        try {
            $result = $this->_client->login(array('username'=>$username, 'password'=>$password));
        } catch (Exception $e) {
            Zuora_Debug::dump($e);
            Zuora_Debug::dump($this->_client->__getLastRequestHeaders(), 'REQUEST HEADERS');
            Zuora_Debug::dump($this->_client->__getLastRequest(), 'REQUEST');
            Zuora_Debug::dump($this->_client->__getLastResponseHeaders(), 'RESPONSE HEADERS');
            Zuora_Debug::dump($this->_client->__getLastResponse(), 'RESPONSE');
            return false;
        }
        $header = new SoapHeader(
        	'http://api.zuora.com/',
        	'SessionHeader',
            array(
            	'session'=>$result->result->Session
            )
        );
        $this->addHeader($header);
        $this->_client->__setLocation($result->result->ServerUrl);
        return true;
    }

    public function clearHeaders(){
        $this->_header = null;
    }

    public function addHeader($hdr){
        if (!$this->_header){
            $this->_header = array();
        }
        $this->_header[] = $hdr;
    }

    public function setQueryOptions($batchSize){
        $header = new SoapHeader(
        	'http://api.zuora.com/',
        	'QueryOptions',
            array(
            	'batchSize'=>$batchSize
            )
        );
        $this->addHeader($header);
    }

    public function setQueueHeader($resultEmail, $userId){
        $header = new SoapHeader(
        	'http://api.zuora.com/',
        	'QueueHeader',
            array(
            	'resultEmail'=>$resultEmail,
            	'userId'=>$userId
            )
        );
        $this->addHeader($header);
    }

    public function setLocation($endpoint){
        $this->_endpoint = $endpoint;
        $this->_client->__setLocation($this->_endpoint);
    }
    
    public function subscribe(
        Zuora_Account $zAccount,
        Zuora_Contact $zBillToContact,
        Zuora_PaymentMethod $zPaymentMethod,
        Zuora_SubscriptionData $zSubscriptionData,        
        Zuora_SubscribeOptions $zSubscribeOptions=null,
        Zuora_Contact $zSoldToContact=null
    )
    {

        
        $subscribeRequest = array(
            'Account'=>$zAccount->getSoapVar(),
            'BillToContact'=>$zBillToContact->getSoapVar(),
            'PaymentMethod'=>$zPaymentMethod->getSoapVar(),
            'SubscriptionData'=>$zSubscriptionData->getSoapVar(),
        );
        
        if (isset($zSoldToContact)) {
            $subscribeRequest['SoldToContact'] = $zSoldToContact->getSoapVar();
        }
        if (isset($zSubscribeOptions)) {
            $subscribeRequest['SubscribeOptions'] = $zSubscribeOptions->getSoapVar();
        }    

        try {
            $result = $this->_client->__soapCall("subscribe", array('zObjects'=>array($subscribeRequest)), null, $this->_header);   
            //Zuora_Debug::dump($result);
            
            $success = $result->result->Success;
        } catch (Exception $e) {
            Zuora_Debug::dump($e);
            Zuora_Debug::dump($this->_client->__getLastRequestHeaders(), 'REQUEST HEADERS');
            Zuora_Debug::dump($this->_client->__getLastRequest(), 'REQUEST');
            Zuora_Debug::dump($this->_client->__getLastResponseHeaders(), 'RESPONSE HEADERS');
            Zuora_Debug::dump($this->_client->__getLastResponse(), 'RESPONSE');
            return false;
        }

        //Zuora_Debug::dump($this->_client->__getLastRequest(), 'REQUEST');
        //Zuora_Debug::dump($this->_client->__getLastResponse(), 'RESPONSE');
        return $result;
    }
    public function subscribeWithExistingAccount(
        Zuora_Account $zAccount,
        Zuora_SubscriptionData $zSubscriptionData,
        Zuora_SubscribeOptions $zSubscribeOptions=null
    )
    {

        
        $subscribeRequest = array(
            'Account'=>$zAccount->getSoapVar(),
            'SubscriptionData'=>$zSubscriptionData->getSoapVar(),
        );

        if (isset($zSubscribeOptions)) {
            $subscribeRequest['SubscribeOptions'] = $zSubscribeOptions->getSoapVar();
        }
        
        try {
            $result = $this->_client->__soapCall("subscribe", array('zObjects'=>array($subscribeRequest)), null, $this->_header);   
            //Zuora_Debug::dump($result);
            
            $success = $result->result->Success;
        } catch (Exception $e) {
            Zuora_Debug::dump($e);
            Zuora_Debug::dump($this->_client->__getLastRequestHeaders(), 'REQUEST HEADERS');
            Zuora_Debug::dump($this->_client->__getLastRequest(), 'REQUEST');
            Zuora_Debug::dump($this->_client->__getLastResponseHeaders(), 'RESPONSE HEADERS');
            Zuora_Debug::dump($this->_client->__getLastResponse(), 'RESPONSE');
            return false;
        }

        //Zuora_Debug::dump($this->_client->__getLastRequest(), 'REQUEST');
        //Zuora_Debug::dump($this->_client->__getLastResponse(), 'RESPONSE');
        return $result;
    }    
    public function create(array $zObjects)
    {
        if (count($zObjects) > 50) {
            throw new Exception('ERROR: create only supports up to 50 objects'); 
        }
        $soapVars = array();
        $type = 'Zuora_Object';
        foreach ($zObjects as $zObject) {
            if ($zObject instanceof $type) {
                $type = get_class($zObject);
                $soapVars[] = $zObject->getSoapVar();
            } else {
                throw new Exception('ERROR: all objects must be of the same type');
            }
        }
        $create = array(
       		"zObjects"=>$soapVars
        );
        try {
            $result = $this->_client->__soapCall("create", $create, null, $this->_header);
        } catch (Exception $e) {
            Zuora_Debug::dump($e);
            Zuora_Debug::dump($this->_client->__getLastRequestHeaders(), 'REQUEST HEADERS');
            Zuora_Debug::dump($this->_client->__getLastRequest(), 'REQUEST');
            Zuora_Debug::dump($this->_client->__getLastResponseHeaders(), 'RESPONSE HEADERS');
            Zuora_Debug::dump($this->_client->__getLastResponse(), 'RESPONSE');
        }
        return $result;
    }

		public function generate(array $zObjects)
    {
        if (count($zObjects) > 50) {
            throw new Exception('ERROR: generate only supports up to 50 objects'); 
        }
        $soapVars = array();
        $type = 'Zuora_Object';
        foreach ($zObjects as $zObject) {
            if ($zObject instanceof $type) {
                $type = get_class($zObject);
                $soapVars[] = $zObject->getSoapVar();
            } else {
                throw new Exception('ERROR: all objects must be of the same type');
            }
        }
        $generate = array(
       		"zObjects"=>$soapVars
        );
        try {
            $result = $this->_client->__soapCall("generate", $generate, null, $this->_header);
        } catch (Exception $e) {
            Zuora_Debug::dump($e);
            Zuora_Debug::dump($this->_client->__getLastRequestHeaders(), 'REQUEST HEADERS');
            Zuora_Debug::dump($this->_client->__getLastRequest(), 'REQUEST');
            Zuora_Debug::dump($this->_client->__getLastResponseHeaders(), 'RESPONSE HEADERS');
            Zuora_Debug::dump($this->_client->__getLastResponse(), 'RESPONSE');
        }
        return $result;
    }

    public function update(array $zObjects)
    {
        if (count($zObjects) > 50) {
            throw new Exception('ERROR: update only supports up to 50 objects'); 
        }
        $soapVars = array();
        $type = 'Zuora_Object';
        foreach ($zObjects as $zObject) {
            if ($zObject instanceof $type) {
                $type = get_class($zObject);
                $soapVars[] = $zObject->getSoapVar();
            } else {
                throw new Exception('ERROR: all objects must be of the same type');
            }
        }
        $update= array(
       		"zObjects"=>$soapVars
        );
        try {
            $result = $this->_client->__soapCall("update", $update, null, $this->_header);
        } catch (Exception $e) {
            Zuora_Debug::dump($e);
            Zuora_Debug::dump($this->_client->__getLastRequestHeaders(), 'REQUEST HEADERS');
            Zuora_Debug::dump($this->_client->__getLastRequest(), 'REQUEST');
            Zuora_Debug::dump($this->_client->__getLastResponseHeaders(), 'RESPONSE HEADERS');
            Zuora_Debug::dump($this->_client->__getLastResponse(), 'RESPONSE');
        }
        return $result;
    }

    public function delete($type, $ids)
    {

        $delete = array(
   		"type"=>$type,
   		"ids"=>$ids,
        );
        $deleteWrapper = array(
   		"delete"=>$delete
        );

        try {
            $result = $this->_client->__soapCall("delete", $deleteWrapper, null, $this->_header);
        } catch (Exception $e) {
            Zuora_Debug::dump($e);
            Zuora_Debug::dump($this->_client->__getLastRequestHeaders(), 'REQUEST HEADERS');
            Zuora_Debug::dump($this->_client->__getLastRequest(), 'REQUEST');
            Zuora_Debug::dump($this->_client->__getLastResponseHeaders(), 'RESPONSE HEADERS');
            Zuora_Debug::dump($this->_client->__getLastResponse(), 'RESPONSE');
        }
        return $result;
    }

    public function execute($type, $syncronous, $ids)
    {

        $execute = array(
   		"type"=>$type,
   		"synchronous"=>$syncronous,
   		"ids"=>$ids,
        );
        $executeWrapper = array(
   		"execute"=>$execute
        );

        try {
            $result = $this->_client->__soapCall("execute", $executeWrapper, null, $this->_header);
        } catch (Exception $e) {
            Zuora_Debug::dump($e);
            Zuora_Debug::dump($this->_client->__getLastRequestHeaders(), 'REQUEST HEADERS');
            Zuora_Debug::dump($this->_client->__getLastRequest(), 'REQUEST');
            Zuora_Debug::dump($this->_client->__getLastResponseHeaders(), 'RESPONSE HEADERS');
            Zuora_Debug::dump($this->_client->__getLastResponse(), 'RESPONSE');
        }
        return $result;
    }

    public function query($zoql)
    {

        $query = array(
   		"queryString"=>$zoql
        );
        $queryWrapper = array(
   		"query"=>$query
        );

        try {
            $result = $this->_client->__soapCall("query", $queryWrapper, null, $this->_header);
        } catch (Exception $e) {
            Zuora_Debug::dump($e);
            Zuora_Debug::dump($this->_client->__getLastRequestHeaders(), 'REQUEST HEADERS');
            Zuora_Debug::dump($this->_client->__getLastRequest(), 'REQUEST');
            Zuora_Debug::dump($this->_client->__getLastResponseHeaders(), 'RESPONSE HEADERS');
            Zuora_Debug::dump($this->_client->__getLastResponse(), 'RESPONSE');
        }
        return $result;
    }

    public function queryMore($zoql)
    {

        $query = array(
   		"queryLocator"=>$zoql
        );
        $queryWrapper = array(
   		"queryMore"=>$query
        );

        try {
            $result = $this->_client->__soapCall("queryMore", $queryWrapper, null, $this->_header);
        } catch (Exception $e) {
            Zuora_Debug::dump($e);
            Zuora_Debug::dump($this->_client->__getLastRequestHeaders(), 'REQUEST HEADERS');
            Zuora_Debug::dump($this->_client->__getLastRequest(), 'REQUEST');
            Zuora_Debug::dump($this->_client->__getLastResponseHeaders(), 'RESPONSE HEADERS');
            Zuora_Debug::dump($this->_client->__getLastResponse(), 'RESPONSE');
        }
        return $result;
    }
    
	/**
     * Enforce singleton; disallow cloning 
     * 
     * @return void
     */
    private function __clone()
    {
    }
    
	/**
     * Singleton instance
     *
     * @return Zuora_API
     */
    public static function getInstance($config)
    {
        if (null === self::$_instance || $config != self::$_config) {
            self::$_instance = new self($config);
        }
        return self::$_instance;
    }
}
