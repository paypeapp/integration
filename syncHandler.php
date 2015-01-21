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
                }
                catch(Exception $e)
                {
                    paypeLog($e->getMessage(), true);
                    die('');
                }
            }

            foreach($config['sync'] as $func)
            {
                // call all synchronization methods defined in config
                $this->$func();
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

        // get customers added to your system after last synced customer (by id last_customer_push_id)
        $customers = $this->wsClient->getCustomers($this->sync->last_customer_push_id);

        foreach($customers as $customer)
        {
            try
            {
                // send customer data to paype
                $this->api->postCustomer($customer);
            }
            catch(Exception $e)
            {
                paypeLog('customersPush customer api post failed: ' . $e->getMessage(), true);
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
            $customers = $this->api->getCustomers($currentTime - $lastSyncTime);
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