<?php
/* ProductMatrix
 *
 * @category   Webshopapps
 * @package    Webshopapps_productmatrix
 * @copyright  Copyright (c) 2010 Zowta Ltd (http://www.webshopapps.com)
 * @license    http://www.webshopapps.com/license/license.txt - Commercial license
 */




class Webshopapps_Productmatrix_Model_Carrier_Productmatrix
    extends Webshopapps_Wsacommon_Model_Shipping_Carrier_Baseabstract
    implements Mage_Shipping_Model_Carrier_Interface
{

    protected $_code = 'productmatrix';
    protected $_default_condition_name = 'per_package';

    protected $_conditionNames = array();
	private $hasCustomopts = false;
	


    public function __construct()
    {
        parent::__construct();
        foreach ($this->getCode('condition_name') as $k=>$v) {
            $this->_conditionNames[] = $k;
        }
    }

    /**
     * Previous entry point, now cut down to look like UPS
     *
     * @param Mage_Shipping_Model_Rate_Request $data
     * @return Mage_Shipping_Model_Rate_Result
     */
    public function setRequest(Mage_Shipping_Model_Rate_Request $request)
    {
        
        $this->_options = explode(',',Mage::getStoreConfig("carriers/productmatrix/ship_options"));
		$skus = explode(',',Mage::getStoreConfig('shipping/downloadshipping/sku_match'),-1);
        
        $request->setPMConditionName($this->getConfigData('condition_name') ? $this->getConfigData('condition_name') : $this->_default_condition_name);
       
        
        $this->_rawRequest = $request;
               
        $freeBoxes = 0;
        $found=false;
        $total=0;
        
        try {
        	foreach ($request->getAllItems() as $item) {
        			
        		$applyShipping = Mage::getModel('catalog/product')->load($item->getProduct()->getId())->getApplyShipping();
        			
        		if(in_array($item->getSku(),$skus )){
        			$this->hasCustomopts = true;
        		}
        			
        		if (($item->getFreeShipping() && $item->getProductType()!= Mage_Catalog_Model_Product_Type::TYPE_VIRTUAL )||
        				($item->getFreeShipping() && $item->getProductType()== Mage_Catalog_Model_Product_Type::TYPE_VIRTUAL && $applyShipping) || 
        					($item->getFreeShipping() && $hasCustomopts)) {
        			$freeBoxes+=$item->getQty();
        		}

        		if ($item->getParentItem())
        		{
        			continue;
        		}
        		if ($item->getHasChildren() && $item->isShipSeparately())
        		{
        			foreach ($item->getChildren() as $child)
        			{
        				if ($child->getIsVirtual() && !$applyShipping || $child->getIsVirtual() && !$this->hasCustomopts)
        				{
        					$total += $child->getBaseRowTotal();
        					$found=true;
        				}
        			}
        		} elseif ($item->getProduct()->isVirtual() && !$applyShipping || $item->getProduct()->isVirtual() && !$this->hasCustomopts)
        		{
        			$total += $item->getBaseRowTotal();
        			$found=true;
        		}
        	}
        } catch (Exception $e) {
        	// this is really bad programmtically but we are going to ignore this, as in some cases there wont be
        	// anything in getAllItems.
        }
        
        if ($found && in_array('remove_virtual',$this->_options)) {
        	// this fixes bug in Magento where package value is not set correctly, but at expense of sacrificing discounts
        	$this->_rawRequest->setPackageValue($this->_rawRequest->getPackageValue() - $total);
        }
        $this->setFreeBoxes($freeBoxes);
        
        $this->_rawRequest->setIgnoreFreeItems(false);

    }
    
	protected function _getQuotes()
    { 
    	if (in_array('custom_sorting',$this->_options)) {
        	$result = Mage::getModel('productmatrix_shipping/rate_result');
        } else {
			$result = Mage::getModel('shipping/rate_result');
        }
        $request = $this->_rawRequest;
        $version = Mage::helper('wsacommon')->getVersion();
        $freeTextSet = false;
    	
     	$ratearray = $this->getRate($request);
     	
     	//This is fixing M1.4.0-1.4.1 when a cart is purely free shipping.
     	$brokenFree = false;
     	
     	if ($version == 1.6 || $version == 1.7 || $version == 1.8){
     		if ($request->getFreeShipping() === true || $request->getPackageQty() == $this->getFreeBoxes()){
     			$brokenFree = true;
     		}
     	}

     	if (empty($ratearray)) {
     		if(!$brokenFree){
	     		if ($this->getConfigData('specificerrmsg')!='') {
		            $error = Mage::getModel('shipping/rate_result_error');
		            $error->setCarrier('productmatrix');
		            $error->setCarrierTitle($this->getConfigData('title'));
		            $error->setErrorMessage($this->getConfigData('specificerrmsg'));
		            $result->append($error);
	     		}
				return $result;
     		} else {
     			$method = Mage::getModel('shipping/rate_result_method');
	     		$method->setCarrier('productmatrix');
				$method->setCarrierTitle($this->getConfigData('title'));
				
				$shippingPrice = 0.00;
				
				if ($this->getConfigData('free_shipping_text') != "") {
					$modifiedName=preg_replace('/&|;| /',"_",$this->getConfigData('free_shipping_text'));
					$method->setMethodTitle($this->getConfigData('free_shipping_text'));
					$freeTextSet = true;
				} else {
					$modifiedName="productmatrix_free_promotion";
					$method->setMethodTitle(Mage::helper('shipping')->__('Free Shipping'));
				}
				$method->setMethod($modifiedName);
				$method->setMethodDescription('Free Shipping');
				$method->setPrice($shippingPrice);
				$method->setCost($shippingPrice);
				$method->setDeliveryType('productmatrix_free_promotion');
				$result->append($method);
				return $result;
     		}
     	}
     		     	
     	$max_shipping_cost=$this->getConfigData('max_shipping_cost');
     	$min_shipping_cost=$this->getConfigData('min_shipping_cost');

	    foreach ($ratearray as $rate)
		{
		   if (!empty($rate) && $rate['price'] >= 0) {
			  $method = Mage::getModel('shipping/rate_result_method');

				$method->setCarrier('productmatrix');
				$method->setCarrierTitle($this->getConfigData('title'));

				$price=$rate['price'];
				if (!empty($max_shipping_cost) && $max_shipping_cost>0 && $price>$max_shipping_cost ) {
					$price=$max_shipping_cost;
				}
		   		if (!empty($min_shipping_cost) && $min_shipping_cost>0 && $price<$min_shipping_cost) {
					$price=$min_shipping_cost;
				}
				if (!in_array('apply_handling',$this->_options) && $price==0) {
					$shippingPrice = $price;
				} else {
					$shippingPrice = $this->getFinalPriceWithHandlingFee($price);
				}
				//this is a fix for 1.4.1.1 and earlier versions where the free ship logic used for UPS doesnt work				
				if (($version == 1.6 || $version == 1.7 || $version == 1.8) &&
					 ($request->getFreeShipping() === true || $request->getPackageQty() == $this->getFreeBoxes() )) {
						$shippingPrice = 0.00;
						if ($this->getConfigData('free_shipping_text') != "") {
							$modifiedName=preg_replace('/&|;| /',"_",$this->getConfigData('free_shipping_text'));
							$method->setMethodTitle($this->getConfigData('free_shipping_text'));
							$freeTextSet = true;													
						} else {
							$modifiedName=preg_replace('/&|;| /',"_",$rate['delivery_type']);
							$method->setMethodTitle(Mage::helper('shipping')->__($rate['delivery_type']));							
						}					
				}
				else if ($price==0  && $this->getConfigData('zero_shipping_text')!='') {
       	   			$modifiedName=preg_replace('/&|;| /',"_",$this->getConfigData('zero_shipping_text'));
					$method->setMethodTitle($this->getConfigData('zero_shipping_text'));
				} else {
       	   			$modifiedName=preg_replace('/&|;| /',"_",$rate['method_name']);
					$method->setMethodTitle(Mage::helper('shipping')->__($rate['delivery_type']));
				}

				$method->setMethod($modifiedName);
				$method->setMethodDescription($rate['notes']);
				
				$method->setPrice($shippingPrice);
				$method->setCost($rate['cost']);
				$method->setDeliveryType($rate['delivery_type']);

				$result->append($method);
				
				if ($freeTextSet) break;
			}
		}
        return $result;
    }

    public function getCode($type, $code='')
    {
        $codes = array(

           	'condition_name'=>array(
                'per_item_bare' => Mage::helper('shipping')->__('Per Item Bare Totalling'),
                'per_item_surcharge' => Mage::helper('shipping')->__('Per Item Surcharge Totalling'),
        		'per_item' => Mage::helper('shipping')->__('Per Item Totalling'),
                'per_product' => Mage::helper('shipping')->__('Per Product Totalling'),
          		'per_product_bare' => Mage::helper('shipping')->__('Per Product Bare Totalling'),
        		'per_package'  => Mage::helper('shipping')->__('Per Package Totalling'),
            	'highest'  => Mage::helper('shipping')->__('Highest Price Totalling'),
            	'lowest'  => Mage::helper('shipping')->__('Lowest Price Totalling'),
            	'order'  => Mage::helper('shipping')->__('Defined Order Totalling')
        	),

            'condition_name_short'=>array(
                'per_item_bare' => Mage::helper('shipping')->__('Per Item Bare Totalling'),
                'per_item_surcharge' => Mage::helper('shipping')->__('Per Item Surcharge Totalling'),
                'per_item' => Mage::helper('shipping')->__('Per Item Totalling'),
                'per_product' => Mage::helper('shipping')->__('Per Product Totalling'),
               	'per_product_bare' => Mage::helper('shipping')->__('Per Product Bare Totalling'),
            	'per_package'  => Mage::helper('shipping')->__('Per Package Totalling'),
            	'highest'  => Mage::helper('shipping')->__('Highest Price Totalling'),
        		'lowest'  => Mage::helper('shipping')->__('Lowest Price Totalling'),
        		'order'  => Mage::helper('shipping')->__('Defined Order Totalling'),
        	),

        	'parent_group'=>array(
            	'child'  			=> Mage::helper('shipping')->__('Default(Child) Shipping Group'),
            	'both'  			=> Mage::helper('shipping')->__('Parent Shipping Group'),
        		'configurable'  	=> Mage::helper('shipping')->__('Configurable Parent, Bundle Child'),
        		'bundle'  			=> Mage::helper('shipping')->__('Configurable Child, Bundle Parent'),
        	),        
        	'postcode_filtering'=>array(
            	'uk'  			=> Mage::helper('shipping')->__('UK'),
        		'canada'  		=> Mage::helper('shipping')->__('Canada Ranges'),
        		'numeric'  		=> Mage::helper('shipping')->__('Numerical Ranges  (US/AUS/FR/etc)'),
        		'both'  		=> Mage::helper('shipping')->__('Both UK and Numeric'),
        		'can_numeric'  	=> Mage::helper('shipping')->__('Both Canada and Numeric'),
        		'none'  		=> Mage::helper('shipping')->__('None/Pattern Matching'),
        ),
        'shipoptions'=>array(
               	'use_base'  		=> Mage::helper('shipping')->__('Always use base currency prices'),     
        		'append_star_rates' => Mage::helper('shipping')->__('Append * shipping group rates'),
        		'apply_handling'  	=> Mage::helper('shipping')->__('Apply handling fee on zero shipping'),
        		'custom_sorting'  	=> Mage::helper('shipping')->__('Custom sorting'),
        		'remove_virtual'  	=> Mage::helper('shipping')->__('Exclude virtual from cart price'),
        		'filter_subtotal'  	=> Mage::helper('shipping')->__('Filter on subtotal price/weight'),
            	'show_tooltips'  	=> Mage::helper('shipping')->__('Show tooltips'),
        	    'split_custom'		=> Mage::helper('shipping')->__('Split shipping groups based on custom options being present' ),
        		'use_discounted'  	=> Mage::helper('shipping')->__('Use discounted price'),
            	'group_text'  		=> Mage::helper('shipping')->__('Use text based shipping group'),
        		'use_tax'			=> Mage::helper('shipping')->__('Use tax inclusive prices'),
        		'NONE'  			=> Mage::helper('shipping')->__('N/A'),
        ),
        

        );

        if (!isset($codes[$type])) {
            throw Mage::exception('Mage_Shipping', Mage::helper('shipping')->__('Invalid Product Matrix code type: %s', $type));
        }

        if (''===$code) {
            return $codes[$type];
        }

        if (!isset($codes[$type][$code])) {
            throw Mage::exception('Mage_Shipping', Mage::helper('shipping')->__('Invalid Product Matrix  code for type %s: %s', $type, $code));
        }

        return $codes[$type][$code];
    }

    public function getRate(Mage_Shipping_Model_Rate_Request $request)
    {  	
        return Mage::getResourceModel('productmatrix_shipping/carrier_productmatrix')->getNewRate($request);
    }

 	/**
     * Get allowed shipping methods
     *
     * @return array
     */
    public function getAllowedMethods()
    {
       $collection = Mage::getResourceModel('productmatrix_shipping/carrier_productmatrix_collection');
       $collection = $collection->setDistinctDeliveryTypeFilter();
       $collection->load();
       $allowedMethods=array();
       $deliveryTypes=array();
       $freeText=preg_replace('/&|;| /',"_",$this->getConfigData('free_shipping_text'));
       $zeroText=preg_replace('/&|;| /',"_",$this->getConfigData('zero_shipping_text'));
       foreach ($collection->getItems() as $item) {
       	   $newDelType=preg_replace('/&|;| /',"_",$item['delivery_type']);
       	   $deliveryTypes[]=$newDelType;
       	   $allowedMethods[$newDelType] = $item['delivery_type'];
       }
       $deliveryTypes[]=$freeText;
       $allowedMethods[$freeText] = $this->getConfigData('free_shipping_text');
       $deliveryTypes[]=$zeroText;
       $allowedMethods[$zeroText] = $this->getConfigData('zero_shipping_text');
       return $allowedMethods;
    }
    
    protected function _updateFreeMethodQuote($request)
    {
        if ($request->getFreeMethodWeight() == $request->getPackageWeight() || !$request->hasFreeMethodWeight()) {
            return;
        }

        $freeMethodArr = explode(',',$this->getConfigData('free_method'));
        if (!$freeMethodArr) {
            return;
        }
        $freeRateIdArr = array();

        if (is_object($this->_result)) {
            foreach ($this->_result->getAllRates() as $i=>$item) {
                if (in_array($item->getMethod(),$freeMethodArr)) {
                    $freeRateIdArr[$item->getMethod()] = $i;
                }
            }
        }

        if (count($freeRateIdArr)==0) {
            return;
        }
        
        $result = $this->_getQuotes();
        
        foreach ($freeRateIdArr as $freeMethod=>$freeRateId) {
	        $price = null;
	        if ($request->getFreeMethodWeight() > 0) {
	            $this->_setFreeMethodRequest($freeMethod);	
	            
	            if ($result && ($rates = $result->getAllRates()) && count($rates)>0) {
	                if ((count($rates) == 1) && ($rates[0] instanceof Mage_Shipping_Model_Rate_Result_Method)) {
	                    $price = $rates[0]->getPrice();
	                }
	                if (count($rates) > 1) {
	                    foreach ($rates as $rate) {
	                        if ($rate instanceof Mage_Shipping_Model_Rate_Result_Method
                            	&& $rate->getMethod() == $freeMethod
	                        ) {
	                            $price = $rate->getPrice();
	                        }
	                    }
	                }
	            }
	        } else {
	            /**
	             * if we can apply free shipping for all order we should force price
	             * to $0.00 for shipping with out sending second request to carrier
	             */
	            $price = 0;
	        }
	
	        /**
	         * if we did not get our free shipping method in response we must use its old price
	         */
	        if (!is_null($price)) {
	            $this->_result->getRateById($freeRateId)->setPrice($price);
	        }
        }
    }
    


}
