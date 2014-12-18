<?php
/**
 * @category   ASPerience
 * @package    Asperience_DeleteAllOrders
 * @author     ASPerience - www.asperience.fr
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
//Module page: http://www.magentocommerce.com/magento-connect/asperience-deleteorders.html\n

Mage::helper('asperience_notificationmanager/Data')->createAdminNotification
(
"To finish the configuration please go in Magento Admin Panel - System - Configuration - Sales - Deletion of Orders",
"Do not forget to configure the status you want to be able to delete. ".
"This module is provided by ASPerience (http://www.asperience.fr/)",
"http://www.magentocommerce.com/magento-connect/asperience-deleteorders.html"
);