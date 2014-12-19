<?php

class Fooman_Connect_Model_System_Abstract
{

    public function getSession()
    {
        return Mage::getSingleton('adminhtml/session');
    }

    public function isConfigured()
    {
        return Mage::helper('foomanconnect')->isConfigured();
    }
}
