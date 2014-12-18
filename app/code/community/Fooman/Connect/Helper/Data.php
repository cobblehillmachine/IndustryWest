<?php

/*
 * @author     Kristof Ringleff
 * @package    Fooman_Connect
 * @copyright  Copyright (c) 2010 Fooman Limited (http://www.fooman.co.nz)
 */

class Fooman_Connect_Helper_Data extends Mage_Core_Helper_Abstract {
    const XML_PATH_CONNECTACCOUNTS_SETTINGS = 'foomanconnect/settings/';

    /**
     * Return store config value for key
     *
     * @param   string $key
     * @return  string
     */
    public function getMageStoreConfig($key, $storeId = Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID) {

        $path = self::XML_PATH_CONNECTACCOUNTS_SETTINGS . $key;
        return Mage::getStoreConfig($path, $storeId);
    }

    /**
     * Save store config value for key
     *
     * @param string $key
     * @param string $value
     * @return <type>
     */
    public function setMageStoreConfig ($key, $value, $storeId = Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID)
    {
        $path = self::XML_PATH_CONNECTACCOUNTS_SETTINGS . $key;

        //save to db
        try {
            $configModel = Mage::getModel('core/config_data');
            $collection = $configModel->getCollection()
                        ->addFieldToFilter('path', $path)
                        ->addFieldToFilter('scope_id', $storeId);
            if ($storeId != Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID) {
                $collection->addFieldToFilter('scope', Mage_Adminhtml_Block_System_Config_Form::SCOPE_STORES);
            }

            if ($collection->load()->getSize() > 0) {
                //value already exists -> update
                foreach ($collection as $existingConfigData) {
                    $existingConfigData->setValue($value)->save();
                }
            } else {
                //new value
                $configModel
                        ->setPath($path)
                        ->setValue($value);
                if ($storeId != Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID ) {
                    $configModel->setScopeId($storeId);
                    $configModel->setScope(Mage_Adminhtml_Block_System_Config_Form::SCOPE_STORES);
                }
                $configModel->save();
            }
        } catch (Exception $e) {
            Mage::logException($e);
        }
        Mage::app()->getConfig()->removeCache();
        //we also set it as a temporary item so we don't need to reload the config
        return Mage::app()->getStore($storeId)->load($storeId)->setConfig($path, $value);
    }

    public function getTaxOptions() {
        return Mage::getModel('foomanconnect/system_taxOptions')->toOptionArray();
    }

    /**
     * check if configuration parameters have been entered
     * minimum required Consumer Key, Consumer Secret and Private Key
     *
     * @return bool
     */
    public function isConfigured() {
        $consumerkey = Mage::helper('core')->decrypt(Mage::helper('foomanconnect')->getMageStoreConfig('consumerkey'));
        $consumersecret = Mage::helper('core')->decrypt(Mage::helper('foomanconnect')->getMageStoreConfig('consumersecret'));
        $privatekey = Mage::helper('core')->decrypt(Mage::helper('foomanconnect')->getMageStoreConfig('privatekey'));
        return (!empty($consumerkey) && !empty($consumersecret)&& !empty($privatekey));
    }

}
