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
Zuora API (Alpha Version) PHP Quickstart

INTRODUCTION
------------

Thank you for downloading the Zuora QuickStart PHP Toolkit.  This download contains code designed to help you begin using Zuora APIs.

REQUIREMENTS
------------

PHP 5.2.6
PHP:SOAP 
cURL (for secure requests over HTTPS)

CONTENTS
--------

This sample zip contains:
		/lib - contains all dependent api files to run the sample
		/signup/signup.php drop-in php page that uses API to display sign-up page and process new order via subscribe() call.
    /config.php  some configuration
    /functions.php  some common functions
    /main.php - Sample code using the Zuora API with PHP
    /readme.txt this file
    /zuora.17.0.wsdl - Copy of the WSDL. You can download the latest version from your own Zuora tenant in the Z-Billing Admin page.
	
DOCUMENTATION & SUPPORT
-----------------------

API Documentation is available at http://developer.zuora.com

PRE-REQUISITES
--------------

The following are pre-requisites to successfully run the sample code:

1. A Zuora Tenant
2. A Zuora User
    a.) with the User Role Permission to create Invoices (http://knowledgecenter.zuora.com/index.php/Z-Billing_Admin#Manage_User_Roles)
3. A Product created with a Rate Plan & Rate Plan Component (http://knowledgecenter.zuora.com/index.php/Product_Catalog), with
    a.) The Effective Period (Start & End) of the Product/Rate Plan not expired (start < today and end > today)
    b.) To keep things simple ,you'd better create a product with flat-fee of one-time charge for testing. 
4. A Zuora Gateway set up (http://knowledgecenter.zuora.com/index.php/Z-Payments_Admin#Setup_Payment_Gateway)
    a.) Either Authorize.net, CyberSource, PayPal Payflow Pro (production or test)
    b.) The setting "Verify new credit card" disabled
5. Modify the Default Subscription Settings
		a.) Turn off the "Require Customer Acceptance of Orders?" 
		b.) Turn off the "Require Service Activation of Orders?" 
    

RUNNING THE EXAMPLE
-------------------

1. Unzip the files contained in the quickstart_php.zip file to a folder on you hard drive.  
2. In config.php, specify:
    a.) $username as the username for your Zuora user.
    b.) $password the password for your Zuora user.
    c.) if you are testing against apisandbox, change $endpoint to https://apisandbox.zuora.com/apps/services/a/17.0
3. From the command line
		a.) In main.php, specify $productName as the name of the Product
		b.) type "php main.php"
		
4. To run the signup.php page, you need to have
    a.) Setup a webserver (Apache +PHP)
		b.) Deploy the php-quickstart
    d.) Run the webserver and hit the signup.php page

INCLUDED IN THE EXAMPLE
-----------------------

This example does the following:

1. Creates an Active Account (Account w/ Status=Active and Bill To Contact/Payment Method)
2. Creates new subscription,one-call
3. Creates new subscription,one-call,no payments
4. Creates new subscription on existing account
5. Creates new subscription ,upgrade and downgrade 
6. Cancel subscription
7. Creates payment on invoice
8. Add usage
9. Generates Invoice and Creates Payment.
10.Query account and delete account.


