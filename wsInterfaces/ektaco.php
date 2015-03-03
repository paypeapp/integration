<?php
class Ektaco implements WsInterface
{
	private $db;

	public function __construct($wsConfig, $paypeApi)
	{
		$this->db = new PDO('dblib:host=' . $wsConfig['location'] . ';dbname='.$wsConfig['db'], $wsConfig['user'], $wsConfig['pwd']);
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

			if($result !== 0)
			{
				$result = $this->createOrUpdateCustomer($c, $this->getCustomerRowId($c));
			}

			if($result !== 0)
			{
				paypeLog('customer not added, update failed errorCode=' . $result . ' name=' . $c->first_name . ' ' . $c->last_name .
					' (id=' . $c->customer_id . ')', true);
			}
		}
	}

	private function createOrUpdateCustomer($customer, $id=null)
	{
		$empty = null;
		$result = null;

		try
		{
			$sql = $this->db->prepare('exec web_updateClientInfo2 :ClientCardID, :FirstName, :LastName, :Phone, :Mail, , :BirthDate, , , , :Language, :Sex, , , :card, , , , , , , :Result');

			$sql->bindParam(':ClientCardID', $id, PDO::PARAM_INT|PDO::PARAM_INPUT_OUTPUT, 10); // null will create, else update
			$sql->bindParam(':FirstName', $customer->first_name, PDO::PARAM_STR|PDO::PARAM_INPUT_OUTPUT, 40);
			$sql->bindParam(':LastName', $customer->last_name, PDO::PARAM_STR|PDO::PARAM_INPUT_OUTPUT, 40);
			$sql->bindParam(':Mail', $customer->email, PDO::PARAM_STR|PDO::PARAM_INPUT_OUTPUT, 50);
			$sql->bindParam(':Phone', $customer->phone_international, PDO::PARAM_STR|PDO::PARAM_INPUT_OUTPUT, 50);
			$sql->bindParam(':card', $customer->customer_id, PDO::PARAM_STR|PDO::PARAM_INPUT_OUTPUT, 40);
			$sql->bindParam(':Language', $customer->language, PDO::PARAM_STR);

			$sex = 0;
			if(!empty($customer->gender))
			{
				$sex = ($customer->gender == 'male') ? 1 : 2;
			}
			$sql->bindParam(':Sex', $sex, PDO::PARAM_INT);

			if(empty($customer->birthday))
			{
				$sql->bindParam(':BirthDate', $customer->birthday, PDO::PARAM_STR);
			}
			else
			{
				$customer->birthday = date('Y-m-d 00:00:00', strtotime($customer->birthday));
				$sql->bindParam(':BirthDate', $customer->birthday, PDO::PARAM_STR);
			}
			/*
			$emptyParams = array('Town', 'Borough', 'ZIP', 'ImageURL', 'Address');
			foreach($emptyParams as $e)
			{
				$sql->bindParam(':'.$e, $empty, PDO::PARAM_STR, 200);
			}

			$emptyOutputParams = array('MessageDlg', 'IdentificationCode', 'info', 'info2', 'info3', 'WelcomeText', 'Title');
			foreach($emptyOutputParams as $e)
			{
				$sql->bindParam(':'.$e, $empty, PDO::PARAM_STR|PDO::PARAM_INPUT_OUTPUT, 250);
			}
			*/
			$sql->bindParam(':Result', $result, PDO::PARAM_INT|PDO::PARAM_INPUT_OUTPUT, 1);
			$r=$sql->execute();
   			paypeLog(json_encode($sql));
			paypeLog(json_encode($r)); // false
			paypeLog('res = '.$result); // ''
		}
		catch(Exception $e)
		{
			paypeLog('ektaco customerPull create or update fail: ' . $e->getMessage() . ' for ' . json_encode($customer), true);
		}

		return $result;
	}

	// get customer SQL RowID by their Paype customer id
	private function getCustomerRowId($customer)
	{
		$sql = $this->db->prepare('select RowID as id from Clientcard where card=:customer_id or Mail=:email');
		$sql->bindParam(':customer_id', $customer->customer_id, PDO::PARAM_STR);
		$sql->bindParam(':email', $customer->email, PDO::PARAM_STR);
		$sql->execute();

		$result = $sql->fetchObject();

		if(empty($result->id))
		{
			paypeLog('can not find customer in mssql to update');
			return 0;
		}

		return $result->id;
	}
}