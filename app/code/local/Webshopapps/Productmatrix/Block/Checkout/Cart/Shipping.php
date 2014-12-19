<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category   Mage
 * @package    Mage_Checkout
 * @copyright  Copyright (c) 2008 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/*
 * Webshopapps Residential Delivery Extension
 * 
 * @author 	Webshopapps
 * @license www.webshopapps.com/license/license.txt
 * 
 * (c) Webshopapps.com Zowta Ltd 2011 - All rights reserved.
 */
class Webshopapps_Productmatrix_Block_Checkout_Cart_Shipping extends Mage_Checkout_Block_Cart_Shipping
{
	
	public function showToolTips($desc)
    { 
    	if($desc=='' or $desc=='*') {
    		return false;
    	}  	
    	// is tooltip present and not *
    	$options = explode(',',Mage::getStoreConfig("carriers/productmatrix/ship_options"));
    	if (in_array('show_tooltips',$options)) {
    		return true;
    	}
    	
        return false;
    }
    
	
	public function toolTipHtmlStart() {
		
		$html = $this->getLayout()
		->createBlock('core/text', 'example-block')
		->setText('<b class="helpcursor" title="')
		->_toHtml();
		return $html;
		$html;
	
	}
	
	public function toolTipHtmlImg() {
	
		$html = $this->getLayout()
		->createBlock('core/text', 'example-block')
		->setText('"><img src="')
		->_toHtml();
		return $html;
		$html;
	
	}
	
	public function toolTipHtmlEnd() {
	
		$html = $this->getLayout()
		->createBlock('core/text', 'example-block')
		->setText('"></b>')					
		->_toHtml();
		return $html;
	
	}
	
}