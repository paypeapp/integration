<?php
require_once('navision.php');
class Navision2 extends Navision
{
    public function getCustomers($lastSyncCustomerId)
    {
        $returnCustomers = array();

        // TODO

        return $returnCustomers;
    }

    public function postCustomers($customers)
    {
        foreach($customers as $c)
        {
            $create = array();
            $create['Customers']['E_Mail'] = $c->email;
            $create['Customers']['Name'] = $c->first_name . ' ' . $c->last_name;
            $create['Customers']['IC_Partner_Code'] = $c->customer_id;

            try
            {
                $this->client->Create($create);
            }
            catch(Exception $e)
            {
                preg_match('/Same e-mail has already used in Regular Customer (.*)/', $e->getMessage(), $navId);

                if(count($navId) == 2)
                {
                    // The error message contains RegularCustomer->No, call update on it
                    $navNo = $navId[1];
                    $this->updateCustomer($create, $navNo);
                }
                else
                {
                    paypeLog('navision customerPull create fail: ' . $e->getMessage(), true);
                }
            }
        }
    }

    private function updateCustomer($customer, $navNo)
    {
        try
        {
            $read = array();
            $read['No'] = $navNo;

            // get customer key by their NAV No from create error message
            $navClient = $this->client->Read($read);

            // update customer sending in create object with key received from read call
            $customer['Customers']['Key'] = $navClient->Customers->Key;
            $update = $this->client->Update($customer);
            paypeLog('navision customerPull customer update: ' . json_encode($update));
        }
        catch(Exception $e)
        {
            paypeLog('navision customerPull update fail: ' . $e->getMessage(), true);
        }
    }
}