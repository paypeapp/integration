<?php
class Email implements WsInterface
{
	private $emails;

	public function __construct($wsConfig, $paypeApi)
	{
		$this->emails = $wsConfig['emails'];
	}

	public function getCustomers($lastSyncCustomerId)
	{
		// No GET from email
		return;
	}

	// format customers and send to emails
	public function postCustomers($customers)
	{
		$countCustomers = count($customers);
		if($countCustomers == 0)
		{
			// no customers = no email
			return;
		}

		$email = $countCustomers . ' new customers registered in Paype, you can manage them at <a href="https://business.paype.com">business management website</a><br><br>';

		$email .= '<table><tr>
					<th>First name</th>
					<th>Last name</th>
					<th>Email</th>
					<th>Gender</th>
					<th>Birthday</th>
					<th>Customer ID</th>
				</tr>';

		foreach($customers as $c)
		{
			$email .= '<tr>
					<td>'.$c->first_name.'</td>
					<td>'.$c->last_name.'</td>
					<td>'.$c->email.'</td>
					<td>'.$c->gender.'</td>
					<td>'.$c->birthday.'</td>
					<td>'.$c->customer_id.'</td>
				</tr>';
		}

		$email .= '</table>';

		$headers  = 'MIME-Version: 1.0' . "\r\n";
		$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
		$headers .= 'From: Paype automailer <paype@paype.com>' . "\r\n";

		foreach($this->emails as $mail)
		{
			mail($mail, 'New Paype users', $email, $headers);
		}
	}
}