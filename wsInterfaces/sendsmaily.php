<?php

class Sendsmaily implements WsInterface
{
	private $location;
	private $key; // api key
	private $autoresponder; // id of autoresponder

	private $paypeApi;

	public function __construct($wsConfig, $paypeApi)
	{
		$this->location = $wsConfig['location'];
		$this->key = $wsConfig['key'];
		$this->autoresponder = $wsConfig['autoresponder'];

		$this->paypeApi = $paypeApi;
	}

	public function getCustomers($lastSyncCustomerId)
	{
		// NOT implemented
	}

	public function postCustomers($customers)
	{
		$postMultiple = array(
			'key' => $this->key,
			'remote' => 1
		);

		if(!empty($this->autoresponder))
		{
			$postMultiple['autoresponder'] = $this->autoresponder;
		}

		// add customers to post array
		foreach($customers as $c)
		{
			$create = array();

			$create['email'] = $c->email;
			$create['firstName'] = $c->first_name;
			$create['lastName'] = $c->last_name;
			$create['gender'] = $c->gender;
			$create['phone'] = $c->phone_international;
			$create['isic'] = intval($c->isic_enabled);
			$create['is_unsubscribed'] = intval(!$c->accept_newsletter);

			if(!empty($c->birthday))
			{
				$create['birthday'] = date("Y-m-d", strtotime($c->birthday));
			}

			if(!empty($c->meta_data))
			{
				try
				{
					$metaData = json_decode($c->meta_data);
					$create['piirkond'] = $metaData->area;
				}
				catch(Exception $e)
				{
					paypeLog('sendsmaily meta data json decode fail');
				}
			}

			paypeLog('sendsmaily customer add: ' . json_encode($create));

			$postMultiple[] = $create;
		}

		// build post
		$postMultiple = http_build_query($postMultiple);

		try
		{
			// send to sendsmaily api as curl post
			$result = $this->curl($postMultiple);
		}
		catch(Exception $e)
		{
			paypeLog('sendsmaily customerPull post fail: ' . $e->getMessage(), true);
		}
	}

	private function curl($postData)
    {
        $headers = array(
            'Method: POST',
            'Connection: Keep-Alive',
            'User-Agent: Paype'
        );

        $post = curl_init();

		curl_setopt($post, CURLOPT_URL, $this->location . '/magento-import'); // undocumented import endpoint with is_unsubscribed param
        curl_setopt($post, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($post, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($post, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($post, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($post); // JSON result

        curl_close($post);
        paypeLog('sendsmaily-api-response: ' . $response);

        $response = json_decode($response);
        return $response;
    }
}
