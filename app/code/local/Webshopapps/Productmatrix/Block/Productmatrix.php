<?php
/**
 *  Webshopapps Shipping Module
 *
 * NOTICE OF LICENSE
 *
 * This code is copyright of Zowta Ltd trading under webshopapps.com
 * As such it muse not be distributed in any form
 *
 * DISCLAIMER
 *
 * It is highly recommended to backup your server files and database before installing this module.
 * No responsibility can be taken for any adverse effects installation or advice may cause. It is also
 * recommended you install on a test server initially to carry out your own testing.
 *
 * @category   Webshopapps
 * @package    Webshopapps_Productmatrix
 * @copyright  Copyright (c) 2010 Zowta Ltd (http://www.webshopapps.com)
 * @license    www.webshopapps.com/license/license.txt
 * @author     Webshopapps <sales@webshopapps.com>
*/
class Webshopapps_Productmatrix_Block_Productmatrix  extends Mage_Core_Block_Template {

   
    public function getShippingRates($package_id=null, $weight=null) 
    {
    	$collection = Mage::getResourceModel('productmatrix_shipping/carrier_productmatrix_collection');

        $collection->setCountryFilter(Mage::getConfig()->getNode('general/country/default', 'store', Mage::app()->getStore()->getCode()));
        if (!empty($package_id)) {
       	 $collection->setPackageId($package_id);
        }
        if (!empty($weight)) {
        	$collection->setWeightRange($weight);
        }
       
    	return $collection->load();
    }
    
    public  function getRow($item) {
    	return $item->getData('dest_country').','.$item->getData('price').','.$item->getData('delivery_type');
    }
    
    public function getPrice($item) {
    	return $item->getData('price');
    }
    
    public function getDeliveryType($item) {
    	return $item->getData('delivery_type');
    }
}