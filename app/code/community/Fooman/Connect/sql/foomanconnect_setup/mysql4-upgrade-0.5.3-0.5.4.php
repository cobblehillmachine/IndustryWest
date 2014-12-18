<?php

$installer = $this;

$installer->startSetup();

$installer->addAttribute('catalog_product', 'xero_sales_account_code', array(
    'group' => $this->_generalGroupName,
    'input' => 'select',
    'visible_on_front' => 0,
    'label' => 'Xero Sales Account Code',
    'required' => 0,
    'source' => 'foomanconnect/system_salesProductAccountOptions')
);

$date = Mage::getSingleton('core/date')->gmtDate();
Mage::getModel('core/config_data')
        ->setPath(Fooman_Connect_Helper_Data::XML_PATH_CONNECTACCOUNTS_SETTINGS . 'xerocreditmemostartdate')
        ->setValue($date)
        ->save();
$installer->endSetup();


