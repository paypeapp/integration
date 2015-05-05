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
		/*$axaXml = '<?xml version="1.0" encoding="UTF-8"?><E-Document><Document>';*/

		foreach($customers as $c)
		{
			$axaXml = '<?xml version="1.0" encoding="UTF-8"?><E-Document><Document>';

			$address = json_decode($c->meta_data);

			$axaCustomer = array();
			$axaCustomer['Name'] = $c->first_name;
			$axaCustomer['Surname'] = $c->last_name;
			$axaCustomer['RegNum'] = $c->customer_id;
			$axaCustomer['ContactData'] = array(
				'Phone' => $c->phone_number,
				'PhoneCountryCode' => $c->phone_country,
				'EmailAddress' => $c->email,
				'Address' => array(
					'Country' => 'Eesti',
					'CountryCode' => 'EE',
					'County' => (!empty($address->county)?$address->county:''),
					'Street' => (!empty($address->street)?$address->street:''),
					'City' => (!empty($address->city)?$address->city:''),
					'PostalCode' => (!empty($address->zip)?$address->zip:''),
					'HouseNo' => (!empty($address->house)?$address->house:''),
					'ApartmentNo' => (!empty($address->apartment)?$address->apartment:'')
				)
			);

			$axaCustomer['AcceptsDataProcessing'] = 'Jah';
			$axaCustomer['AcceptsSpecialOffers'] = 'Jah';
			$axaCustomer['PaypeId'] = $c->token;
			$axaCustomer['CustAccount'] = null;
			$axaCustomer['VATRegNum'] = null;
			$axaCustomer['Currency'] = 'EUR';
			$axaCustomer['CustType'] = 'ERAISIK';

			$axaXml .= '<DocumentCust>' . $this->arrayToXml($axaCustomer) . '</DocumentCust>';

			$axaXml .= '</Document></E-Document>';

			$response = $this->client->__soapCall('CallXMLAction', array(array('action'=>'createClient_MCC','xml'=>$axaXml)));

			paypeLog('axa res > ' . json_encode($response));
		}
	}

	private function arrayToXml($array)
	{
		$xml = '';
		foreach($array as $key=>$value)
		{
			if(is_array($value))
			{
				$value = $this->arrayToXml($value);
			}
			$xml .= '<'.$key.'>'.$value.'</'.$key.'>';
		}

		return $xml;
	}
}