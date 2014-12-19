<?php

/* ProductMatrix
 *
 * @category   Webshopapps
 * @package    Webshopapps_productmatrix
 * @copyright  Copyright (c) 2010 Zowta Ltd (http://www.webshopapps.com)
 * @license    http://www.webshopapps.com/license/license.txt - Commercial license
 */


class Webshopapps_Productmatrix_Block_Shippingoptions extends Mage_Catalog_Block_Product_Abstract
{

	public function getShippingRates()
	{
		$_product = $this->getProduct();
		return $this->_getShippingRates($_product->getAttributeText('package_id'), floatval($_product->getWeight()),0);
	}

	public function _getShippingRates($package_id = null, $weight = null, $regionFilter=0)
	{
		$collection = Mage::getResourceModel('productmatrix_shipping/carrier_productmatrix_collection');
		$collection->setCountryFilter(Mage::getConfig()->getNode('general/country/default', 'store', Mage::app()->getStore()->getCode()));
		$collection->setRegionFilter($regionFilter);
		$collection->setPackageId($package_id);
		$collection->setWeightRange($weight);
		return $collection->load();
	}

	public function getRow($item)
	{
		return $item->getData('dest_country') . ',' . $item->getData('dest_region_id') . ','. $item->getData('price') . ',' . $item->getData('delivery_type');
	}

	public function getPrice($item)
	{
		return $item->getData('price');
	}

	public function getDeliveryType($item)
	{
		return $item->getData('delivery_type');
	}
}