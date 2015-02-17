<?php
// mac os x: http://voyte.ch/getting-php-in-homebrew-to-access-mssql-via/
class Mssql implements WsInterface
{
	private $db;

	public function __construct($wsConfig, $paypeApi)
	{
		if(!extension_loaded('mssql'))
		{
			$errMsg = 'mssql extension not added';
			paypeLog($errMsg, true);
			die('');
		}

		$this->db = mssql_connect($wsConfig['location'], $wsConfig['user'], $wsConfig['pwd']);

		mssql_select_db($wsConfig['db'], $this->db);
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
			// try creating the customer
			$result = $this->createOrUpdateCustomer($c);

			if($result != 0)
			{
				// TODO: client exists, get their id and update
			}
		}
	}

	private function createOrUpdateCustomer($customer, $id=null)
	{
		$empty = null;
		$result = null;

		$sql = mssql_init('web_updateClientInfo2');
		
		if(empty($id))
		{	
			// create user	
			mssql_bind($sql, '@ClientCardID', $id, SQLINT4, true, true);
		}
		else
		{
			// update user
			mssql_bind($sql, '@ClientCardID', $id, SQLINT4);
		}
		mssql_bind($sql, '@FirstName', $customer->first_name, SQLVARCHAR);
		mssql_bind($sql, '@LastName', $customer->last_name, SQLVARCHAR);
		mssql_bind($sql, '@Mail', $customer->email, SQLVARCHAR);
		mssql_bind($sql, '@Language', $customer->language, SQLVARCHAR);
		mssql_bind($sql, '@Phone', $customer->phone_international, SQLVARCHAR);
		mssql_bind($sql, '@card', $customer->customer_id, SQLVARCHAR);
		
		$sex = 0;
		if(!empty($customer->gender))
		{
			$sex = ($customer->gender == 'male') ? 1 : 2;
		}
		mssql_bind($sql, '@Sex', $sex, SQLINT1);

		if(empty($customer->birthday))
		{
			mssql_bind($sql, '@BirthDate', $customer->birthday, SQLVARCHAR, false, true);
		}
		else
		{
			$customer->birthday = date('Y-m-d 00:00:00', strtotime($customer->birthday));
			mssql_bind($sql, '@BirthDate', $customer->birthday, SQLVARCHAR);
		}

		$emptyParams = array('Town', 'Borough', 'ZIP', 'ImageURL', 'Address');
		foreach($emptyParams as $e)
		{
			mssql_bind($sql, '@'.$e, $empty, SQLVARCHAR, false, true);
		}

		$emptyOutputParams = array('MessageDlg', 'IdentificationCode', 'info', 'info2', 'info3', 'WelcomeText', 'Title');
		foreach($emptyOutputParams as $e)
		{
			mssql_bind($sql, '@'.$e, $empty, SQLVARCHAR, true, true);
		}

		mssql_bind($sql, '@Result', $result, SQLINT1, true, true);

		mssql_execute($sql);

		mssql_free_statement($sql);

		return $result;
	}
}