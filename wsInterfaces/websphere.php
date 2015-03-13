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
			$sendCustomerToWebsphere = false;

			$cardInfo = new CustomerCardInfo();
			$cardInfo->email = $c->email;
			$cardInfo->firstName = $c->first_name;
			$cardInfo->lastName = $c->last_name;
			$cardInfo->language = $c->language;
			if(preg_match("'^\d{16}$'", $c->customer_id))
			{
				$cardInfo->cardNo = $c->customer_id;
				$sendCustomerToWebsphere = true;
			}
			elseif(Library::validateEstonianPersonalCode($c->customer_id))
			{
				$cardInfo->idCode = $c->customer_id;
				$sendCustomerToWebsphere = true;
			}
			$cardInfo->phone = $c->phone_international;
			$cardInfo->birthDate = !empty($c->birthday) ? date('Y-m-d', strtotime($c->birthday)) : null;
			$cardInfo->agreedToTerms = true;

			$post = new PostCustomerCardInfo();
			$post->arg0 = $cardInfo;

			try
			{
				if($sendCustomerToWebsphere)
				{
					$res = $this->client->__soapCall('postCustomerCardInfo', array($post));

					paypeLog('websphere customerPull create res: ' . json_encode($res));
				}

				// if customer post was a success and we got new card number we can send it back to API and activate the customer card
				if($sendCustomerToWebsphere && !empty($res->return) && strlen($res->return->cardNo) == 16)
				{
					$this->api->updateCustomer($c->token, array('customer_id' => $res->return->cardNo, 'active' => true));
				}
				else if(empty($res->return->statusCode) || in_array($res->return->statusCode, array(2, 4)))
				{
					// rest of statusCodes will leave the customer inactive
					// INVALID_INPUT_DATA or NO_MATCHES_FOUND will
					// send customer a message and then delete them
					$this->api->messageCustomer($c->token, 'Test');
					$this->api->deleteCustomer($c->token);
				}
			}
			catch(Exception $e)
			{
				paypeLog('websphere customerPull create fail: ' . $e->getMessage() . ' for ' . json_encode($post), true);
			}
		}
	}
}