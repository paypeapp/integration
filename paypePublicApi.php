<?php

/*
 * Making calls to Paype public API
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
            paypeLog($errMsg, true);
            die('');
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
        $customers = $this->curl(array('created_within' => $createdWithin));

        if(!empty($customers) && !is_array($customers))
        {
            // 1 customer returned, make into array
            $customers = array($customers);
        }

        return $customers;
    }

    public function postCustomer($customer)
    {
        $this->endpoint = 'customers';
        return $this->curl($customer, 'POST');
    }

    public function updateCustomer($uuid, $customerUpdateFields)
    {
        $this->endpoint = 'customers/'. $uuid;
        return $this->curl($customerUpdateFields, 'PUT');
    }

    public function getSync()
    {
        $this->endpoint = 'sync';
        return $this->curl();
    }

    // build call url with parameters, key, nonce and auth signature
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

    // call Paype API and return the response
    private function curl($params = null, $method = 'GET')
    {
        $headers = array(
            'Method: ' . $method,
            'Connection: Keep-Alive',
            'User-Agent: ' . $this->partner . '-Paype-rest-curl',
            'Content-Type: application/json'
        );

        $rest = curl_init();

        if($method == 'POST' || $method == 'PUT')
        {
            curl_setopt($rest, CURLOPT_URL, $this->getUrl());
            curl_setopt($rest, CURLOPT_POST, 1);
            curl_setopt($rest, CURLOPT_POSTFIELDS, json_encode($params));
            curl_setopt($rest, CURLOPT_POST, 1);
            curl_setopt($rest, CURLOPT_CUSTOMREQUEST, $method);
        }
        else
        {
            curl_setopt($rest, CURLOPT_URL, $this->getUrl($params));
        }
        curl_setopt($rest, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($rest, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($rest, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($rest);

        curl_close($rest);
        paypeLog('rest-api-response ' . $method . ' ' . json_encode($params) . ': ' . json_encode($response));

        if(!empty($response->error))
        {
            throw new Exception($response->error->message);
        }

        return json_decode($response);
    }

    public function setEndpoint($e)
    {
        $this->endpoint = $e;
    }
}