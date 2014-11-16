<?php
require_once('config.php');
require_once('paypePublicApi.php');
require_once('partnerCommunicator.php');

Class SyncHandler
{
    public function __construct($config)
    {
        $this->log('start');
        $t1 = time();
        set_time_limit(0);

        if(!$this->auth($config['auth']))
        {
            $this->log('auth failed', true);
            die('Access denied. Try harder, or do not try at all!');
        }

        $this->sync($config);
        $this->log('finished in ' . time() - $t1 . ' seconds');
    }

    private function sync($config)
    {
        if(!empty($config['webservice']['type']) && file_exists('wsInterfaces/' . $config['webservice']['type'].'.php'))
        {
            require_once('wsInterfaces/' . $config['webservice']['type'] . '.php');

            $wsClassName = ucfirst($config['webservice']['type']);

            $wsFactory = new $wsClassName($config['webservice']);
            $ws = $wsFactory->getClient();

            $partnerCommunication = new PartnerCommunicator($ws, new PaypePublicApi($config), $config);
            foreach($config['sync'] as $func)
            {
                $partnerCommunication->$func();
            }
        }
        else
        {
            $this->log('failed to load webservice: ' . $config['webservice']['type'], true);
        }
    }

    private function auth($auth)
    {
        $ip = $_SERVER['REMOTE_ADDR'];
        $this->log('connecting from ip:'.$ip);

        return !empty($auth['ipWhitelist']) && is_array($auth['ipWhitelist']) && in_array($ip, $auth['ipWhitelist']);
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

new SyncHandler($syncConfig);