<?php

session_start();

// Erply API class http://erply.com/getting-started-with-erply-api/?lang=php
class EAPI
{
	const VERIFY_USER_FAILURE = 2001;
	const CURL_ERROR = 2002;
	const PHP_SESSION_NOT_STARTED = 2003;
	const MISSING_PARAMETERS = 2004;

	public $url;
	public $clientCode;
	public $username;
	public $password;
	public $sslCACertPath;

	public function __construct($url = null, $clientCode = null, $username = null, $password = null, $sslCACertPath = null)
	{
		$this->url = $url;
		$this->clientCode = $clientCode;
		$this->username = $username;
		$this->password = $password;
		$this->sslCACertPath = $sslCACertPath;
	}

	public function sendRequest($request, $parameters = array())
	{
		//validate that all required parameters are set
		if(!$this->url OR !$this->clientCode OR !$this->username OR !$this->password){
			throw new Exception('Missing parameters', self::MISSING_PARAMETERS);
		}

		//add extra params
		$parameters['request'] = $request;
		$parameters['clientCode'] = $this->clientCode;
		$parameters['version'] = '1.0';
		if($request != "verifyUser") $parameters['sessionKey'] = $this->getSessionKey();

		//create request
		$handle = curl_init($this->url);

		//set the payload
		curl_setopt($handle, CURLOPT_POST, true);
		curl_setopt($handle, CURLOPT_POSTFIELDS, $parameters);

		//return body only
		curl_setopt($handle, CURLOPT_HEADER, 0);
		curl_setopt($handle, CURLOPT_RETURNTRANSFER, 1);

		//create errors on timeout and on response code >= 300
		curl_setopt($handle, CURLOPT_TIMEOUT, 45);
		curl_setopt($handle, CURLOPT_FAILONERROR, true);
		curl_setopt($handle, CURLOPT_FOLLOWLOCATION, false);

		//set up host and cert verification
		curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($handle, CURLOPT_SSLVERSION,3);
		curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
		if($this->sslCACertPath) {
			curl_setopt($handle, CURLOPT_CAINFO, $this->sslCACertPath);
			curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, true);
		}

		//run
		$response = curl_exec($handle);
		$error = curl_error($handle);
		$errorNumber = curl_errno($handle);
		curl_close($handle);
		if($error) throw new Exception('CURL error: '.$response.':'.$error.': '.$errorNumber, self::CURL_ERROR);
		return $response;
	}

	protected function getSessionKey()
	{
		//test for session
		if(!isset($_SESSION)) throw new Exception('PHP session not started', self::PHP_SESSION_NOT_STARTED);

		//if no session key or key expired, then obtain it
		if(
			!isset($_SESSION['EAPISessionKey'][$this->clientCode][$this->username]) ||
			!isset($_SESSION['EAPISessionKeyExpires'][$this->clientCode][$this->username]) ||
			$_SESSION['EAPISessionKeyExpires'][$this->clientCode][$this->username] < time()
		) {
			//make request
			$result = $this->sendRequest("verifyUser", array("username" => $this->username, "password" => $this->password));
			$response = json_decode($result, true);

			//check failure
			if(!isset($response['records'][0]['sessionKey'])) {
				unset($_SESSION['EAPISessionKey'][$this->clientCode][$this->username]);
				unset($_SESSION['EAPISessionKeyExpires'][$this->clientCode][$this->username]);

				$e = new Exception('Verify user failure', self::VERIFY_USER_FAILURE);
				$e->response = $response;
				throw $e;
			}

			//cache the key in PHP session
			$_SESSION['EAPISessionKey'][$this->clientCode][$this->username] = $response['records'][0]['sessionKey'];
			$_SESSION['EAPISessionKeyExpires'][$this->clientCode][$this->username] = time() + $response['records'][0]['sessionLength'] - 30;

		}

		//return cached key
		return $_SESSION['EAPISessionKey'][$this->clientCode][$this->username];
	}
}

class Erply implements WsInterface
{
	private $client;
	private $customerGroups;
	private $paypeApi;

	public function __construct($wsConfig, $paypeApi)
	{
		$this->client = new EAPI();

		$this->client->clientCode = $wsConfig['clientCode'];
		$this->client->username = $wsConfig['user'];
		$this->client->password = $wsConfig['pwd'];
		$this->client->url = 'https://' . $wsConfig['clientCode'] . '.erply.com/api/';

		$this->paypeApi = $paypeApi;
	}

	public function getCustomers($lastSyncTimestamp, $customers=array(), $pageNo = 1)
	{
		$returnCustomers = $customers;

		$getParams = array();
		$getParams['recordsOnPage'] = 100;
		$getParams['pageNo'] = $pageNo;
		if(!empty($this->paypeApi->sync->last_customer_pull_time))
		{
			// using pull timestamp - last sync time in unix ts
			$getParams['changedSince'] = $this->paypeApi->sync->last_customer_pull_time;
		}

		try
		{
			$result = $this->client->sendRequest("getCustomers", $getParams);

			$res = json_decode($result);
			if($res->status->responseStatus != 'ok')
			{
				throw new Exception($result); //json of response
			}
		}
		catch(Exception $e)
		{
			paypeLog('erply customerPull create fail: ' . $e->getMessage() . ' for ' . json_encode($create), true);
		}

		// loop for customer data formatting
		foreach($res->records as $customer)
		{
			if(!empty($customer->customerID) && !empty($customer->email))
			{
				$newCustomer = array(
					'first_name' => $customer->firstName,
					'last_name' => $customer->lastName,
					'email' => $customer->email,
					'customer_id' => $customer->customerID,
					'status' => $customer->groupName
				);

				if(!empty($customer->gender))
				{
					$newCustomer['gender'] = $customer->gender;
				}

				if(!empty($customer->phone))
				{
					$newCustomer['phone'] = preg_replace('/[^0-9]/', '', $customer->phone);
				}

				if(!empty($customer->birthday))
				{
					$newCustomer['birthday'] = $customer->birthday;
				}

				$returnCustomers[] = $newCustomer;
			}
		}

		if($res->status->recordsInResponse == 100)
		{
			return $this->getCustomers($lastSyncTimestamp, $returnCustomers, $pageNo+1);
		}

		return $returnCustomers;
	}

	public function postCustomers($customers)
	{
		foreach($customers as $c)
		{
			$create = array();
			if(Library::validateEstonianPersonalCode($c->customer_id))
			{
				// if its estonian personal code new customer created in Erply and we send personal code
				$create['code'] = $c->customer_id;
			}
			elseif($c->customer_id[0] != 'P') // if the customer ID has P prefix its a new customer without personal code and save should be called without customerID
			{
				// customer exists and their Erply and Paype ID should match, this is update in Erply saveCustomer
				$create['customerID'] = $c->customer_id;
			}
			$create['email'] = $c->email;
			$create['firstName'] = $c->first_name;
			$create['lastName'] = $c->last_name;
			$create['gender'] = $c->gender;
			$create['phone'] = $c->phone_international;
			$create['groupID'] = $this->getCustomerGroupId($c->status);
			if(!empty($c->birthday))
			{
				$create['birthday'] = date("Y-m-d", strtotime($c->birthday));
			}

			try
			{
				$result = $this->client->sendRequest("saveCustomer", $create);

				$res = json_decode($result);
				if($res->status->responseStatus != 'ok')
				{
					throw new Exception($result); //json of response
				}
				paypeLog('erply postCustomers debug: ' . $result . ' create :' .  json_encode($create));

				if(!empty($res->records[0]->customerID) && $res->records[0]->alreadyExists == 0)
				{
					// newly created customer returns Erply ID, save this in Paype as customer ID
					$this->paypeApi->updateCustomer($c->token, array('customer_id' => $res->records[0]->customerID, 'active' => true));
				}
			}
			catch(Exception $e)
			{
				paypeLog('erply customerPull create fail: ' . $e->getMessage() . ' for ' . json_encode($create), true);
			}
		}
	}

	private function getCustomerGroups()
	{
		if(empty($this->customerGroups))
		{
			try
			{
				$result = $this->client->sendRequest("getCustomerGroups", array());

				$res = json_decode($result);
				if($res->status->responseStatus != 'ok')
				{
					throw new Exception($result); //json of response
				}
			}
			catch(Exception $e)
			{
				paypeLog('erply customerPush getCustomerGroups fail: ' . $e->getMessage(), true);
			}

			$this->customerGroups = $res->records;
		}

		return $this->customerGroups;
	}

	private function getCustomerGroupId($groupName)
	{
		foreach($this->getCustomerGroups() as $group)
		{
			if($group->name == $groupName)
			{
				return $group->customerGroupID;
			}
		}

		// none was found
		return $this->createCustomerGroup($groupName);
	}

	private function createCustomerGroup($groupName)
	{
		$returnId = null;

		$create = array();
		$create['name'] = $groupName;

		try
		{
			$result = $this->client->sendRequest("saveCustomerGroup", $create);

			$res = json_decode($result);
			if($res->status->responseStatus != 'ok')
			{
				throw new Exception($result); //json of response
			}
			paypeLog('erply debug createCustomerGroup: ' . $result);

			if(!empty($res->records[0]->customerGroupID))
			{
				$returnId = $res->records[0]->customerGroupID;
			}
		}
		catch(Exception $e)
		{
			paypeLog('erply customerPull saveCustomerGroup fail: ' . $e->getMessage() . ' for ' . json_encode($create), true);
		}

		return $returnId;
	}
}