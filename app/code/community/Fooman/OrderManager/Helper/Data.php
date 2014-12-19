<?php

/*
 * @author     Kristof Ringleff
 * @package    Fooman_OrderManager
 * @copyright  Copyright (c) 2009 Fooman Limited (http://www.fooman.co.nz)
 * @copyright  Copyright (c) 2009 smARTstudiosUK Limited (http://smartebusiness.co.uk)
 */

class Fooman_OrderManager_Helper_Data extends Mage_Core_Helper_Abstract
{
    const XML_PATH_ORDERMANAGER_SETTINGS = 'ordermanager/';

    /**
     * Return store config value for key
     *
     * @param   string $key
     * @return  string
     */
    public function getStoreConfig ($key, $storeId = Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID)
    {
        $path = self::XML_PATH_ORDERMANAGER_SETTINGS . $key;
        return Mage::getStoreConfig($path, $storeId);
    }

    /**
     * retrieve carrier title
     */
    public function getCarrierTitle ($carrierCode)
    {
        $_carrierTitles = Mage::registry('carrier_titles');

        if(!isset($_carrierTitles[$carrierCode])) {
            if($carrierCode == 'custom') {
                $_carrierTitles[$carrierCode] = Mage::helper('ordermanager')->getStoreConfig('settings/customtitle');
            } else {
                $_carrierTitles[$carrierCode] = Mage::getStoreConfig('carriers/'.$carrierCode.'/title');
            }
            Mage::unregister('carrier_titles');
            Mage::register('carrier_titles', $_carrierTitles);
        }
        return $_carrierTitles[$carrierCode];
    }
}