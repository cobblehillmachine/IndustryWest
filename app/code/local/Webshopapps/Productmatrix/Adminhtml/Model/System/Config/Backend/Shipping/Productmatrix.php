<?php
/* ProductMatrix
 *
 * @category   Webshopapps
 * @package    Webshopapps_productmatrix
 * @copyright  Copyright (c) 2010 Zowta Ltd (http://www.webshopapps.com)
 * @license    http://www.webshopapps.com/license/license.txt - Commercial license
 */




class Webshopapps_Productmatrix_Adminhtml_Model_System_Config_Backend_Shipping_Productmatrix extends Mage_Core_Model_Config_Data
{
    public function _afterSave()
    {
		Mage::getResourceModel('productmatrix_shipping/carrier_productmatrix')->uploadAndImport($this);
    }
}
