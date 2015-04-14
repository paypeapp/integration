<?php
require_once('navision.php');
class Navision2 extends Navision
{
    public function getCustomers($lastSyncCustomerId)
    {
		return array(); // TODO: not yet syncing everything
		$returnCustomers = array();

		$read = array(
			'filter' => array(
				array('Field'=>'Paype_ID', 'Criteria'=>'') // only ask ones with empty Paype ID - not synced yet
			),
			'bookmarkKey' => null,
			'setSize' => 1000
		);

		if(!empty($lastSyncCustomerId))
		{
			$read['filter'][] = array('Field'=>'No', 'Criteria'=>$lastSyncCustomerId . '..'); // start from last synced ID
		}

		try
		{
			$customers = $this->client->ReadMultiple($read);
		}
		catch(Exception $e)
		{
			paypeLog('navision customersPush customer read fail: ' . $e->getMessage(), true);
		}

		if(empty($customers->ReadMultiple_Result->Customers))
		{
			$customers = array();
		}
		else
		{
			$customers = $customers->ReadMultiple_Result->Customers;
		}

		if(!is_array($customers))
		{
			$customers = array($customers);
		}

		foreach($customers as $customer)
		{
			if(!empty($customer->No))
			{
				// TODO: split first and last name
				$returnCustomers[] = array(
					'first_name' => (!empty($customer->Name) ? $customer->Name : ''),
					'last_name' => (!empty($customer->Name) ? $customer->Name : ''),
					'email' => $customer->E_Mail,
					'customer_id' => $customer->Paype_ID
				);
			}
		}

		return $returnCustomers;
    }

	public function postCustomers($customers)
	{
		foreach($customers as $c)
		{
			$create = array();
			$create['_Email'] = $c->email;
			$create['_Name'] = $c->first_name . ' ' . $c->last_name;
			$create['_Phone'] = $c->phone_international;
			$create['customerID'] = $c->customer_id;
			$create['_CustomerNo'] = '';
			$create['_UpdateDiscount'] = $c->identification_count > 0;

			try
			{
				$debug = $this->client->CreateOrUpdateCustomer($create);
				paypeLog('debug: ' . json_encode($debug) . ' '. json_encode($create));
			}
			catch(Exception $e)
			{
				paypeLog('navision customerPull create fail: ' . $e->getMessage(), true);
			}
		}
}