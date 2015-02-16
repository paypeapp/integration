<?php
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

		$this->db = mssql_connect($wsConfig['location'], $wsConfig['user'], $wsConfig['pwd'])
			or die("Couldn't connect to SQL Server on ". $wsConfig['location']);


		mssql_select_db($wsConfig['db'], $this->db)
			or die("Couldn't open database " . $wsConfig['db']);
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
			$empty = null;
			$result = null;

			$sql = mssql_init('web_updateClientInfo2');
			mssql_bind($sql, '@ClientCardID', $c->customer_id, SQLINT4); // TODO: check the field type
			mssql_bind($sql, '@FirstName', $c->first_name, SQLVARCHAR);
			mssql_bind($sql, '@LastName', $c->last_name, SQLVARCHAR);
			mssql_bind($sql, '@Mail', $c->email, SQLVARCHAR);
			mssql_bind($sql, '@Language', $c->language, SQLVARCHAR);
			mssql_bind($sql, '@Phone', $c->phone_international, SQLVARCHAR);
			mssql_bind($sql, '@BirthDate', $c->birthday, SQLVARCHAR);
			mssql_bind($sql, '@IdentificationCode', $c->customer_id, SQLVARCHAR);
			$sex = 0;
			if(!empty($c->gender))
			{
				$sex = ($c->gender == 'male')?1:2;
			}
			mssql_bind($sql, '@Sex', $sex, SQLINT1);

			$emptyParams = array('Town', 'Borough', 'ZIP', 'ImageURL', 'Address');
			foreach($emptyParams as $e)
			{
				mssql_bind($sql, '@'.$e, $empty, SQLVARCHAR, false, true);
			}

			$emptyOutputParams = array('MessageDlg', 'card', 'info', 'info2', 'info3', 'WelcomeText', 'Title');
			foreach($emptyOutputParams as $e)
			{
				mssql_bind($sql, '@'.$e, $empty, SQLVARCHAR, true, true);
			}
			mssql_bind($sql, '@Result', $result, SQLINT1, true, true);

			mssql_execute($sql);

			// paypeLog('debug: mssql update result ' . $result);
			// TODO: non-0 result needs logging and action

			mssql_free_statement($sql);
		}
	}
}