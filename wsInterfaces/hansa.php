<?php
class Hansa implements WsInterface
{
	private $csvUrl;
	private $filePath;
	private $fileName;

	public function __construct($wsConfig, $paypeApi)
	{
		$this->csvUrl = $wsConfig['location'];
        $this->filePath = $wsConfig['file']['path'];
        $this->fileName = $wsConfig['file']['name'];
	}

    // Sample implementation
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

    // Post customers to your system
	public function postCustomers($customers)
	{
        $lineBreak = "\n";
        $tab = "\t";

		$csv = '';

		foreach($customers as $c)
		{

            for($i=0; $i<215;$i++){ // there are 215 fields in hansa's cuvc export/import documentation

                switch($i){

                    // 0 - UUID
                    case 0:
                        $csv .= $c->token;
                        break;

                    // 3 - code
                    case 3:
                        $csv .= 'P'.sprintf('%09d', intval($c->customer_id));
                        break;

                    // 5 - person
                    case 5:
                        $csv .= $c->first_name . ' ' . $c->last_name;
                        break;

                    // 84 - mobile
                    case 84:
                        $csv .= $c->phone_international;
                        break;

                    // 137 - gender
                    case 137:
                        $csv .= $c->gender == 'male'?1:0;
                        break;

                    // 163 - age
                    case 163:
                        $csv .= $c->age;
                        break;

                    // 188 - Birthday
                    case 188:
                        $csv .= $c->birthday;
                        break;

                }
                $csv .= $tab;
            }

            $csv .= $lineBreak;

		}

        $file = fopen($this->filePath.$this->fileName, "w") or die("Unable to open file: ".$this->filePath.$this->fileName." !");
        fwrite($file, $csv);
        fclose($file);

	}

}