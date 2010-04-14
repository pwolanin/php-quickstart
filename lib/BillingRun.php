<?php
class Zuora_BillingRun extends Zuora_Object
{
    protected $zType = 'BillingRun';
    
    public function __construct()
    {
        $this->_data = array(
            'BatchOrAccountId'=>null,
            'BillingRunNumber'=>null,
            'EndDate'=>null,
            'ErrorMessage'=>null,
            'ExecutedDate'=>null,
            'InvoiceDate'=>null,
            'NumberOfAccounts'=>null,
            'NumberOfInvoices'=>null,
            'StartDate'=>null,
            'Status'=>null,
            'TargetDate'=>null,
            'TargetType'=>null,
            'TotalTime'=>null,
        );
    }
}
