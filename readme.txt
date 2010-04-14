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

    /main.php - Sample code using the Zuora API with PHP
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
    b.) An Accounting Code specified on the Rate Plan Component 
4. A Zuora Gateway set up (http://knowledgecenter.zuora.com/index.php/Z-Payments_Admin#Setup_Payment_Gateway)
    a.) Either Authorize.net, CyberSource, PayPal Payflow Pro (production or test)
    b.) The setting "Verify new credit card" disabled

RUNNING THE EXAMPLE
-------------------

1. Unzip the files contained in the quickstart_php.zip file to a folder on you hard drive.  
2. In main.php, specify:
    a.) $username as the username for your Zuora user.
    b.) $password the password for your Zuora user.
    c.) if you are testing against apisandbox, change $endpoint to https://apisandbox.zuora.com/apps/services/a/17.0
    d.) $accountingCode as the Accounting Code specified on the Rate Plan Component
3. From the command line, type "php main.php"

INCLUDED IN THE EXAMPLE
-----------------------

This example does the following:

1. Creates an Active Account (Account w/ Status=Active and Bill To Contact/Payment Method)
2. Creates some usage 
3. Queries Account
4. Deletes Account
5. Queries Product Catalog by AccountingCode
6. Calls subscribe() to create new Account w/ Subscription
7. Calls subscribeWithExistingAccount() to create Subscription with the existing Account
8. Generates Invoice
9. Creates Payment.


