<?php
class PostCustomerCardInfo
{
	/** @var CustomerCardInfo */
	public $arg0; // CustomerCardInfo
}

class CustomerCardInfo {
	/** @var String */
	public $cardNo; // String
	/** @var String */
	public $idCode; // String
	/** @var String */
	public $firstName; // String
	/** @var String */
	public $lastName; // String
	/** @var DateTime */
	public $birthDate; // DateTime
	/** @var String */
	public $email; // String
	/** @var String */
	public $phone; // String
	/** @var Address */
	public $address; // Address
	/** @var String */
	public $language; // String
	/** @var Boolean */
	public $agreedToTerms; // Boolean
}

class Websphere implements WsInterface
{
	private $client, $api;

	public function __construct($wsConfig, $paypeApi)
	{
		$this->client = new SoapClient(
			$wsConfig['location'],
			array(
				'soap_version'		=>	SOAP_1_1,
				'login'				=>	$wsConfig['webUsername'],
				'password'			=>	$wsConfig['webPassword'],
				'trace'				=>	1,
				'exceptions'		=>	1,
				'cache_wsdl'		=>	'WSDL_CACHE_NONE'
			)
		);
		$this->api = $paypeApi;
	}

	public function getCustomers($lastSyncCustomerId)
	{
		//TODO: not implemented
		return array();
	}

	public function postCustomers($customers)
	{

		foreach($customers as $c)
		{
			$updateCardNoInApi = false;

			$cardInfo = new CustomerCardInfo();
			$cardInfo->email = $c->email;
			$cardInfo->firstName = $c->first_name;
			$cardInfo->lastName = $c->last_name;
			$cardInfo->language = $c->language;
			if(strlen($c->customer_id) == 16)
			{
				$cardInfo->cardNo = $c->customer_id;
			}
			else
			{
				$updateCardNoInApi = true;
				$cardInfo->idCode = $c->customer_id;
			}
			$cardInfo->phone = $c->phone_international;
			$cardInfo->birthDate = !empty($c->birthday) ? date('Y-m-d', strtotime($c->birthday)) : null;
			$cardInfo->agreedToTerms = true;

			$post = new PostCustomerCardInfo();
			$post->arg0 = $cardInfo;

			try
			{
				$res = $this->client->__soapCall('postCustomerCardInfo', array($post));

				paypeLog('websphere customerPull create res: ' . json_encode($res));

				// if customer post was a success and we got new card number we can send it back to API and activate the customer card
				if($updateCardNoInApi && strlen($res->return->cardNo) == 16)
				{
					$this->api->updateCustomer($c->token, array('customer_id' => $res->return->cardNo, 'active' => true));
				}
			}
			catch(Exception $e)
			{
				paypeLog('websphere customerPull create fail: ' . $e->getMessage() . ' for ' . json_encode($post), true);
			}
		}
	}
}