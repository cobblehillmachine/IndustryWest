<?php

class Fooman_Connect_Model_System_AbstractAccounts extends  Fooman_Connect_Model_System_Abstract
{
    const XERO_ACCOUNTS_REGISTRY_KEY = 'xero-accounts';

    public function getXeroAccounts()
    {
        if($this->isConfigured() && Mage::helper('foomanconnect')->getMageStoreConfig('xeroenabled')) {
            $result = Mage::registry(self::XERO_ACCOUNTS_REGISTRY_KEY);
            if (!$result) {
                $result = Mage::getModel('foomanconnect/xeroApi')->getAccounts();
                Mage::register(self::XERO_ACCOUNTS_REGISTRY_KEY, $result);
            }
            return $result;
        } else {
            Mage::throwException('Please configure and enable the integration above and save config.');
        }
    }
}
