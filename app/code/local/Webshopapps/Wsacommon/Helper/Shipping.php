<?php
/* WSA Common
 *
 * @category   Webshopapps
 * @package    Webshopapps_Wsacommon
 * @copyright  Copyright (c) 2011 Zowta Ltd (http://www.webshopapps.com)
 * @license    http://www.webshopapps.com/license/license.txt - Commercial license
 */
class Webshopapps_Wsacommon_Helper_Shipping extends Mage_Core_Helper_Abstract
{
	public static function getVirtualItemTotals($item, &$weight, &$qty, &$price, $useParent=true,$ignoreFreeItems=true, &$itemGroup=array(),
			$useDiscountValue=false, $cartFreeShipping=false, $useBase=false, $useTax = false) {

		 
		$addressWeight=0;
		$addressQty=0;
		$freeMethodWeight=0;
		$itemGroup[]=$item;
		$applyShipping= Mage::getModel('catalog/product')->load($item->getProduct()->getId())->getApplyShipping();
		$downloadShipping = Mage::helper('wsacommon')->isModuleEnabled('Webshopapps_Downloadshipping');
		$hasCustomOptions = 0;
		if($downloadShipping) {
			$hasCustomOptions = Mage::helper('downloadshipping')->hasCustomOptions($item);
		}


		if(!$downloadShipping && $item->getProduct()->isVirtual()){

			return false;
		}

		if ($ignoreFreeItems && $item->getFreeShipping()) {
			return false;
		}

		/*
		 * Children weight we calculate for parent
		*/
		if ($item->getParentItem() && ( ($item->getParentItem()->getProduct()->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE && $useParent)
				|| $item->getParentItem()->getProduct()->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE  )) {
			return false;
		}

		if (!$useParent && $item->getHasChildren() && $item->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE ) {
			return false;
		}

		if ($item->getHasChildren() && $item->isShipSeparately()) {


			foreach ($item->getChildren() as $child) {
				$itemGroup[]=$item;
				if($downloadable){
					if ($child->getProduct()->isVirtual() && !$applyShipping || !$hasCustomOptions) {
						continue;
					}
				}

				$addressQty += $item->getQty()*$child->getQty();

				if (!$item->getProduct()->getWeightType()) {
					$itemWeight = $child->getWeight();
					$itemQty    = $child->getTotalQty();
					$rowWeight  = $itemWeight*$itemQty;
					if ($cartFreeShipping || $child->getFreeShipping()===true) {
						$rowWeight = 0;
					} elseif (is_numeric($child->getFreeShipping())) {
						$freeQty = $child->getFreeShipping();
						if ($itemQty>$freeQty) {
							$rowWeight = $itemWeight*($itemQty-$freeQty);
						} else {
							$rowWeight = 0;
						}
					}
					$freeMethodWeight += $rowWeight;
				}
			}
			if ($item->getProduct()->getWeightType()) {
				$itemWeight = $item->getWeight();
				$rowWeight  = $itemWeight*$item->getQty();
				$addressWeight+= $rowWeight;
				if ($cartFreeShipping || $item->getFreeShipping()===true) {
					$rowWeight = 0;
				} elseif (is_numeric($item->getFreeShipping())) {
					$freeQty = $item->getFreeShipping();
					if ($item->getQty()>$freeQty) {
						$rowWeight = $itemWeight*($item->getQty()-$freeQty);
					} else {
						$rowWeight = 0;
					}
				}
				$freeMethodWeight+= $rowWeight;
			}
		} else {
			if ($downloadShipping){

				if(!$item->getProduct()->isVirtual()|| $item->getProduct()->isVirtual() && $applyShipping || $hasCustomOptions){

					$addressQty += $item->getQty();
				}
				else{return false;
				}
			}
			$itemWeight = $item->getWeight();
			$rowWeight  = $itemWeight*$item->getQty();
			$addressWeight+= $rowWeight;
			if ($cartFreeShipping || $item->getFreeShipping()===true) {
				$rowWeight = 0;
			} elseif (is_numeric($item->getFreeShipping())) {
				$freeQty = $item->getFreeShipping();
				if ($item->getQty()>$freeQty) {
					$rowWeight = $itemWeight*($item->getQty()-$freeQty);
				} else {
					$rowWeight = 0;
				}
			}
			$freeMethodWeight+= $rowWeight;
		}

		if (!$useParent && $item->getParentItem() && $item->getParentItem()->getProductType() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE ) {
			$weight=$addressWeight*$item->getParentItem()->getQty();
			$qty=$addressQty*$item->getParentItem()->getQty();
			$parentProduct = $item->getParentItem()->getProduct();
			!$useBase ? $finalPrice = $item->getRowTotal() : $finalPrice = $item->getBaseRowTotal();
			$useTax && $useBase ? $finalPrice += $item->getBaseTaxAmount() : false;
			$useTax && !$useBase ? $finalPrice += $item->getTaxAmount() : false;
				
			if ($parentProduct->getPriceType() == Mage_Bundle_Model_Product_Price::PRICE_TYPE_FIXED) {
				if ($parentProduct->hasCustomOptions()) {
					$customOption = $parentProduct->getCustomOption('bundle_option_ids');
					$customOption = $parentProduct->getCustomOption('bundle_selection_ids');
					$selectionIds = unserialize($customOption->getValue());
					$selections = $parentProduct->getTypeInstance(true)->getSelectionsByIds($selectionIds, $parentProduct);
					$selections->addTierPriceData();
					foreach ($selections->getItems() as $selection) {
						if ($selection->getProductId()== $item->getProductId()) {
							$finalPrice = $item->getParentItem()->getProduct()->getPriceModel()->getChildFinalPrice(
									$parentProduct, $item->getParentItem()->getQty(),
									$selection, $qty, $item->getQty());
							//Price from here is always base. Convert to store to stay consistent unless flag $useBase is set.
							!$useBase ? $finalPrice = Mage::helper('directory')->currencyConvert($finalPrice,
									Mage::app()->getStore()->getBaseCurrencyCode(), Mage::app()->getStore()->getCurrentCurrencyCode()) : '';
						}
					}
				}
			}
			$price=$finalPrice;
		} else {
			//Using the quote item means we can still get the tax amount in multi shipping checkout					
			$quoteItem = $item->getQuoteItem();
			is_object($quoteItem) ? $item = $quoteItem : 0;
			$weight=$addressWeight;
			$qty=$addressQty;
			!$useBase ? $price = $item->getRowTotal() : $price = $item->getBaseRowTotal();
			$useTax && !$useBase ? $price += $item->getTaxAmount() : false;
			$useTax && $useBase ? $price += $item->getBaseTaxAmount() : false;
		}

		if ($useDiscountValue){
			!$useBase ? $price-=$item->getDiscountAmount() : $price-=$item->getBaseDiscountAmount();;
		}

		return true;
   	}
   	
   	/** 
   	 * PHP - Doesnt support function overloading. Bring on a real language!
   	 * Enter description here ...
   	 * @param unknown_type $item
   	 * @param unknown_type $weight
   	 * @param unknown_type $qty
   	 * @param unknown_type $price
   	 * @param unknown_type $useParent
   	 * @param unknown_type $ignoreFreeItems
   	 * @param unknown_type $itemGroup
   	 * @param unknown_type $useDiscountValue
   	 */
   	public static function getItemTotals($item, &$weight, &$qty, &$price, $useParent=true,$ignoreFreeItems=true, 
 			&$itemGroup=array(),$useDiscountValue=false, $cartFreeShipping = false,$useBase = false, $useTax = false) {

 		$freeMethodWeight =0;
 		return self::getItemInclFreeTotals($item, $weight, $qty, $price, $freeMethodWeight, $useParent,$ignoreFreeItems, 
 			$itemGroup,$useDiscountValue,$cartFreeShipping, $useBase, $useTax);
 	}

 	/**
 	 * Freemethod weight now returned
 	 * Enter description here ...
 	 * @param unknown_type $item
 	 * @param unknown_type $weight
 	 * @param unknown_type $qty
 	 * @param unknown_type $price
 	 * @param unknown_type $freeMethodWeight
 	 * @param unknown_type $useParent
 	 * @param unknown_type $ignoreFreeItems
 	 * @param unknown_type $itemGroup
 	 * @param unknown_type $useDiscountValue
 	 */
 	public static function getItemInclFreeTotals($item, &$weight, &$qty, &$price, &$freeMethodWeight, $useParent=true,$ignoreFreeItems=true,
 			&$itemGroup=array(),$useDiscountValue=false, $cartFreeShipping = false, $useBase = false, $useTax = false) {

 		$addressWeight=0;
 		$addressQty=0;
 		$freeMethodWeight=0;
 		$itemGroup[]=$item;

 		if (!is_object($item))
 		{
 			Mage::helper('wsacommon/log')->postCritical('wsacommon','Fatal Error','Item/Product is Malformed');
 			return false;
 		}
 		 
 		/**
 		 * Skip if this item is virtual
 		 **/

 		if ($item->getProduct()->isVirtual()) {
 			return false;
 		}

 		if ($ignoreFreeItems && ($item->getFreeShipping() || $cartFreeShipping)) {
 			return false;
 		}

 		/**
 		 * Children weight we calculate for parent
 		 */
 		 
 		if ($item->getParentItem() && ( ($item->getParentItem()->getProduct()->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE && $useParent)
 				|| $item->getParentItem()->getProduct()->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE  )) {
 			return false;
 		}

 		if (!$useParent && $item->getHasChildren() && $item->getProduct()->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE ) {
 			return false;
 		}

 		if ($item->getHasChildren() && $item->isShipSeparately()) {
 			foreach ($item->getChildren() as $child) {
 				$itemGroup[]=$item;
 				if ($child->getProduct()->isVirtual()) {
 					continue;
 				}
 				$addressQty += $item->getQty()*$child->getQty();

 				if (!$item->getProduct()->getWeightType()) {
 					$itemWeight = $child->getWeight();
 					$itemQty    = $child->getTotalQty();
 					$rowWeight  = $itemWeight*$itemQty;
 					if ($cartFreeShipping || $child->getFreeShipping()===true) {
 						$rowWeight = 0;
 					} elseif (is_numeric($child->getFreeShipping())) {
 						$freeQty = $child->getFreeShipping();
 						if ($itemQty>$freeQty) {
 							$rowWeight = $itemWeight*($itemQty-$freeQty);
 						} else {
 							$rowWeight = 0;
 						}
 					}
 					$freeMethodWeight += $rowWeight;
 				}
 			}
 			if ($item->getProduct()->getWeightType()) {
 				$itemWeight = $item->getWeight();
 				$rowWeight  = $itemWeight*$item->getQty();
 				$addressWeight+= $rowWeight;
 				if ($cartFreeShipping || $item->getFreeShipping()===true) {
 					$rowWeight = 0;
 				} elseif (is_numeric($item->getFreeShipping())) {
 					$freeQty = $item->getFreeShipping();
 					if ($item->getQty()>$freeQty) {
 						$rowWeight = $itemWeight*($item->getQty()-$freeQty);
 					} else {
 						$rowWeight = 0;
 					}
 				}
 				$freeMethodWeight+= $rowWeight;
 			}
 		} else {
 			if (!$item->getProduct()->isVirtual()) {
 				$addressQty += $item->getQty();
 			}
 			$itemWeight = $item->getWeight();
 			$rowWeight  = $itemWeight*$item->getQty();
 			$addressWeight+= $rowWeight;
 			if ($cartFreeShipping || $item->getFreeShipping()===true) {
 				$rowWeight = 0;
 			} elseif (is_numeric($item->getFreeShipping())) {
 				$freeQty = $item->getFreeShipping();
 				if ($item->getQty()>$freeQty) {
 					$rowWeight = $itemWeight*($item->getQty()-$freeQty);
 				} else {
 					$rowWeight = 0;
 				}
 			}
 			$freeMethodWeight+= $rowWeight;
 		}

 		if (!$useParent && $item->getParentItem() && $item->getParentItem()->getProductType() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE ) {
 			$weight=$addressWeight*$item->getParentItem()->getQty();
 			$qty=$addressQty*$item->getParentItem()->getQty();
 			$parentProduct = $item->getParentItem()->getProduct();
 			!$useBase ? $finalPrice = $item->getRowTotal() : $finalPrice = $item->getBaseRowTotal();
 			$useTax && $useBase ? $finalPrice += $item->getBaseTaxAmount() : false;
 			$useTax && !$useBase ? $finalPrice += $item->getTaxAmount() : false;
 			if ($parentProduct->getPriceType() == Mage_Bundle_Model_Product_Price::PRICE_TYPE_FIXED) {
 				if ($parentProduct->hasCustomOptions()) {
 					$customOption = $parentProduct->getCustomOption('bundle_option_ids');
 					$customOption = $parentProduct->getCustomOption('bundle_selection_ids');
 					$selectionIds = unserialize($customOption->getValue());
 					$selections = $parentProduct->getTypeInstance(true)->getSelectionsByIds($selectionIds, $parentProduct);
 					$selections->addTierPriceData();
 					foreach ($selections->getItems() as $selection) {
 						if ($selection->getProductId()== $item->getProductId()) {
 							$finalPrice = $item->getParentItem()->getProduct()->getPriceModel()->getChildFinalPrice(
 									$parentProduct, $item->getParentItem()->getQty(),
 									$selection, $qty, $item->getQty());
 							//Price from here is always base. Convert to store to stay consistent unless flag $useBase is set.
 							!$useBase ? $finalPrice = Mage::helper('directory')->currencyConvert($finalPrice,
 									Mage::app()->getStore()->getBaseCurrencyCode(), Mage::app()->getStore()->getCurrentCurrencyCode()) : '';
 						}
 					}
 				}
 			}
 			$price=$finalPrice;
 		}   else {
 			//Using the quote item means we can still get the tax amount in multi shipping checkout
 			$quoteItem = $item->getQuoteItem();
 			is_object($quoteItem) ? $item = $quoteItem : 0;
 			$weight=$addressWeight;
 			$qty=$addressQty;
 			!$useBase ? $price = $item->getRowTotal() : $price = $item->getBaseRowTotal();
 			$useTax && !$useBase ? $price += $item->getTaxAmount() : false;
 			$useTax && $useBase ? $price += $item->getBaseTaxAmount() : false;
 		}
 			
 		if ($useDiscountValue){
 			!$useBase ? $price-=$item->getDiscountAmount() : $price-=$item->getBaseDiscountAmount();;
 		}

 		return true;
 	}

  

   	public static function updateStatus($session,$numRows) {
   		if ($numRows<1) {
			$session->addError(Mage::helper('adminhtml')->__($numRows.' rows have been imported. See <a href="http://wiki.webshopapps.com/the-faq#TOC-This-Shipping-Method-Is-Unavailable">wiki article for help</a>'))  ;
        } else {
        	$session->addSuccess(Mage::helper('adminhtml')->__($numRows.' rows have been imported.'));
        }
   	}
   	
   	public static function hasFreightCarrierEnabled() {
   		if(Mage::helper('wsacommon')->isModuleEnabled('Webshopapps_Yrcfreight','carriers/yrcfreight/active') || 
   			Mage::helper('wsacommon')->isModuleEnabled('Webshopapps_Wsaupsfreight','carriers/wsaupsfreight/active') ||
   			Mage::helper('wsacommon')->isModuleEnabled('Webshopapps_Rlfreight','carriers/rlfreight/active')  ) {
   			return true;
   		}
   		return false;
   	}
   	
   	/**
   	 * 
   	 * Retrieves enabled freight carriers. Currently only returns one
   	 */
  	public static function getFreightCarriers() {
   		if(Mage::helper('wsacommon')->isModuleEnabled('Webshopapps_Yrcfreight','carriers/yrcfreight/active')) {
   			return 'yrcfreight';
   		}
   		
   		if (Mage::helper('wsacommon')->isModuleEnabled('Webshopapps_Wsaupsfreight','carriers/wsaupsfreight/active') ) {
   			return 'wsaupsfreight';
   		}
   		
  	   	if (Mage::helper('wsacommon')->isModuleEnabled('Webshopapps_Rlfreight','carriers/rlfreight/active') ) {
   			return 'rlfreight';
   		}
   		return '';
   	}
   	
   	/**
   	 * 
   	 * Method to save a backup copy of the CSV file to the file system.
   	 * @param String $file - CSV file to be saved.
   	 * @param String $fileName - What to call the file including extension.
   	 */
   	public function saveCSV($file,$fileName) {
   		
   		$dir = Mage::getBaseDir('var').'/export/';
   		
   		try {
   			if(!is_dir($dir)) {
   				if(!mkdir($dir)){
   					Mage::helper('wsacommon/log')->postMajor("WSA Helper","IO Error","Error Creating Backup CSV File Directory");			
   				}
   			}
   			if (!ctype_digit(file_put_contents($dir.$fileName, $file))) {
   				Mage::helper('wsacommon/log')->postMajor("WSA Helper","IO Error","Error Creating Backup CSV File");   				
   			}
   		} catch (Exception $e) {
   			Mage::helper('wsacommon/log')->postMajor("Helper","Error Saving CSV File Backup",$e->getMessage());
   		}
   		
   	}
}