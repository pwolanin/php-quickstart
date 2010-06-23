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


