#!/usr/bin/php
<?php 
/*
 *    Copyright (c) 2010 Zuora, Inc.
 *    
 *    Permission is hereby granted, free of charge, to any person obtaining a copy of 
 *    this software and associated documentation files (the "Software"), to use copy, 
 *    modify, merge, publish the Software and to distribute, and sublicense copies of 
 *    the Software, provided no fee is charged for the Software.  In addition the
 *    rights specified above are conditioned upon the following:
 *    
 *    The above copyright notice and this permission notice shall be included in all
 *    copies or substantial portions of the Software.
 *    
 *    Zuora, Inc. or any other trademarks of Zuora, Inc.  may not be used to endorse
 *    or promote products derived from this Software without specific prior written
 *    permission from Zuora, Inc.
 *    
 *    THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *    IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *    FITNESS FOR A PARTICULAR PURPOSE AND NON-INFRINGEMENT. IN NO EVENT SHALL
 *    ZUORA, INC. BE LIABLE FOR ANY DIRECT, INDIRECT OR CONSEQUENTIAL DAMAGES
 *    (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 *    LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
 *    ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 *    (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 *    SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */
error_reporting(E_ALL); 
ini_set("display_errors", 1); 

date_default_timezone_set('America/Los_Angeles');

require_once 'lib/API.php';
require_once 'config.php';
require_once 'functions.php';

$config = new stdClass();
$config->wsdl = $wsdl;

$productName = '<your productName>';// to keep things simple ,you'd better create a product with flat-fee of one-time charge for testing. and turn off the "Require Customer Acceptance of Orders?" ,"Require Service Activation of Orders?" in the Default Subscription Settings.

$instance = Zuora_API::getInstance($config);
$instance->setQueryOptions($query_batch_size);

# LOGIN
$instance->setLocation($endpoint);
$instance->login($username, $password);


# GET PRODUCT RATEPLAN FOR SUBSCRIBE
$ProductRatePlan  = getProductRatePlan($instance,$productName);
print "\nProductRatePlan Queried (ProductName=$productName): " . $ProductRatePlan->Id;

print "\n-------------------------------------------------------------------------------";
print "\nUSE CASE #1:CREATE ACTIVE ACCOUNT";
print "\n-------------------------------------------------------------------------------";
# CREATE AN ACTIVE ACCOUNT
$name = "Testabc" . time();
$newAccountId = createActiveAccount($instance, $name);
print "\nAccount Created: " . $newAccountId;
# QUERY ACCOUNT
$query = "SELECT Id, AccountNumber, Name FROM Account WHERE name = '".$name."'";
$records = queryAll($instance, $query);
print "\nAccount Queried ($query): " . $records[0]->AccountNumber;

print "\n-------------------------------------------------------------------------------";
print "\nUSE CASE #2:CREATE NEW SUBSCRIPTION, ONE-CALL";
print "\n-------------------------------------------------------------------------------";

# SUBSCRIBE WILL CREATE ACCOUNT
# Invoice & Payment
$result = subscribe($instance, $ProductRatePlan);
$success = $result->result->Success;
$msg = ($success ? $result->result->AccountNumber . ',' . $result->result->SubscriptionNumber : $result->result->Errors->Code . " (" . $result->result->Errors->Message.")");
print "\nSubscribe and Invoice&Payment:(AccountNumber & SubscriptionNumber) " . $msg;

if($success){
	$subscriptionId = $result -> result -> SubscriptionId;
	# QUERY Subscription
	$query = "SELECT Id, Name FROM Subscription WHERE Id = '".$subscriptionId."'";
	$records = queryAll($instance, $query);
	print "\nSubscription Queried ($query): " . $records[0]->Name;
}

print "\n-------------------------------------------------------------------------------";
print "\nUSE CASE #3:CREATE NEW SUBSCRIPTION, ONE-CALL, NO PAYMENTS";
print "\n-------------------------------------------------------------------------------";

# SUBSCRIBE NO Payment

$result = subscribe($instance, $ProductRatePlan,false);
$success = $result->result->Success;
$msg = ($success ? $result->result->AccountNumber . ',' . $result->result->SubscriptionNumber : $result->result->Errors->Code . " (" . $result->result->Errors->Message.")");
print "\nSubscribe and Invoice:(AccountNumber & SubscriptionNumber) " . $msg;

if($success){
	$subscriptionId = $result -> result -> SubscriptionId;
	# QUERY Subscription
	$query = "SELECT Id, Name FROM Subscription WHERE Id = '".$subscriptionId."'";
	$records = queryAll($instance, $query);
	print "\nSubscription Queried ($query): " . $records[0]->Name;
}

print "\n-------------------------------------------------------------------------------";
print "\nUSE CASE #4:CREATE NEW SUBSCRIPTION ON EXISTING ACCOUNT";
print "\n-------------------------------------------------------------------------------";

# SUBSCRIBE WITH EXISTING WILL CREATE SUBSCRIPTION ON EXISTING ACCOUNT
$result = subscribeWithExistingAccount($instance, $ProductRatePlan, $newAccountId);
$success = $result->result->Success;
$msg = ($success ? $result->result->AccountNumber . ',' . $result->result->SubscriptionNumber : $result->result->Errors->Code . " (" . $result->result->Errors->Message.")");
print "\nSubscribe with existing account and Invoice&Payment:(AccountNumber & SubscriptionNumber) " . $msg;

if($success){
	$subscriptionId = $result -> result -> SubscriptionId;
	# QUERY Subscription
	$query = "SELECT Id, Name FROM Subscription WHERE Id = '".$subscriptionId."'";
	$records = queryAll($instance, $query);
	print "\nSubscription Queried ($query): " . $records[0]->Name;
}
print "\n-------------------------------------------------------------------------------";
print "\nUSE CASE #5:CREATE NEW SUBSCRIPTION, UPGRADE AND DOWNGRADE";
print "\n-------------------------------------------------------------------------------";

$result = subscribe($instance, $ProductRatePlan);
$success = $result->result->Success;
$msg = ($success ? $result->result->SubscriptionNumber : $result->result->Errors->Code . " (" . $result->result->Errors->Message.")");
print "\nSubscribe for Amendment:(SubscriptionNumber) " . $msg;

$subscriptionId = $result -> result -> SubscriptionId;

$result = newProductAmendment($instance,$productName,$subscriptionId);
$success = $result->result->Success;
$msg = ($success ? $result->result->Id : $result->result->Errors->Code . " (" . $result->result->Errors->Message.")");
print "\nnew Product Amendment: " . $msg;

$query = "SELECT Id, Name , Version FROM Subscription WHERE PreviousSubscriptionId = '".$subscriptionId."'";
$records = queryAll($instance, $query);

$newSubscriptionId=$records[0]->Id;

print "\nSubscription After new Product Amendment(Id,Name,Version):". $records[0]->Id . ',' .$records[0]->Name . ','. $records[0]->Version;

$query = "select Id from RatePlan where SubscriptionId='" . $records[0]->Id . "'";
$records = queryAll($instance, $query);

$result = removeProductAmendment($instance,$records[0]->Id,$newSubscriptionId);
$success = $result->result->Success;
$msg = ($success ? $result->result->Id : $result->result->Errors->Code . " (" . $result->result->Errors->Message.")");
print "\nremove Product Amendment: " . $msg;

$query = "SELECT Id, Name , Version FROM Subscription WHERE PreviousSubscriptionId = '".$newSubscriptionId."'";
$records = queryAll($instance, $query);

print "\nSubscription After remove Product Amendment(Id,Name,Version):". $records[0]->Id . ',' .$records[0]->Name . ','. $records[0]->Version;

print "\n-------------------------------------------------------------------------------";
print "\nUSE CASE #6:CANCEL SUBSCRIPTION";
print "\n-------------------------------------------------------------------------------";


$result = subscribe($instance, $ProductRatePlan);
$success = $result->result->Success;
$msg = ($success ? $result->result->SubscriptionNumber : $result->result->Errors->Code . " (" . $result->result->Errors->Message.")");
print "\nSubscribe for Cancel Amendment:(SubscriptionNumber) " . $msg;

$subscriptionId = $result -> result -> SubscriptionId;
$result = cancelSubscriptionAmendment($instance,$subscriptionId);
$success = $result->result->Success;
$msg = ($success ? $result->result->Id : $result->result->Errors->Code . " (" . $result->result->Errors->Message.")");
print "\nCancel Subscription Amendment: " . $msg;

print "\n-------------------------------------------------------------------------------";
print "\nUSE CASE #7:CREATE PAYMENT ON INVOICE";
print "\n-------------------------------------------------------------------------------";

$result = subscribe($instance, $ProductRatePlan,false);
$success = $result->result->Success;
$msg = ($success ? $result->result->SubscriptionNumber : $result->result->Errors->Code . " (" . $result->result->Errors->Message.")");
print "\nSubscribe no Payment:(SubscriptionNumber) " . $msg;

$accountId = $result -> result -> AccountId;

createAndApplyPayment($instance,$accountId);

print "\n-------------------------------------------------------------------------------";
print "\nUSE CASE #8:CREATE REFUND";
print "\n-------------------------------------------------------------------------------";

print "\n Not Implemented";

print "\n-------------------------------------------------------------------------------";
print "\nUSE CASE #9:ADD USAGE";
print "\n-------------------------------------------------------------------------------";

#UPLOAD some usage
//the minimum required fields
$usage = array("AccountId" => $newAccountId,"Quantity" => 5,"StartDateTime" => '2010-04-08T12:22:22',"UOM" => 'Each');
$result = uploadUsages($instance,array($usage));
print "\nUsage Created: " . $result->result->Id;

print "\n-------------------------------------------------------------------------------";
print "\nUSE CASE #10:QUERY PRODUCT CATALOG";
print "\n-------------------------------------------------------------------------------";

$query = "SELECT Id, Name FROM Product";
$records = queryAll($instance, $query);

print "\nAll Product count:". count($records);

print "\n-------------------------------------------------------------------------------";
print "\nUSE CASE #11:GENERATE INVOICE";
print "\n-------------------------------------------------------------------------------";

# GENERATE & QUERY & POST INVOICE
print "\nGenerating Invoice...";
$result = subscribe($instance, $ProductRatePlan,false,false);
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
print "\n-------------------------------------------------------------------------------";
print "\nUSE CASE #12:QUERY ACCOUNT AND DELETE ACCOUNT";
print "\n-------------------------------------------------------------------------------";
# CREATE AN ACTIVE ACCOUNT
$name = "Test" . time();
$id = createActiveAccount($instance, $name);
print "\nAccount Created: " . $id;

# QUERY ACCOUNT
$query = "SELECT Id, AccountNumber, Name FROM Account WHERE name = '".$name."'";
$records = queryAll($instance, $query);
print "\nAccount Queried ($query): " . $records[0]->AccountNumber;

# DELETE ACCOUNT
$result = $instance->delete('Account', array($id));
$success = $result->result->success;
$msg = ($success ? "Success" : $result->result->errors->Code . " (" . $result->result->errors->Message.")");
print "\nAccount Deleted: " . $msg;


# useful for debugging responses
# Zuora_Debug::dump($result);

die();

# method to create an active account. requires that you have:
#
#   1.) a gateway setup, 
#   2.) gateway configured to not verify new credit cards
#
# if you want to verify a new credit card, make sure the card info
# you specify is correct.
function createActiveAccount($instance, $name){

    # Create Draft Account
    $zAccount = makeAccount($name,'USD','Draft');

    $result = $instance->create(array($zAccount));
    $accountId = $result->result->Id;

    # Create Contact on Account
    $zBillToContact = makeContact('Robert','Smith','4901 Morena Blvd',null,'San Diego','Virginia','United States','92117','robert@smith.com',null,$accountId);
    
    $result = $instance->create(array($zBillToContact));
    $contactId = $result->result->Id;

    # Create Payment Method on Account
    $zPaymentMethod = makePaymentMethod('Firstly Lastly', '52 Vexford Lane',null, 'Anaheim', 'California', 'United States','22042', 'Visa', '5105105105105100', '12', '2012',$accountId);
         
    $result = $instance->create(array($zPaymentMethod));    
    $paymentMethodId = $result->result->Id;

    # Update Account w/ Bill To/Sold To; also specify as active
    $zAccount = new Zuora_Account();
    $zAccount->Id = $accountId;
    $zAccount->Status = 'Active';
    $zAccount->BillToId = $contactId;
    $zAccount->SoldToId = $contactId;
    $zAccount->DefaultPaymentMethodId = $paymentMethodId;
    $result = $instance->update(array($zAccount));

    return $accountId;

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
function subscribe($instance, $ProductRatePlan,$GeneratePayments=true,$GenerateInvoice=true,$accountName=null, $subscriptionName=null){

    $ProductRatePlanId = $ProductRatePlan->Id;

    # SUBSCRIBE
    $zAccount =  makeAccount((isset($accountName)? $accountName : 'Robert Smith') ,'USD','Active');

    $zPaymentMethod = makePaymentMethod('Firstly Lastly', '52 Vexford Lane',null, 'Anaheim', 'California', 'United States','22042', 'Visa', '5105105105105100', '12', '2012');

    $zBillToContact = makeContact('Robert','Smith','4901 Morena Blvd',null,'San Diego','Virginia','United States','92117','robert@smith.com',null);

   
    $zSubscription = makeSubscription((isset($subscriptionName)?$subscriptionName:"Name".time()),null);

    $zSubscriptionData = makeSubscriptionData($zSubscription,array(),array(),$ProductRatePlanId);
    
    $zSubscribeOptions = new Zuora_SubscribeOptions($GenerateInvoice,$GeneratePayments);
     
    $result = $instance->subscribe($zAccount, $zSubscriptionData,$zBillToContact, $zPaymentMethod, $zSubscribeOptions);

    return $result;
	
}

# make subscribe w/ existing call
function subscribeWithExistingAccount($instance, $ProductRatePlan, $accountId,$GeneratePayments=true,$GenerateInvoice=true, $subscriptionName=null){
    
    $ProductRatePlanId = $ProductRatePlan->Id;

    # SUBSCRIBE
    $zAccount = new Zuora_Account();
    $zAccount->Id = $accountId;

    $zSubscription = makeSubscription((isset($subscriptionName)?$subscriptionName:"Name".time()),null);

		$zSubscriptionData = makeSubscriptionData($zSubscription,array(),array(),$ProductRatePlanId);


    $zSubscribeOptions = new Zuora_SubscribeOptions($GenerateInvoice, $GeneratePayments);

    $result = $instance->subscribeWithExistingAccount($zAccount, $zSubscriptionData, $zSubscribeOptions);

    return $result;

}
# upload some hard code sample usage info
function uploadUsages($instance,$usages){
	
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

function getProductRatePlan($instance,$productName){
	$result = $instance->query("select Id from Product where Name='$productName'");
  if ($result->result->size != 1){
  	print "No Product found with Name = '$productName'";
		exit();
  }		
  $productId = $result->result->records->Id;
  
  $result = $instance->query("select Id,Name from ProductRatePlan where ProductId = '$productId'");
	if ($result->result->size == 0){
		print "No Product Rate Plan found with ProductId = '$productId'(Product Name = '$productName')";
		exit();
  }
	
	if(is_array($result->result->records)){
		$ProductRatePlan = $result->result->records[0];
	}else{
		$ProductRatePlan = $result->result->records;
	}
	
	return $ProductRatePlan;
}

function getProductRatePlanCharges($instance,$productRatePlanId){
	$result = $instance->query("select Id from ProductRatePlanCharge where ProductRatePlanId='$productRatePlanId'");
  if ($result->result->size == 0){
  	print "No Product RatePlan Charge found with ProductRatePlanId = '$productRatePlanId'";
		exit();
  }		
  
  $ProductRatePlanCharges = array();
  if(is_array($result->result->records)){
  	$ProductRatePlanCharges = $result->result->records;
  }else{
  	$ProductRatePlanCharges[] = $result->result->records;
  }
  
  return $ProductRatePlanCharges; 
}
function newProductAmendment($instance,$newProductName,$subscriptionId){
	$date = date('Y-m-d\TH:i:s');
	
	$amendment = new Zuora_Amendment();
	$amendment->EffectiveDate = $date;
	$amendment->Name = 'addproduct' . time();
	$amendment->Status = 'Draft';
	$amendment->SubscriptionId = $subscriptionId;
	$amendment->Type = 'NewProduct';
	
	$result = $instance->create(array($amendment));
	
	$amendmentId = $result -> result -> Id;
	
	$rateplan = new Zuora_RatePlan();
	$rateplan->AmendmentId = $amendmentId;
	$rateplan->AmendmentType = 'NewProduct';
	
	$ProductRatePlan = getProductRatePlan($instance,$newProductName);
	$rateplan->ProductRatePlanId = $ProductRatePlan->Id;
	
	$instance->create(array($rateplan));
	
	$amendment = new Zuora_Amendment();
	$amendment->Id = $amendmentId;
	$amendment->ContractEffectiveDate = $date;
	
	$amendment->Status = 'Completed';
	
	$result = $instance->update(array($amendment));
	return $result;
}

function removeProductAmendment($instance,$ratePlanId,$subscriptionId){
	$date = date('Y-m-d\TH:i:s');
	
	$amendment = new Zuora_Amendment();
	$amendment->EffectiveDate = $date;
	$amendment->Name = 'removeproduct' . time();
	$amendment->Status = 'Draft';
	$amendment->SubscriptionId = $subscriptionId;
	$amendment->Type = 'RemoveProduct';
	
	$result = $instance->create(array($amendment));
	
	$amendmentId = $result -> result -> Id;
	
	$rateplan = new Zuora_RatePlan();
	$rateplan->AmendmentId = $amendmentId;
	$rateplan->AmendmentType = 'RemoveProduct';

	$rateplan->AmendmentSubscriptionRatePlanId  = $ratePlanId;
	
	$instance->create(array($rateplan));
	
	$amendment = new Zuora_Amendment();
	$amendment->Id = $amendmentId;
	$amendment->ContractEffectiveDate = $date;
	
	$amendment->Status = 'Completed';
	
	$result = $instance->update(array($amendment));
	return $result;
}

function cancelSubscriptionAmendment($instance,$subscriptionId){
	$date = date('Y-m-d\TH:i:s');
	
	$amendment = new Zuora_Amendment();
	$amendment->EffectiveDate = $date;
	$amendment->Name = 'cancel' . time();
	$amendment->Status = 'Draft';
	$amendment->SubscriptionId = $subscriptionId;
	$amendment->Type = 'Cancellation';
	
	$result = $instance->create(array($amendment));
	
	$amendmentId = $result -> result -> Id;	
	
	$amendment = new Zuora_Amendment();
	$amendment->Id = $amendmentId;
	$amendment->ContractEffectiveDate = $date;
	
	$amendment->Status = 'Completed';
	
	$result = $instance->update(array($amendment));
	return $result;

}