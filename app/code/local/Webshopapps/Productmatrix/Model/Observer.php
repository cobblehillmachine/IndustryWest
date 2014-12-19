<?php

/**
 * Magento Webshopapps Module
 *
 * @category   Webshopapps
 * @package    Webshopapps Wsacommon
 * @copyright  Copyright (c) 2011 Zowta Ltd (http://www.webshopapps.com)
 * @license    www.webshopapps.com/license/license.txt
 * @author     Karen Baker <sales@webshopapps.com>
*/

class Webshopapps_Productmatrix_Model_Observer extends Mage_Core_Model_Abstract
{
	public function postError($observer) {
		if (!Mage::helper('wsacommon')->checkItems('Y2FycmllcnMvcHJvZHVjdG1hdHJpeC9zaGlwX29uY2U=',
        	'dXBzaWRlZG93bg==','Y2FycmllcnMvcHJvZHVjdG1hdHJpeC9zZXJpYWw=')) {
				$session = Mage::getSingleton('adminhtml/session');
				$session->addError(Mage::helper('adminhtml')->__(base64_decode('U2VyaWFsIEtleSBJcyBOT1QgVmFsaWQgZm9yIFdlYlNob3BBcHBzIFByb2R1Y3RNYXRyaXg=')));
     	}
	}	
}