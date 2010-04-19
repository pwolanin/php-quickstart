#!/usr/bin/php
<?php 

error_reporting(E_ALL); 
ini_set("display_errors", 1); 

date_default_timezone_set('America/Los_Angeles');

require_once 'lib/API.php';

$config = new stdClass();
$config->wsdl = 'zuora.17.0.wsdl';
$username = '<your username>';
$password = '<your password>';
$endpoint = 'https://www.zuora.com/apps/services/a/17.0';
$accountingCode = "TestNewComponent";

$instance = Zuora_API::getInstance($config);
$instance->setQueryOptions(100);

# LOGIN
$instance->setLocation($endpoint);
$instance->login($username, $password);
$instance->setLocation($endpoint);

# CREATE AN ACTIVE ACCOUNT
$name = "Test" . time();
$id = createActiveAccount($instance, $name);
print "\nAccount Created: " . $id;
# QUERY ACCOUNT
$query = "SELECT Id, AccountNumber, Name FROM Account WHERE name = '".$name."'";
$records = queryAll($instance, $query);
print "\nAccount Queried ($query): " . $records[0]->AccountNumber;

#CREATE some usage
//the minimum required fields
$usage = array("AccountId" => $id,"Quantity" => 1234/1000,"StartDateTime" => '2010-04-08T12:22:22',"UOM" => '1KUSD');
$result = upload_usage($instance,array($usage));
print "\nUsage Created: " . $result->result->Id;

# DELETE ACCOUNT
$result = $instance->delete('Account', array($id));
$success = $result->result->success;
$msg = ($success ? "Success" : $result->result->errors->Code . " (" . $result->result->errors->Message.")");
print "\nAccount Deleted: " . $msg;

# GET PRODUCT IDs FOR SUBSCRIBE
$ProductRatePlanCharge = getChargeByAccountingCode($instance, $accountingCode);
print "\nProductRatePlanCharge Queried (AccountingCode=$accountingCode): " . $ProductRatePlanCharge->Id;

# SUBSCRIBE WILL CREATE ACCOUNT
$result = subscribe($instance, $ProductRatePlanCharge);
$success = $result->result->Success;
$msg = ($success ? $result->result->AccountNumber : $result->result->Errors->Code . " (" . $result->result->Errors->Message.")");
print "\nSubscribe and Invoice&Payment:(AccountNumber) " . $msg;

if($success){
  $accountId = $result->result->AccountId;

  # SUBSCRIBE WITH EXISTING WILL CREATE SUBSCRIPTION ON EXISTING ACCOUNT
  $result = subscribeWithExistingAccount($instance, $ProductRatePlanCharge, $accountId);
  print "\nSubscribe with existing account:";
  $success = $result->result->Success;
	$msg = ($success ? "\nSubscription created\n\tAccount: ".$result->result->AccountId."\n\tSubscriptionNumber: ".$result->result->SubscriptionNumber : $result->result->Errors->Code . " (" . $result->result->Errors->Message.")");
	print $msg;
}

# GENERATE & QUERY & POST INVOICE
print "\nGenerating Invoice...";
$result = subscribe($instance, $ProductRatePlanCharge,false);
$success = $result->result->Success;
$accountId = ($success ? $result->result->AccountId : "");
if($accountId){	
	$invoiceDate = date('Y-m-d\TH:i:s');
	$targetDate = date('Y-m-d\TH:i:s', strtotime('+2 month', strtotime($invoiceDate)));
	$result = generateInvoice($instance,$accountId,$invoiceDate,$targetDate);
	$success = $result->result->Success;
	$msg = ($success ? $result->result->Id : $result->result->Errors->Code . " (" . $result->result->Errors->Message.")");
	print "\nInvoice Created: " . $msg . "\n";
	
	if($success){
	  # QUERY Invoice
	  $query = "SELECT Id, InvoiceNumber,Status FROM Invoice WHERE id = '".$result->result->Id."'";
	  $records = queryAll($instance, $query);
	  print "\nInvoice Queried ($query): " . $records[0]->InvoiceNumber ." ". $records[0]->Status . "\n";
	  
	  # POST Invoice
	  $result = postInvoice($instance,$result->result->Id);
	  $success = $result->result->Success;
	  print "\nInvoice Posted :" .($result->result->Success ? "Success" : $result->result->Errors->Code . " (" . $result->result->Errors->Message.")"); 
  
    if($success){
 		# DO PAYMENT
    	createAndApplyPayment($instance,$accountId);
    }    
	}
}

# useful for debugging responses
# Zuora_Debug::dump($result);

exit;

# method to create an active account. requires that you have:
#
#   1.) a gateway setup, 
#   2.) gateway configured to not verify new credit cards
#
# if you want to verify a new credit card, make sure the card info
# you specify is correct.
function createActiveAccount($instance, $name){

    # Create Draft Account
    $zAccount = new Zuora_Account();
    $zAccount->Name = $name;
    $zAccount->AllowInvoiceEdit = 1;
    $zAccount->AutoPay = 0;
    $zAccount->Batch = 'Batch1';
    $zAccount->BillCycleDay = 1;
    $zAccount->CrmId = 'SFDC-1223471249003';
    $zAccount->Currency = 'USD';
    $zAccount->PaymentTerm = 'Due Upon Receipt';
    $zAccount->PurchaseOrderNumber = 'PO-1223471249003';
    $zAccount->Status = 'Draft';

    $result = $instance->create(array($zAccount));
    $accountId = $result->result->Id;

    # Create Contact on Account
    $zBillToContact = new Zuora_Contact();
    $zBillToContact->AccountId = $accountId;
    $zBillToContact->Address1 = '4901 Morena Blvd';
    $zBillToContact->City = 'San Diego';
    $zBillToContact->Country = 'United States';
    $zBillToContact->FirstName = 'Robert';
    $zBillToContact->LastName = 'Smith';
    $zBillToContact->PostalCode = '92117';
    $zBillToContact->State = 'Virginia';
    $zBillToContact->WorkEmail = 'robert@smith.com';
    $result = $instance->create(array($zBillToContact));
    $contactId = $result->result->Id;

    # Create Payment Method on Account
    $zPaymentMethod = new Zuora_PaymentMethod();
    $zPaymentMethod->AccountId = $accountId;
    $zPaymentMethod->CreditCardAddress1 = '52 Vexford Lane';
    $zPaymentMethod->CreditCardCity = 'Anaheim';
    $zPaymentMethod->CreditCardCountry = 'United States';
    $zPaymentMethod->CreditCardExpirationMonth = '12';
    $zPaymentMethod->CreditCardExpirationYear = '2010';
    $zPaymentMethod->CreditCardHolderName = 'Firstly Lastly';
    $zPaymentMethod->CreditCardNumber = '4111111111111111';
    $zPaymentMethod->CreditCardPostalCode = '22042';
    $zPaymentMethod->CreditCardState = 'California';
    $zPaymentMethod->CreditCardType = 'Visa';
    $zPaymentMethod->Type = 'CreditCard';
    $result = $instance->create(array($zPaymentMethod));
    $paymentMethodId = $result->result->Id;

    # Update Account w/ Bill To/Sold To; also specify as active
    $zAccount = new Zuora_Account();
    $zAccount->Id = $accountId;
    $zAccount->Status = 'Active';
    $zAccount->BillToId = $contactId;
    $zAccount->SoldToId = $contactId;
    $result = $instance->update(array($zAccount));

    return $accountId;

}

# method to query all records of a type.
function queryAll($instance, $query){

    $moreCount = 0;
    $recordsArray = array();
    $totalStart = time();

    $start = time();
    $result = $instance->query($query);
    $end = time();
    $elapsed = $end - $start;

    $done = $result->result->done;
    $size = $result->result->size;
    $records = $result->result->records;

    if ($size == 0){
    } else if ($size == 1){
        array_push($recordsArray, $records);
    } else {

        $locator = $result->result->queryLocator;
        $newRecords = $result->result->records;
        $recordsArray = array_merge($recordsArray, $newRecords);

        while (!$done && $locator && $moreCount == 0){
        
            $start = time();
            $result = $instance->queryMore($locator);
            $end = time();
            $elapsed = $end - $start;
    
            $done = $result->result->done;
            $size = $result->result->size;
            $locator = $result->result->queryLocator;
            print "\nqueryMore";

            $newRecords = $result->result->records;
            $count = count($newRecords);
            if ($count == 1){
                array_push($recordsArray, $newRecords);
            } else {
                $recordsArray = array_merge($recordsArray, $newRecords);
            }
    
        }
    }

    $totalEnd = time();
    $totalElapsed = $totalEnd - $totalStart;

    return $recordsArray;

}

# get ProductRatePlanCharge by AccountingCode.
function getChargeByAccountingCode($instance, $accountingCode){

    $result = $instance->query("select Id, ProductRatePlanId from ProductRatePlanCharge where AccountingCode = '$accountingCode'");
    if ($result->result->size != 1){
        print "No Product Rate Plan Charge found with AccountingCode = '$accountingCode'";
        exit();
    }

    $ProductRatePlanCharge = $result->result->records;
    return $ProductRatePlanCharge;

}

# make subscribe call
function subscribe($instance, $ProductRatePlanCharge,$GenerateInvoiceAndPayments=true,$accountName=null, $subscriptionName=null){


    $ProductRatePlanChargeId = $ProductRatePlanCharge->Id;
    $ProductRatePlanId = $ProductRatePlanCharge->ProductRatePlanId;

    # print "\nPRODUCT RATE PLAN ID: $ProductRatePlanId";
    # print "\nPRODUCT RATE PLAN CHARGE ID: $ProductRatePlanChargeId";

    # SUBSCRIBE
    $zAccount = new Zuora_Account();
    $zAccount->AllowInvoiceEdit = 1;
    $zAccount->AutoPay = 0;
    $zAccount->Batch = 'Batch1';
    $zAccount->BillCycleDay = 1;
    $zAccount->CrmId = 'SFDC-1223471249003';
    $zAccount->Currency = 'USD';
    if (isset($accountName)){
        $zAccount->Name = $accountName;
    } else {
        $zAccount->Name = 'Robert Smith';
    }
    $zAccount->PaymentTerm = 'Due Upon Receipt';
    $zAccount->PurchaseOrderNumber = 'PO-1223471249003';
    $zAccount->Status = 'Active';

    $zPaymentMethod = new Zuora_PaymentMethod();
    $zPaymentMethod->CreditCardAddress1 = '52 Vexford Lane';
    $zPaymentMethod->CreditCardAddress2 = '';
    $zPaymentMethod->CreditCardCity = 'Anaheim';
    $zPaymentMethod->CreditCardCountry = 'United States';
    $zPaymentMethod->CreditCardExpirationMonth = '12';
    $zPaymentMethod->CreditCardExpirationYear = '2012';
    $zPaymentMethod->CreditCardHolderName = 'Firstly Lastly';
    $zPaymentMethod->CreditCardNumber = '4111111111111111';
    $zPaymentMethod->CreditCardPostalCode = '22042';
    $zPaymentMethod->CreditCardState = 'California';
    $zPaymentMethod->CreditCardType = 'Visa';
    $zPaymentMethod->Type = 'Credit Card';

    $zBillToContact = new Zuora_Contact();
    $zBillToContact->Address1 = '4901 Morena Blvd';
    $zBillToContact->Address2 = '';
    $zBillToContact->City = 'San Diego';
    $zBillToContact->Country = 'United States';
    $zBillToContact->FirstName = 'Robert';
    $zBillToContact->LastName = 'Smith';
    $zBillToContact->PostalCode = '92117';
    $zBillToContact->State = 'Virginia';
    $zBillToContact->WorkEmail = 'robert@smith.com';

    $date = date('Y-m-d\TH:i:s');
    $zSubscription = new Zuora_Subscription();
    if (isset($subscriptionName)){
        $zSubscription->Name = $subscriptionName;
    } else {
        $zSubscription->Name = "Name" . time();
    }
    $zSubscription->AutoRenew = 1;
    $zSubscription->ContractAcceptanceDate = $date;
    $zSubscription->ContractEffectiveDate = $date;
    $zSubscription->Currency = 'USD';
    $zSubscription->InitialTerm = 1;
    $zSubscription->RenewalTerm = 1;
    $zSubscription->ServiceActivationDate = $date;
    $zSubscription->Status = 'Active';
    $zSubscription->TermStartDate=$date;
    $zSubscription->Version=1;

    $zRatePlan = new Zuora_RatePlan();
    $zRatePlan->AmendmentType = 'NewProduct';

    $zRatePlan->ProductRatePlanId = $ProductRatePlanId;
    $zRatePlanData = new Zuora_RatePlanData($zRatePlan);
    
    $zRatePlanCharge = new Zuora_RatePlanCharge();
    $zRatePlanCharge->ProductRatePlanChargeId = $ProductRatePlanChargeId;
    $zRatePlanCharge->Quantity = 1;
    $zRatePlanCharge->TriggerEvent = 'ServiceActivation';

    $zRatePlanData->addRatePlanCharge($zRatePlanCharge);

    $zSubscriptionData = new Zuora_SubscriptionData($zSubscription);
    $zSubscriptionData->addRatePlanData($zRatePlanData);
    
    $zSubscribeOptions = new Zuora_SubscribeOptions(true,true);
    
    if($GenerateInvoiceAndPayments===false){
    	$zSubscribeOptions = new Zuora_SubscribeOptions(false,false);
    }
        
    $result = $instance->subscribe($zAccount, $zBillToContact, $zPaymentMethod, $zSubscriptionData,$zSubscribeOptions);

    return $result;

}
# make subscribe w/ existing call
function subscribeWithExistingAccount($instance, $ProductRatePlanCharge, $accountId, $subscriptionName=null){

    $ProductRatePlanChargeId = $ProductRatePlanCharge->Id;
    $ProductRatePlanId = $ProductRatePlanCharge->ProductRatePlanId;

    # print "\nPRODUCT RATE PLAN ID: $ProductRatePlanId";
    # print "\nPRODUCT RATE PLAN CHARGE ID: $ProductRatePlanChargeId";

    # SUBSCRIBE
    $zAccount = new Zuora_Account();
    $zAccount->Id = $accountId;

    $date = date('Y-m-d\TH:i:s');
    $zSubscription = new Zuora_Subscription();
    if (isset($subscriptionName)){
        $zSubscription->Name = $subscriptionName;
    } else {
        $zSubscription->Name = "Name" . time();
    }
    $zSubscription->AutoRenew = 1;
    $zSubscription->ContractAcceptanceDate = $date;
    $zSubscription->ContractEffectiveDate = $date;
    $zSubscription->Currency = 'USD';
    $zSubscription->InitialTerm = 1;
    $zSubscription->RenewalTerm = 1;
    $zSubscription->ServiceActivationDate = $date;
    $zSubscription->Status = 'Active';
    $zSubscription->TermStartDate=$date;
    $zSubscription->Version=1;

    $zRatePlan = new Zuora_RatePlan();
    $zRatePlan->AmendmentType = 'NewProduct';

    $zRatePlan->ProductRatePlanId = $ProductRatePlanId;
    $zRatePlanData = new Zuora_RatePlanData($zRatePlan);
    
    $zRatePlanCharge = new Zuora_RatePlanCharge();
    $zRatePlanCharge->ProductRatePlanChargeId = $ProductRatePlanChargeId;
    $zRatePlanCharge->Quantity = 1;
    $zRatePlanCharge->TriggerEvent = 'ServiceActivation';

    $zRatePlanData->addRatePlanCharge($zRatePlanCharge);

    $zSubscriptionData = new Zuora_SubscriptionData($zSubscription);
    $zSubscriptionData->addRatePlanData($zRatePlanData);

    $zSubscribeOptions = new Zuora_SubscribeOptions(true, false);

    $result = $instance->subscribeWithExistingAccount($zAccount, $zSubscriptionData, $zSubscribeOptions);

    return $result;

}
# upload some hard code sample usage info
function upload_usage($instance,$usages){
	
	$zUsages = array();
	
	foreach($usages as $usage){
		$zUsage = new Zuora_Usage();
		$zUsage->AccountId = $usage['AccountId'];
		$zUsage->Quantity  = $usage['Quantity'];
		$zUsage->StartDateTime = $usage['StartDateTime'];
		$zUsage->UOM = $usage['UOM'];
		$zUsages[] = $zUsage;
	}
	// please Iterate through the list of zUsages, 50 at a time
  $result = $instance->create($zUsages);
	
	return $result;
}

# generate an invoice
function generateInvoice($instance,$accountId,$invoiceDate,$targetDate){
	$zInvoices = array();
	
	$invoice = new Zuora_Invoice();
	$invoice->AccountId = $accountId;
	$invoice->InvoiceDate = $invoiceDate;
	$invoice->TargetDate = $targetDate;

	$zInvoices[] = $invoice;
	
	$result = $instance->generate($zInvoices);
	return $result;
}

# post invoice
function postInvoice($instance,$invoiceId){
  $invoice = new Zuora_Invoice();
	$invoice->Id = $invoiceId;
	$invoice->Status = 'Posted';
	
	$result = $instance->update(array($invoice));
	return $result;
}

function createAndApplyPayment($instance,$accountId){
 # QUERY PaymentMethod
 $query = "SELECT Id,Type FROM PaymentMethod WHERE AccountId = '".$accountId."'";
 $records = queryAll($instance, $query);
 $paymentMethodId=$records[0]->Id;
 print "\nPaymentMethod Queried ($query): " . $records[0]->Id . "  " . $records[0]->Type;
 
 # QUERY Invoice Balance
 $query = "select Id,Balance from Invoice where AccountId = '".$accountId."' and Balance>0";
 $records = queryAll($instance, $query);
 $amount = $records[0]->Balance;
 $invoiceId = $records[0]->Id;
 print "\nInvoice Balance Queried ($query): " . $records[0]->Id . "  " . $records[0]->Balance;
 
 $payment = new Zuora_Payment();
 $payment->AccountId = $accountId;
 $payment->Amount = $amount;
 $payment->EffectiveDate = date('Y-m-d\TH:i:s');
 $payment->PaymentMethodId = $paymentMethodId;
 $payment->Type = 'Electronic';
 $payment->Status = 'Draft';
 
 $result = $instance->create(array($payment));
 $paymentId = $result->result->Id;
 
 $success1 = $result->result->Success;
 $msg = "Payment: ".($success1 ? "Success" : $result->result->errors->Code . " (" . $result->result->errors->Message.")");
 
 $invoicePayment = new Zuora_InvoicePayment();
 $invoicePayment->Amount = $amount;
 $invoicePayment->InvoiceId = $invoiceId;
 $invoicePayment->PaymentId = $paymentId;  
 $result = $instance->create(array($invoicePayment));
 
 $success2 = $result->result->Success;
 $msg .=" -> InvoicePayment: ". ($success2 ?  "Success" : $result->result->errors->Code . " (" . $result->result->errors->Message.")");

 $payment = new Zuora_Payment();
 $payment->Id = $paymentId;
 $payment->Status = 'Processed';
 $result=$instance->update(array($payment));	
 $success3 = $result->result->Success;	
 $msg .=" -> Payment Processed:". ($success3 ?  "Success" : $result->result->errors->Code . " (" . $result->result->errors->Message.")");

 print "\nCreate and Apply Payment: " . $msg;
}
