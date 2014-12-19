<?php
/* ProductMatrix
 *
 * @category   Webshopapps
 * @package    Webshopapps_productmatrix
 * @copyright  Copyright (c) 2010 Zowta Ltd (http://www.webshopapps.com)
 * @license    http://www.webshopapps.com/license/license.txt - Commercial license
 */




class Webshopapps_Productmatrix_Adminhtml_Model_System_Config_Source_Shipping_Productmatrix
{
    public function toOptionArray()
    {

        $tableRate = Mage::getSingleton('productmatrix_shipping/carrier_productmatrix');
        $arr = array();
        foreach ($tableRate->getCode('condition_name') as $k=>$v) {
            $arr[] = array('value'=>$k, 'label'=>$v);
        }
        return $arr;
    }
}
