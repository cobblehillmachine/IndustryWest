<?php
/* ProductMatrix
 *
 * @category   Webshopapps
 * @package    Webshopapps_productmatrix
 * @copyright  Copyright (c) 2010 Zowta Ltd (http://www.webshopapps.com)
 * @license    http://www.webshopapps.com/license/license.txt - Commercial license
 */




class Webshopapps_Productmatrix_Model_Rate_Result extends Mage_Shipping_Model_Rate_Result
{
	/**
	 *  Sort rates by price from min to max
	 *
	 *  @return	  Mage_Shipping_Model_Rate_Result
	 */
	public function sortRatesByPrice () {
		
		return;
		// dont do anything - leave to the shipping extension
	}
}
