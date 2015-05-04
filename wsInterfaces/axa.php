<?php
class Axa implements WsInterface
{
	private $client;

	public function __construct($wsConfig, $paypeApi)
	{
		$this->client = new SoapClient($wsConfig['location']);
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
			$axaCustomer = array();
			$axaCustomer['Name'] = $c->first_name;
			$axaCustomer['Surname'] = $c->last_name;
			$axaCustomer['RegNum'] = $c->customer_id;
			$axaCustomer['Phone'] = $c->phone_international;
			$axaCustomer['EmailAddress'] = $c->email;

			$response = $this->client->__soapCall('CreateClient', array(array('xml'=>$this->arrayToXml($axaCustomer))));

			paypeLog('axa res > ' . json_encode($response));
		}
	}

	private function arrayToXml($array)
	{
		$xml = '';
		foreach($array as $key=>$value)
		{
			$xml .= '<'.$key.'>'.$value.'</'.$key.'>';
		}

		return $xml;
	}
}