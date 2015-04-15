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
			$create['_UpdateDiscount'] = false;

			$create['_CustomerNo'] = '';
			$create['customerID'] = '';

			if(Library::validateEstonianPersonalCode($c->customer_id))
			{
				$create['customerID'] = $c->customer_id;
			}
			elseif(!empty($c->personal_code))
			{
				$create['customerID'] = $c->personal_code;
			}
			else
			{
				$create['_CustomerNo'] = $c->customer_id;
			}

			//paypeLog('debug create > '. json_encode($create) . ' ' . json_encode($c));

			try
			{
				$createReturn = $this->client->CreateOrUpdateCustomer($create);
				paypeLog('CreateOrUpdateCustomer: ' . json_encode($createReturn) . ' ' . json_encode($create));

				if(!$createReturn->return_value)
				{
					// new customer
					$this->sendMail($c);
				}
				elseif($c->identification_count > 0)
				{
					// old customer, and has identified
					$create['_UpdateDiscount'] = true;
					$updateDiscount = $this->client->CreateOrUpdateCustomer($create);
					paypeLog('updateDiscount: ' . json_encode($updateDiscount));
				}
			} catch (Exception $e) {
				paypeLog('navision customerPull create fail: ' . $e->getMessage(), true);
			}
		}
	}

	private function sendMail($customer)
	{
		// TODO
	}
}