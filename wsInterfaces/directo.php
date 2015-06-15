<?php
class Directo implements WsInterface
{
	private $url, $api, $key;

	public function __construct($wsConfig, $paypeApi)
	{
		$this->url = $wsConfig['location'] . '/xmlcore.asp';
		$this->key = $wsConfig['key'];
		$this->api = $paypeApi;
	}

	public function getCustomers($lastSyncCustomerId)
	{
		// TODO: not implemented
		$customers = array();
		return $customers;
	}

	public function postCustomers($customers)
	{
		foreach($customers as $c)
		{
			$result = $this->curlPostCustomer($c);

			if(!empty($result['Result']))
			{
				paypeLog('directo-api-response > ' . json_encode($c) . ': ' . json_encode($result));

				if($result['Result']['@attributes']['Type'] == 0)
				{
					$code = $result['Result']['@attributes']['code'];
					paypeLog('success, call update with code ' . $code);

					$this->api->updateCustomer($c->token, array('customer_id' => $code));
				}
			}
		}
	}


	private function curlGetCustomer($customer)
	{
		$headers = array(
			'Method: GET',
			'Connection: Keep-Alive',
			'User-Agent: Paype-sync'
		);

		$curl = curl_init();

		// TODO?
	}

	private function curlPostCustomer($customer)
	{
		if(!Library::validateEstonianPersonalCode($customer->customer_id))
		{
			paypeLog('customer '.$customer->customer_id.' not new with personal code as id, disregard post');
			return;
		}

		$headers = array(
			'Method: POST',
			'Connection: Keep-Alive',
			'User-Agent: Paype-sync',
			'Content-Type: application/x-www-form-urlencoded'
		);

		$curl = curl_init();

		curl_setopt($curl, CURLOPT_URL, $this->url);
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

		$postFields = array('put'=>1, 'what'=>'customer');
		$postFields['xmldata'] = '<?xml version="1.0" encoding="utf-8"?><customers>';
		$postFields['xmldata'] .= '<customer appkey="'.$this->key.'" loyaltycard="'.$customer->customer_id.'" name="'.$customer->first_name.' '.$customer->last_name.'"></customer>';
		$postFields['xmldata'] .= '</customers>';

		curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($postFields));

		$response = curl_exec($curl);

		return json_decode(json_encode(simplexml_load_string($response)), true);
	}
}