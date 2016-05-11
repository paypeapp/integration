<?php

Class SyncHandler
{
    private $wsClient;
    private $api;
    private $sync;
    private $config;

    public function __construct($config)
    {
        paypeLog('start');
        $t1 = time();

        $this->config = $config;

        if(!$this->auth($config['auth']))
        {
            paypeLog('auth failed', true);
            die('Access denied. Try harder, or do not try at all!');
        }

        $this->sync();

        paypeLog('finished in ' . (time() - $t1) . ' seconds');
    }

    private function sync()
    {
        if(!empty($this->config['webservice']['type']) && file_exists(dirname(__FILE__) . '/wsInterfaces/' . strtolower($this->config['webservice']['type']) . '.php'))
        {
            require_once(dirname(__FILE__) . '/wsInterfaces/' . strtolower($this->config['webservice']['type']) . '.php');

            $wsClassName = ucfirst($this->config['webservice']['type']);

            $this->api = new PaypePublicApi($this->config);
            $this->wsClient = new $wsClassName($this->config['webservice'], $this->api);

            if(!$this->wsClient instanceof WsInterface)
            {
                paypeLog('webservice ' . $this->config['webservice']['type'] . ' has to implement WsInterface', true);
                die('');
            }

            if(!empty($_GET['sync']))
            {
                // particular sync method is forced, do not call methods in config
                $this->config['sync'] = array($_GET['sync']);
            }

            $this->getSync();

            foreach($this->config['sync'] as $func)
            {
                // call all synchronization methods defined in config
                if(method_exists($this, $func))
                {
                    $this->$func();
                }
                else
                {
                    paypeLog('failed to find sync method: ' . $func, true);
                }
            }
        }
        else
        {
            paypeLog('failed to load webservice: ' . $this->config['webservice']['type'], true);
            die('');
        }
    }

    // send customers added in local system to Paype
    private function customersPush()
    {
        paypeLog('customersPush');

        $lastCustomerId = !empty($this->sync->last_customer_push_id) ? $this->sync->last_customer_push_id : '';

        // get customers added to your system after last synced customer (by id last_customer_push_id)
        $customers = $this->wsClient->getCustomers($lastCustomerId);

        foreach($customers as $customer)
        {
            try
            {
                // send customer data to paype
                if($_GET['update'])
                {
                    $this->api->updateCustomer($customer->customer_id, $customer);
                }
                else
                {
                    $this->api->postCustomer($customer);
                }
            }
            catch(Exception $e)
            {
                paypeLog('customersPush customer api post failed: ' . $e->getMessage() . ' customer: ' . json_encode($customer), true);
            }
        }
    }

    // get customers added in Paype to local system
    private function customersPull()
    {
        paypeLog('customersPull');

        $currentTime = $createdWithin = time();

        if(!empty($this->config['interval']))
        {
            $createdWithin = $this->config['interval'];
        }
        else if(!empty($this->sync->last_customer_pull_time))
        {
            $createdWithin = $currentTime - $this->sync->last_customer_pull_time + 1;
        }

        try
        {
            // get customers from Paype, use created_within time in seconds to only get customers added after last sync
            // unless force param is used
            $customers = $this->api->getCustomers($createdWithin);
        }
        catch(Exception $e)
        {
            paypeLog('customersPull customers api get fail: ' . $e->getMessage(), true);
            $customers = array();
        }

        // post customers to your system
        $this->wsClient->postCustomers($customers);
    }

    // sync flags held in Paype
    private function getSync()
    {
        if(empty($_GET['force']))
        {
            try
            {
                // unless force-d to sync all, get synchronization flags from Paype
                $this->sync = $this->api->getSync();
                if(empty($this->sync))
                {
                    throw new Exception('Failed to get sync flags');
                }
            }
            catch(Exception $e)
            {
                paypeLog($e->getMessage(), true);
                die('');
            }
        }
    }

    // highly recommended to use authentication methods, especially when serving integration over public web (not recommended)
    // example uses caller IP whitelisting
    private function auth($auth)
    {
        if(ISCLI)
        {
            return true;
        }

        $ip = $_SERVER['REMOTE_ADDR'];
        paypeLog('connecting from ip:'.$ip);

        return !empty($auth['ipWhitelist']) && is_array($auth['ipWhitelist']) && in_array($ip, $auth['ipWhitelist']);
    }
}
?>