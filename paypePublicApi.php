<?php

/*
 * Making calls to Paype public API, documented at https://business.paype.me/api-docs.html
 */
class PaypePublicApi
{
    private $location;
    private $key;
    private $secret;
    private $endpoint;
    private $partner;
    private $nonce;

    public function __construct($config)
    {
        if(empty($config['api']['location']) || empty($config['api']['key']) || empty($config['api']['secret']))
        {
            $errMsg = 'api credentials not set in config';
            $this->log($errMsg, true);
            die('Fail');
        }

        $this->location =  $config['api']['location'];
        $this->key = $config['api']['key'];
        $this->secret = $config['api']['secret'];

        // create nonce of current time in microseconds
        $nonce = explode(' ', microtime());
        $this->nonce = $nonce[1] . str_pad(substr($nonce[0], 2, 6), 6, '0');

        $this->endpoint = 'customers';
        $this->partner = $config['partnerName'];
    }

    public function getCustomers($createdWithin)
    {
        $this->endpoint = 'customers';
        return $this->curl(array('created_within' => $createdWithin));
    }

    public function postCustomer($customer)
    {
        $this->endpoint = 'customers';
        return $this->curl($customer, 'POST');
    }

    public function getSync()
    {
        $this->endpoint = 'sync';
        return $this->curl();
    }

    private function getUrl($queryParams = null)
    {
        $url = $this->location . '/' . $this->endpoint . '?';

        $this->nonce++;
        $signature = hash('sha256', $this->nonce.$this->key.$this->secret);

        $params = array(
            'nonce' => $this->nonce,
            'key' => $this->key,
            'signature' => $signature
        );

        if($queryParams)
        {
            $params = array_merge($queryParams, $params);
        }

        $url = $url . http_build_query($params);

        return $url;
    }

    private function curl($params = null, $method = 'GET')
    {
        $headers = array(
            'Method: ' . $method,
            'Connection: Keep-Alive',
            'User-Agent: ' . $this->partner . '-Paype-rest-curl',
            'Content-Type: application/json'
        );

        $rest = curl_init();
        if($method == 'POST')
        {
            curl_setopt($rest,CURLOPT_URL, $this->getUrl());
            curl_setopt($rest,CURLOPT_POST, 1);
            curl_setopt($rest,CURLOPT_POSTFIELDS, json_encode($params));
        }
        else
        {
            curl_setopt($rest,CURLOPT_URL, $this->getUrl($params));
        }
        curl_setopt($rest,CURLOPT_HTTPHEADER, $headers);
        curl_setopt($rest,CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($rest,CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($rest);

        curl_close($rest);
        $this->log('rest-api-response ' . $method . ' ' . json_encode($params) . ': ' . json_encode($response));

        if(!empty($response->error))
        {
            throw new Exception($response->error->message);
        }

        return $response;
    }

    private function log($msg, $alwaysLog = false)
    {
        if(!empty($_GET['verbose']) || $alwaysLog)
        {
            error_log('[paype-sync] ' . substr($msg, 0, 100));
            echo $msg . '<br>';
        }
    }

    public function setEndpoint($e)
    {
        $this->endpoint = $e;
    }
}