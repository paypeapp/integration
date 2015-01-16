<?php
class Magento implements WsInterface
{
	private $client, $session;

	public function __construct($wsConfig, $paypeApi)
	{
		$this->client = new SoapClient(
			$wsConfig['location'],
			array(
				'soap_version'		=>	SOAP_1_2,
				'login'				=>	$wsConfig['webUsername'],
				'password'			=>	$wsConfig['webPassword'],
				'authentication'	=>	'SOAP_AUTHENTICATION_DIGEST',
				'trace'				=>	1,
				'exceptions'		=>	1,
				'cache_wsdl'		=>	'WSDL_CACHE_NONE'
			)
		);

		$this->session = $this->client->login($wsConfig['user'], $wsConfig['pwd']);
	}

	public function getCustomers($lastSyncCustomerId)
	{
		$customers = $returnCustomers = array();

		$customerMagentoId = 1;

		//this part here would find the Magento client_id of last synced customer
		if(!empty($lastSyncCustomerId) && false) //TODO
		{
			try
			{
				// TODO: This does not work, no customer_code filter:
				//$lastSyncedCustomer = $this->client->call($this->session, 'customer.list', array(array('customer_code' => array('eq' => $lastSyncCustomerId))));
				// TODO: This would work, but sync id has to be communicated differently:
				//$lastSyncedCustomer = $this->client->call($this->session, 'customer.list', array(array('customer_id' => array('eq' => 1000))));
				paypeLog('lastSynced customer '.json_encode($lastSyncedCustomer));

				if(!empty($lastSyncedCustomer['client_id']))
				{
					$customerMagentoId = $lastSyncedCustomer['client_id'];
				}
			}
			catch(Exception $e)
			{
				paypeLog('magento last synced customer read fail: ' . $e->getMessage(), true); // most probably not found, carry on starting to get from first
			}
		}

		while(count($customers) < 5) // TODO: how many to query at once TBD
		{
			try
			{
				$customers[] = $this->client->call($this->session, 'customer.info', $customerMagentoId);
				$customerMagentoId++;
			}
			catch(Exception $e)
			{
				paypeLog('magento customersPush customer read fail: ' . $e->getMessage(), true);
				break; // most probably no more customers
			}
		}

		// loop for customer data formatting
		foreach($customers as $customer)
		{
			if(!empty($customer['client_code']))
			{
				$newCustomer = array(
					'first_name' => $customer['firstname'] . ' ' . $customer['middlename'],
					'last_name' => $customer['lastname'],
					'email' => $customer['email'],
					'customer_id' => $customer['client_code']
				);

				if(!empty($customer['dob']))
				{
					$newCustomer['birthday'] = explode(' ', $customer['dob'])[0];
				}
				if(!empty($customer['gender']))
				{
					$newCustomer['gender'] = str_replace('1', 'male', str_replace('2', 'female', $customer['gender']));
				}

				$returnCustomers[] = $newCustomer;
			}
		}

		return $returnCustomers;
	}

	public function postCustomers($customers)
	{
		foreach($customers as $c)
		{
			$create = array();
			$create['email'] = $c->email;
			$create['firstname'] = $c->first_name;
			$create['lastname'] = $c->last_name;
			$create['client_code'] = $c->customer_id;

			if(!empty($c->birthday))
			{
				$create['dob'] = date("Y-m-d 00:00:00", strtotime($c->birthday));
			}
			if(!empty($c->gender))
			{
				$create['gender'] = str_replace('male', '1', str_replace('female', '2', $c->gender));
			}

			try
			{
				$res = $this->client->call($this->session, 'customer.create', array($create));
				paypeLog('magento customerPull create res: ' . json_encode($res));
			}
			catch(Exception $e)
			{
				paypeLog('magento customerPull create fail: ' . $e->getMessage() . ' for ' . json_encode($create), true);
			}
		}
	}
}