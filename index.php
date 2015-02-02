<?php
// is the script run as command-line tool
define('ISCLI', PHP_SAPI === 'cli');
// disable running time limit
set_time_limit(0);

if(ISCLI)
{
    // add command-line parameters to GET parameters array
    parse_str(implode('&', array_slice($argv, 1)), $_GET);
}

function paypeLog($msg, $alwaysLog = false)
{
    if(!empty($_GET['verbose']) || $alwaysLog)
    {
        error_log('[paype-sync] ' . substr($msg, 0, 1000));
        if(!ISCLI)
        {
            echo $msg . '<br>';
        }
    }
}

require_once('paypePublicApi.php');
require_once('syncHandler.php');
require_once('wsInterfaces/wsInterface.php');
require_once('library.php');

new SyncHandler(require('config.php'));