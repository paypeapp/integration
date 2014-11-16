<?php
// TODO: add your ws

class PartnerCommunicator
{
    private $client;
    private $api;
    private $sync;

    public function __construct($client, $api, $config)
    {
        $this->client = $client;
        $this->api = $api;
        $this->config = $config;

        if(empty($_GET['force']))
        {
            try
            {
                $this->sync = json_decode($this->api->getSync());
            }
            catch(Exception $e)
            {
                $this->log($e->getMessage(), true);
                die('Fail.');
            }
        }
    }

    // send customers added in local system to Paype
    public function customersPush()
    {
        $this->log('customersPush');

        // TODO: get customers added on your systems to array
        if(!is_array($customers))
        {
            $customers = array($customers);
        }

        foreach($customers as $customer)
        {
            // TODO: map the customer fields
            $customer = array(
                'first_name' => $customer->First_Name,
                'last_name' => $customer->Last_Name,
                'email' => $customer->E_Mail,
                'customer_id' => $customer->No
            );

            try
            {
                $this->api->postCustomer($customer);
            }
            catch(Exception $e)
            {
                $this->log('customersPush customer api post failed: ' . $e->getMessage(), true);
            }
        }
    }

    // get customers added in Paype to local system
    public function customersPull()
    {
        $this->log('customersPull');

        $currentTime = time();
        $lastSyncTime = 0;

        if(!empty($this->sync->last_customer_pull_time))
        {
            $lastSyncTime = $this->sync->last_customer_pull_time;
        }

        try
        {
            $customers = json_decode($this->api->getCustomers($currentTime - $lastSyncTime));
        }
        catch(Exception $e)
        {
            $this->log('customersPull customers api get fail: ' . $e->getMessage(), true);
        }

        if(!is_array($customers))
        {
            // there is only one customer
            $customers = array($customers);
        }

        foreach($customers as $c)
        {
            // TODO: post the customer to your systems
        }
    }

    private function log($msg, $alwaysLog = false)
    {
        if(!empty($_GET['verbose']) || $alwaysLog)
        {
            error_log('[paype-sync] ' . substr($msg, 0, 100));
            echo $msg . '<br>';
        }
    }
}