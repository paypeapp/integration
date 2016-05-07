<?php

class Cli implements WsInterface
{
	public function postCustomers($customers)
	{
		foreach($customers as $c)
		{
			// dont do anything
		}
	}
	public function getCustomers($lastSyncCustomerId)
	{
		$returnCustomers = array();
		if(empty($_GET['customer_id']))
		{
			paypeLog('CLI customer read failed, 13 digit personal code as customer_id is mandatory', true);
			return $returnCustomers;
		}

		$returnCustomers[] = $_GET;

		return $returnCustomers;
	}
}