<?php
interface WsInterface
{
    // return customers saved in your systems as array with elements mapped to Paype expected customer input params
    // input param will say the id of the customer paype has received last while syncing
    public function getCustomers($lastSyncCustomerId);

    // saving customers in your system, customers sent in as array of customers with Paype public-api return params
    // that you map within the functions
    public function postCustomers($customers);
}