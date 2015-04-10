<?php

Class SyncHandler
{
    private $wsClient;
    private $api;
    private $sync;

    public function __construct($config)
    {
        paypeLog('start');
        $t1 = time();

        if(!$this->auth($config['auth']))
        {
            paypeLog('auth failed', true);
            die('Access denied. Try harder, or do not try at all!');
        }

        $this->sync($config);

        paypeLog('finished in ' . (time() - $t1) . ' seconds');
    }

    private function sync($config)
    {
        if(!empty($config['webservice']['type']) && file_exists(dirname(__FILE__) . '/wsInterfaces/' . strtolower($config['webservice']['type']) . '.php'))
        {
            require_once(dirname(__FILE__) . '/wsInterfaces/' . strtolower($config['webservice']['type']) . '.php');

            $wsClassName = ucfirst($config['webservice']['type']);

            $this->api = new PaypePublicApi($config);
            $this->wsClient = new $wsClassName($config['webservice'], $this->api);

            if(!$this->wsClient instanceof WsInterface)
            {
                paypeLog('webservice ' . $config['webservice']['type'] . ' has to implement WsInterface', true);
                die('');
            }

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

            if(!empty($_GET['sync']))
            {
                // particular sync method is forced, do not call methods in config
                $config['sync'] = array($_GET['sync']);
            }

            foreach($config['sync'] as $func)
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
            paypeLog('failed to load webservice: ' . $config['webservice']['type'], true);
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
                $this->api->postCustomer($customer);
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

        $currentTime = time();
        $lastSyncTime = 0;

        if(!empty($this->sync->last_customer_pull_time))
        {
            $lastSyncTime = $this->sync->last_customer_pull_time;
        }

        try
        {
            // get customers from Paype, use created_within time in seconds to only get customers added after last sync
            // unless force param is used
            $createdWithin = $currentTime - $lastSyncTime + 1;
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