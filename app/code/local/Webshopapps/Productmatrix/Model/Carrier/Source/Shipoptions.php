<?php
/* ProductMatrix
 *
 * @category   Webshopapps
 * @package    Webshopapps_productmatrix
 * @copyright  Copyright (c) 2010 Zowta Ltd (http://www.webshopapps.com)
 * @license    http://www.webshopapps.com/license/license.txt - Commercial license
 */


class Webshopapps_Productmatrix_Model_Carrier_Source_Shipoptions {

public function toOptionArray()
    {
        $productmatrix = Mage::getSingleton('productmatrix/carrier_productmatrix');
        $arr = array();
        foreach ($productmatrix->getCode('shipoptions') as $k=>$v) {
            $arr[] = array('value'=>$k, 'label'=>Mage::helper('shipping')->__($v));
        }
        return $arr;
    }
}
