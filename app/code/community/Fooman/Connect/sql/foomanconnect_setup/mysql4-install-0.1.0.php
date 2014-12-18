<?php

$installer = $this;

$installer->startSetup();
if(version_compare(Mage::getVersion(),'1.4.1.0') >= 0){
    $installer->getConnection()->addColumn($installer->getTable('sales_flat_order'), 'xero_invoice_id', "varchar(255)");
    $installer->getConnection()->addColumn($installer->getTable('sales_flat_order'), 'xero_invoice_number', "varchar(255)");
    $installer->getConnection()->addColumn($installer->getTable('sales_flat_order'), 'xero_export_status', "smallint(5) default 0");
    $installer->getConnection()->addColumn($installer->getTable('sales_flat_order'), 'xero_last_validation_errors', "text");    
} else {
    $installer->addAttribute('order', 'xero_last_validation_errors', array('type' => 'text','label' => 'Xero Last Validation Errors','is_required'=>0));
    $installer->addAttribute('order', 'xero_invoice_id', array('type' => 'varchar','label' => 'Xero Invoice Id','is_required'=>0));
    $installer->addAttribute('order', 'xero_invoice_number', array('type' => 'varchar','label' => 'Xero Invoice Number','is_required'=>0));
    $installer->addAttribute('order', 'xero_export_status', array('type' => 'int','label' => 'Xero Export Status','is_required'=>0,'default' => '0'));
}
$date = Mage::getSingleton('core/date')->gmtDate();
Mage::getModel('core/config_data')
        ->setPath(Fooman_Connect_Helper_Data::XML_PATH_CONNECTACCOUNTS_SETTINGS . 'xeroorderstartdate')
        ->setValue($date)
        ->save();
$installer->endSetup();


