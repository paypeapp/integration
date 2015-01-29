<?php
class Csv implements WsInterface
{
	private $csvUrl;

	public function __construct($wsConfig, $paypeApi)
	{
		$this->csvUrl = $wsConfig['location'];
	}

	// expecting customer_id,first_name,last_name,email
	public function getCustomers($lastSyncCustomerId)
	{
		$customers = array();

		$data = file_get_contents($this->csvUrl);
		$data = explode("\n", $data);

		foreach($data as $c)
		{
			$c = str_replace('"', '', $c);
			$c = str_replace(';', ',', $c);

			$fields = explode(",", $c);

			if(count($fields)==4)
			{
				$customers[] = array(
					'customer_id' => $fields[0],
					'first_name' => $fields[1],
					'last_name' => $fields[2],
					'email' => $fields[3]
				);
			}
		}

		return $customers;
	}

	public function postCustomers($customers)
	{
		$csv = 'First name,Last name,Email,Gender,Birthday,Customer ID,Customer since\n';

		foreach($customers as $c)
		{
			$csv .= $c->first_name.','.$c->last_name.','.$c->email.','.$c->gender.',';
			$csv .= $c->birthday.','.$c->customer_id.','.$c->customer_since.'\n';
		}

		echo $csv;
	}
}