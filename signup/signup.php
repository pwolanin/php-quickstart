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

date_default_timezone_set('America/Los_Angeles');

require_once '../lib/API.php';
require_once '../config.php';
require_once '../functions.php';

$Name = "Name";
$FirstName = "FirstName";
$LastName = "LastName";
$WorkEmail = "WorkEmail";
$WorkPhone = "WorkPhone";
$Address1 = "Address1";
$Address2 = "Address2";
$City = "City";
$State = "State";
$Country = "Country";
$PostalCode = "PostalCode";
$CreditCardHolderName = "CreditCardHolderName";
$CreditCardNumber = "CreditCardNumber";
$CreditCardExpirationMonth = "CreditCardExpirationMonth";
$CreditCardExpirationYear = "CreditCardExpirationYear";
$CreditCardType = "CreditCardType";
$CreditCardPostalCode = "CreditCardPostalCode";

$status='';

$requireFieldsArray = array($Name,$FirstName,$LastName,$WorkEmail,$WorkPhone,$Address1,$City,$State,$Country,$PostalCode,$CreditCardHolderName,$CreditCardNumber,$CreditCardExpirationMonth,$CreditCardExpirationYear,$CreditCardType,$CreditCardPostalCode);

$fieldsValue = array();
$fieldsValue[$Name]                     = getPostValue($Name,'Test Company');
$fieldsValue[$FirstName]                = getPostValue($FirstName,'Clement');
$fieldsValue[$LastName]                 = getPostValue($LastName,'Banks');
$fieldsValue[$WorkEmail]                = getPostValue($WorkEmail,'cbanks@gmail.com');
$fieldsValue[$WorkPhone]                = getPostValue($WorkPhone,'415 555 1212');
$fieldsValue[$Address1]                 = getPostValue($Address1,'517 Country Lane');
$fieldsValue[$Address2]                 = getPostValue($Address2,'');
$fieldsValue[$City]                     = getPostValue($City,'Anaheim');
$fieldsValue[$State]                    = getPostValue($State,'CA');
$fieldsValue[$Country]                  = getPostValue($Country,'USA');
$fieldsValue[$PostalCode]               = getPostValue($PostalCode,'92808');
$fieldsValue[$CreditCardHolderName]     = getPostValue($CreditCardHolderName,'Clement Banks');
$fieldsValue[$CreditCardNumber]         = getPostValue($CreditCardNumber,'41111111111111111');
$fieldsValue[$CreditCardExpirationMonth]= getPostValue($CreditCardExpirationMonth,'01');
$fieldsValue[$CreditCardExpirationYear] = getPostValue($CreditCardExpirationYear,'2014');
$fieldsValue[$CreditCardType]           = getPostValue($CreditCardType,'Visa');
$fieldsValue[$CreditCardPostalCode]     = getPostValue($CreditCardPostalCode,'92808');

$config = new stdClass();
$config->wsdl = "../".$wsdl;

$instance = Zuora_API::getInstance($config);
$instance->setQueryOptions($query_batch_size);

# LOGIN
$instance->setLocation($endpoint);

if($instance->login($username, $password)){
	

	$productId = $_POST['Products'];
	$rateplanId = $_POST['RatePlans'];
	$chargeIds = $_POST['Charges'];
	
	$nowdate =date('Y-m-d\TH:i:s',time());
	$products = queryAll($instance,"select Id ,Name from Product where EffectiveEndDate > '".$nowdate ."' and EffectiveStartDate <'".$nowdate ."'" );
	
	if (count($products)==1){
		$productId = $products[0]->Id;
	}
	
	if(isset($productId) && strlen($productId)==32){
		$rateplans = queryAll($instance,"select Id,Name from ProductRatePlan where ProductId='".$productId."'");				
		
		if(count($rateplans)==1){
			$rateplanId = $rateplans[0]->Id;
		}
		
		if(isset($rateplanId) && strlen($rateplanId)==32){
			$rateplancharges = queryAll($instance,"select Id, Name, AccountingCode, DefaultQuantity, Type, Model, ProductRatePlanId from ProductRatePlanCharge where ProductRatePlanId ='".$rateplanId."'");
			
				if($chargeIds==null){
					$chargeIds = array();
					foreach($rateplancharges as $rc){
						$chargeIds[]=$rc->Id;			
					}	
				}
				
			
		}
	}
  
  if($_POST['Submit']){
		if(validate()){
			subscribedata($instance,$chargeIds,$rateplancharges,$rateplanId);
		}
	}
}else{
	$status="<b>Login Failed</b>";
}


function createMessage($subscriberesult){
	global $status;
	
	$status = "";
	if(isset($subscriberesult)){
		if($subscriberesult->result->Success){
           $status = $status . "<b>Subscribe Result: Success</b>";
            $status = $status . "<br>&nbsp;&nbsp;Account Id: " . $subscriberesult->result->AccountId;
            $status = $status . "<br>&nbsp;&nbsp;Account Number: " . $subscriberesult->result->AccountNumber;
            $status = $status . "<br>&nbsp;&nbsp;Subscription Id: " .$subscriberesult->result->SubscriptionId;
            $status = $status . "<br>&nbsp;&nbsp;Subscription Number: " .$subscriberesult->result->SubscriptionNumber;
            $status = $status . "<br>&nbsp;&nbsp;Invoice Id: " .$subscriberesult->result->InvoiceId;
            $status = $status . "<br>&nbsp;&nbsp;Invoice Number: " .$subscriberesult->result->InvoiceNumber;
			
		}else{
			     $status =  $status . "<b>Subscribe Result: Failed</b>";
            if(is_array($subscriberesult->result->Errors)){
            	foreach($subscriberesult->result->Errors as $err){
                 $status = $status . "<br>&nbsp;&nbsp;Error Code: " . $err->Code;
                 $status = $status . "<br>&nbsp;&nbsp;Error Message: " . $err->Message;          		
            	}
            }else{
                 $status = $status . "<br>&nbsp;&nbsp;Error Code: " . $subscriberesult->result->Errors->Code;
                 $status = $status . "<br>&nbsp;&nbsp;Error Message: " . $subscriberesult->result->Errors->Message;          		
            
            }

		}
	}
}
function subscribedata($instance,$chargeIds,$rateplancharges,$rateplanId){
   global $Name ;
   global $FirstName ;
   global $LastName ;
   global $WorkEmail ;
   global $WorkPhone ;
   global $Address1 ;
   global $Address2 ;
   global $City ;
   global $State ;
   global $Country ;
   global $PostalCode ;
   global $CreditCardHolderName ;
   global $CreditCardNumber ;
   global $CreditCardExpirationMonth ;
   global $CreditCardExpirationYear ;
   global $CreditCardType ;
   global $CreditCardPostalCode ;
   
	 $subscriptionName= getPostValue($Name) . " New Signup (" . time() . ")";

	 $account = makeAccount(getPostValue($Name),'USD','Draft');
   $contact = makeContact(getPostValue($FirstName),getPostValue($LastName),getPostValue($Address1),getPostValue($Address2),getPostValue($City),getPostValue($State),getPostValue($Country),getPostValue($PostalCode),getPostValue($WorkMail),getPostValue($WorkPhone));
   $paymentmethod = makePaymentMethod(getPostValue($CreditCardHolderName), getPostValue($Address1),getPostValue($Address2), getPostValue($City), getPostValue($State), getPostValue($Country), getPostValue($PostalCode), getPostValue($CreditCardType), getPostValue($CreditCardNumber), getPostValue($CreditCardExpirationMonth),getPostValue($CreditCardExpirationYear));
     
   $subscription = makeSubscription( $subscriptionName,null);
   
   $zSubscriptionData =makeSubscriptionData($subscription,$chargeIds,$rateplancharges,$rateplanId);
   
   $zSubscribeOptions = new Zuora_SubscribeOptions(false,false);
   
   $result = $instance->subscribe($account, $contact, $paymentmethod, $zSubscriptionData,$zSubscribeOptions);
  
  createMessage($result);
}
function validate(){
	global $productId;
	global $rateplanId;
	global $chargeIds;
	global $requireFieldsArray;
		
	if(isEmpty($productId)) return false;
	if(isEmpty($rateplanId)) return false;
	if(!isset($chargeIds)) return false;
	
	$valid = true;
	foreach($requireFieldsArray as $ea){
		$valid = $valid && validateValue($ea);
	}
	return $valid;
}

function  validateValue($param) {
	global $status;
	global $fieldsValue;
	
  $empty = isEmpty($fieldsValue[$param]);
  if ($empty) {
     $status = $status . $param . " is a required value.<br>";
  }
  return !$empty;
}
?>
<html xmlns="http://www.w3.org/1999/xhtml"> 
<head id="Head1">
<title> 
	Sign Up
</title></head> 
<body> 
 
    <span id="status"><?php echo $status;?></span> 
 
    <form name="Form1" method="post"  action="<?php echo $_SERVER['PHP_SELF']; ?>" id="Form1"> 
    <h2> 
        Select a Product</h2> 
    <table> 
        <tr> 
            <td> 
                <td> 
                    <label for="Products">  
                        Product *</label> 
                </td> 
            </td> 
            <td> 
                <select name="Products" onchange="document.Form1.submit()" id="Products" maxlength="32" >
				 			<?php if($products==null){?>
									<option value=''>-- ERROR OCCUR --</option>
				 			<?php
				 			}else { 
				 				?>
				 				<option value=''>-- SELECT A PRODUCT --</option>
				 			<?php 
				 			  foreach($products as  $p){
				 			?>
				 				<option value="<?php echo $p->Id;?>" <?php if ($productId==$p->Id){ ?> selected <?php }?>> <?php echo htmlentities($p->Name);?> </option>
				 			<?php
				 			}}
				 			?>
				  
				</select> 
            </td> 
        </tr> 
        <tr> 
            <td> 
                <td> 
                    <label for="RatePlans"> 
                        Rate Plan *</label> 
                </td> 
            </td> 
            <td> 
                <select name="RatePlans" onchange="document.Form1.submit()" id="RatePlans" maxlength="32"> 
									<?php if($rateplans==null){?>
									<option value=''>-- SELECT A PRODUCT ABOVE --</option>
									<?php } else {
									foreach($rateplans as  $r){	
										
										$name = $r->Name . ($r->AccountingCode ? (" (" . $r->AccountingCode . ")") :"");
										
									?>
										<option value="<?php echo $r->Id;?>" <?php if ($rateplanId==$r->Id){ ?> selected <?php }?>> <?php echo htmlentities($name);?> </option>
									<?php }}?>
								</select> 
            </td> 
        </tr> 
        <tr> 
            <td> 
                <td> 
                    <label for="Charges"> 
                        Charges *</label> 
                </td> 
            </td> 
            <td> 
                <select size="4" name="Charges[]" multiple="multiple" id="Charges" maxlength="32" > 
                <?php if($rateplancharges==null){?>
								<option value=''>-- SELECT A RATE PLAN ABOVE --</option>
							<?php }else {
									foreach($rateplancharges as $c){
								?>
								<option value="<?php echo $c->Id;?>" <?php if(in_array($c->Id,$chargeIds)) {?> selected <?php }?> > <?php echo htmlentities($c->Name);?> </option>
							 <?php }} ?>
								</select> 
            </td> 
        </tr> 
    </table> 
    <h2> 
        Company Information</h2> 
    <table> 
        <tr> 
            <td> 
                <label for="Name"> 
                    Account Name *</label> 
            </td> 
            <td> 
                <input name="Name" type="text" id="Name" maxlength="40" size="40" value="<?php echo htmlentities($fieldsValue[$Name]);?>" /> 
            </td> 
        </tr> 
        <tr> 
            <td> 
                <label for="FirstName"> 
                    First Name *</label> 
            </td> 
            <td> 
                <input name="FirstName" type="text" id="FirstName" maxlength="40" size="40" value="<?php echo htmlentities($fieldsValue[$FirstName]);?>" /> 
            </td> 
        </tr> 
        <tr> 
            <td> 
                <label for="LastName"> 
                    Last Name *</label> 
            </td> 
            <td> 
                <input name="LastName" type="text" id="LastName" maxlength="80" size="40" value="<?php echo htmlentities($fieldsValue[$LastName]);?>" /> 
            </td> 
        </tr> 
        <tr> 
            <td> 
                <label for="WorkEmail"> 
                    Email *</label> 
            </td> 
            <td> 
                <input name="WorkEmail" type="text" id="WorkEmail" maxlength="80" size="40" value="<?php echo htmlentities($fieldsValue[$WorkEmail]);?>" /> 
            </td> 
        </tr> 
        <tr> 
            <td> 
                <label for="WorkPhone"> 
                    Phone</label> 
            </td> 
            <td> 
                <input name="WorkPhone" type="text" id="WorkPhone" maxlength="40" size="40" value="<?php echo htmlentities($fieldsValue[$WorkPhone]);?>" /> 
            </td> 
        </tr> 
        <tr> 
            <td> 
                <label for="Address1"> 
                    Address 1</label> 
            </td> 
            <td> 
                <input name="Address1" type="text" id="Address1" maxlength="40" size="40" value="<?php echo htmlentities($fieldsValue[$Address1]);?>" /> 
            </td> 
        </tr> 
        <tr> 
            <td> 
                <label for="Address2"> 
                    Address 2</label> 
            </td> 
            <td> 
                <input name="Address2" type="text" id="Address2" maxlength="40" value="<?php echo htmlentities($fieldsValue[$Address2]);?>"/> 
            </td> 
        </tr> 
        <tr> 
            <td> 
                <label for="City"> 
                    City</label> 
            </td> 
            <td> 
                <input name="City" type="text" id="City" maxlength="40" size="40" value="<?php echo htmlentities($fieldsValue[$City]);?>"/> 
            </td> 
        </tr> 
        <tr> 
            <td> 
                <label for="State"> 
                    State</label> 
            </td> 
            <td> 
                <input name="State" type="text" id="State" maxlength="40" size="40" value="<?php echo htmlentities($fieldsValue[$State]);?>"/> 
            </td> 
        </tr> 
        <tr> 
            <td> 
                <label for="Country"> 
                    Country</label> 
            </td> 
            <td> 
<select name="Country" id="Country" maxlength="32"> 
				<option <?php if($fieldsValue[$PostalCode]=='USA'){?> selected <?php }?> value="USA">USA</option> 
				<option <?php if($fieldsValue[$PostalCode]=='CAN'){?> selected <?php }?> value="CAN">CAN</option> 
</select> 
            </td> 
        </tr> 
        <tr> 
            <td> 
                <label for="PostalCode"> 
                    ZIP/Postal Code</label> 
            </td> 
            <td> 
                <input name="PostalCode" type="text" id="PostalCode" maxlength="40" size="40" value="<?php echo htmlentities($fieldsValue[$PostalCode]);?>"/> 
            </td> 
            </td> 
        </tr> 
    </table> 
    <h2> 
        Billing Information</h2> 
    <table> 
        <tr> 
            <td> 
                <label for="CreditCardType"> 
                    Card Type *</label> 
            </td> 
            <td> 
                <select name="CreditCardType" id="CreditCardType" maxlength="32"> 
					<option <?php if($fieldsValue[$CreditCardType]=='Visa'){?> selected <?php }?> value="Visa">Visa</option> 
					<option <?php if($fieldsValue[$CreditCardType]=='MasterCard'){?> selected <?php }?> value="MasterCard">MasterCard</option> 
					<option <?php if($fieldsValue[$CreditCardType]=='AmericanExpress'){?> selected <?php }?> value="AmericanExpress">AmericanExpress</option> 
				</select> 
            </td> 
        </tr> 
        <tr> 
            <td> 
                <label for="CreditCardNumber"> 
                    Card Number *</label> 
            </td> 
            <td> 
                <input name="CreditCardNumber" type="text" id="CreditCardNumber" maxlength="40" size="40" value="<?php echo htmlentities($fieldsValue[$CreditCardNumber]);?>"/> 
            </td> 
        </tr> 
        <tr> 
            <td> 
                <label for="CreditCardHolderName"> 
                    Name on Card *</label> 
            </td> 
            <td> 
                <input name="CreditCardHolderName" type="text" id="CreditCardHolderName" maxlength="40" size="40" value="<?php echo htmlentities($fieldsValue[$CreditCardHolderName]);?>"/> 
            </td> 
        </tr> 
        <tr> 
            <td> 
                <label for="CreditCardExpirationMonth"> 
                    Expiration (MM/YYYY) *</label> 
            </td> 
            <td> 
                <select name="CreditCardExpirationMonth" id="CreditCardExpirationMonth" maxlength="2"> 
					<option <?php if($fieldsValue[$CreditCardExpirationMonth]=='01'){?> selected <?php }?> value="01">01</option> 
					<option <?php if($fieldsValue[$CreditCardExpirationMonth]=='02'){?> selected <?php }?> value="02">02</option> 
					<option <?php if($fieldsValue[$CreditCardExpirationMonth]=='03'){?> selected <?php }?> value="03">03</option> 
					<option <?php if($fieldsValue[$CreditCardExpirationMonth]=='04'){?> selected <?php }?> value="04">04</option> 
					<option <?php if($fieldsValue[$CreditCardExpirationMonth]=='05'){?> selected <?php }?> value="05">05</option> 
					<option <?php if($fieldsValue[$CreditCardExpirationMonth]=='06'){?> selected <?php }?> value="06">06</option> 
					<option <?php if($fieldsValue[$CreditCardExpirationMonth]=='07'){?> selected <?php }?> value="07">07</option> 
					<option <?php if($fieldsValue[$CreditCardExpirationMonth]=='08'){?> selected <?php }?> value="08">08</option> 
					<option <?php if($fieldsValue[$CreditCardExpirationMonth]=='09'){?> selected <?php }?> value="09">09</option> 
					<option <?php if($fieldsValue[$CreditCardExpirationMonth]=='10'){?> selected <?php }?> value="10">10</option> 
					<option <?php if($fieldsValue[$CreditCardExpirationMonth]=='11'){?> selected <?php }?> value="11">11</option> 
					<option <?php if($fieldsValue[$CreditCardExpirationMonth]=='12'){?> selected <?php }?> value="12">12</option> 
				</select> 
                <select name="CreditCardExpirationYear" id="CreditCardExpirationYear" maxlength="4"> 
					<option <?php if($fieldsValue[$CreditCardExpirationYear]=='2014'){?> selected <?php }?> value="2014">2014</option> 
					<option <?php if($fieldsValue[$CreditCardExpirationYear]=='2013'){?> selected <?php }?> value="2013">2013</option> 
					<option <?php if($fieldsValue[$CreditCardExpirationYear]=='2012'){?> selected <?php }?> value="2012">2012</option> 
					<option <?php if($fieldsValue[$CreditCardExpirationYear]=='2011'){?> selected <?php }?> value="2011">2011</option> 
					<option <?php if($fieldsValue[$CreditCardExpirationYear]=='2010'){?> selected <?php }?> value="2010">2010</option> 
				</select> 
            </td> 
        </tr> 
        <tr> 
            <td> 
                <label for="CreditCardPostalCode"> 
                    ZIP/Postal Code *</label> 
            </td> 
            <td> 
                <input name="CreditCardPostalCode" type="text" id="CreditCardPostalCode" maxlength="40" size="40" value="<?php echo htmlentities($fieldsValue[$CreditCardPostalCode]);?>"/> 
            </td> 
        </tr> 
    </table> 
    <br /> 
    <input name="Submit" type="submit" id="Submit" value="Submit" /> 
    </form> 
</body> 
</html> 
