<?php
/* ProductMatrix
 *
 * @category   Webshopapps
 * @package    Webshopapps_productmatrix
 * @copyright  Copyright (c) 2010 Zowta Ltd (http://www.webshopapps.com)
 * @license    http://www.webshopapps.com/license/license.txt - Commercial license
 */


class Webshopapps_Productmatrix_Model_Mysql4_Carrier_Productmatrix extends Mage_Core_Model_Mysql4_Abstract
{

	private $_request;
	private $_zipSearchString;
	private $_table;
	private $_customerGroupCode;
	private $_starIncludeAll;
	private $_minusOne;
	private $_exclusionList;
	private $_structuredItems;
	private $_hasEmptyPackages;
	private $_prioritySet;
	private $_maxPriceSet;
	private $_freeShipping;
	private $_useParent;
	private $_useBundleParent=false;
	private $_useConfigurableParent=true;
	private $_appendStarRates;
	private $_stockFound = false;
	private $_outofstock = false;
	private $_debug;
	private $_options;
	private $_ignoreAdditionalItemPrice;
	private $_ignoreFreeItems;
	private $_shortMatchPostcode = '';
	private $_longMatchPostcode = '';	
	private $_twoPhaseFiltering;
	

    protected function _construct()
    {
        $this->_init('shipping/productmatrix', 'pk');
    }

    public function getNewRate(Mage_Shipping_Model_Rate_Request $request)
    {

    	$this->_debug= Mage::helper('wsalogger')->isDebug('Webshopapps_Productmatrix');
		$this->_hasEmptyPackages=false;
    	$this->_prioritySet = false;
    	$this->_ignoreAdditionalItemPrice=false;
        $read = $this->_getReadAdapter();
        $this->_maxPriceSet = false;
        $this->_freeShipping = $request->getFreeShipping();
        $starIncludeAll = Mage::getStoreConfig("carriers/productmatrix/star_include_all");
        $postcodeFilter = Mage::getStoreConfig("carriers/productmatrix/postcode_filter");       
        $this->_options = explode(',',Mage::getStoreConfig("carriers/productmatrix/ship_options"));
        
        if ($this->_debug) {
        	Mage::helper('wsalogger/log')->postDebug('productmatrix','Postcode Format',$postcodeFilter);	
			Mage::helper('wsalogger/log')->postDebug('productmatrix','Option Settings',$this->_options);		
        }		
		
        if ( Mage_Usa_Model_Shipping_Carrier_Abstract::USA_COUNTRY_ID == $request->getDestCountryId() ||  'BR' == $request->getDestCountryId()) {
    		$postcode = substr($request->getDestPostcode(), 0, 5); 
        } else {
    		$postcode = $request->getDestPostcode();
    	}
    	
    	
    	
        $this->_table = Mage::getSingleton('core/resource')->getTableName('productmatrix_shipping/productmatrix');
        $this->_appendStarRates =  in_array('append_star_rates',$this->_options);
        
        
        $this->processZipcode($read, $postcode, 
			$this->_twoPhaseFiltering, $this->_zipSearchString, $this->_shortMatchPostcode, $this->_longMatchPostcode );


        // if POBOX search on CITY field
        $searchPOBox=false;
    	if (preg_match("/p\.* *o\.* *box/i", $request->getDestStreet())) {
  			$searchPOBox=true;
    		if ($this->_debug) {
  				Mage::helper('wsalogger/log')->postDebug('productmatrix','POBox check','We cannot deliver to PO boxes.');
  				
    		}
		}

        // make global as used all around
        $this->_request=$request;
        $this->_starIncludeAll=$starIncludeAll;


		$items = $request->getAllItems();
		if (empty($items) || ($items=='')) {
			return;
		}

		$this->_customerGroupCode = $this->getCustomerGroupCode();
		if ($this->_debug) {
			Mage::helper('wsalogger/log')->postInfo('productmatrix','Customer Group Code',$this->_customerGroupCode);				
		}

		// get the package_id's for the items in the cart

		$conditionName=$this->_request->getPMConditionName();
		$this->populateStructuredItems($items,$conditionName);
        if (!Mage::helper('wsacommon')->checkItems('Y2FycmllcnMvcHJvZHVjdG1hdHJpeC9zaGlwX29uY2U=',
        	'dXBzaWRlZG93bg==','Y2FycmllcnMvcHJvZHVjdG1hdHJpeC9zZXJpYWw=')) {
 	     		Mage::helper('wsalogger/log')->postCritical('productmatrix',base64_decode('TGljZW5zZQ=='),base64_decode('U2VyaWFsIEtleSBJbnZhbGlk'));        
        		return; }

		$first=true;
		foreach ($this->_structuredItems as $structuredItem) {
			if ($structuredItem['package_id']=='none' && $this->_starIncludeAll) { continue; }
			$this->_minusOne=false;
			if(!$first) {
				$data=$this->runSelectStmt($read,$structuredItem,$searchPOBox);
				if (!empty($data)) {
					if ($conditionName=='highest') {
						$this->mergeHighest($data,$finalResults);
					} else if ($conditionName=='lowest') {
						$this->mergeLowest($data,$finalResults);
					} else if ($conditionName=='order') {
						$this->mergeOrdered($data,$finalResults);
					} else {
						$this->mergeResults($data,$finalResults);
					}
				} else if (!$this->_starIncludeAll  || (!$this->_starIncludeAll && $this->_minusOne)){
					return;
				}
			} else {
				$data=$this->runSelectStmt($read,$structuredItem,$searchPOBox);
				if (!empty($data)) {
					$first=false;
					$finalResults=$data;
				} else if (!$this->_starIncludeAll  || (!$this->_starIncludeAll && $this->_minusOne)) {
					return;
				}
			}
		}


		if (empty($finalResults)) { return; }
		if (!empty($this->_exclusionList)) {
			foreach ($finalResults as $key=>$result) {
				foreach ($this->_exclusionList as $ekey=>$exclusionItem) {
					if ($result['delivery_type']==$exclusionItem['delivery_type']) {
						$finalResults[$key]="";
						break;
						}
				}

			}
		    foreach ($finalResults as $key=>$result) {
	    		if (empty($finalResults[$key])) {
	    			unset($finalResults[$key]);
	    		}
	    	}
		}

		if (empty($finalResults)) { return; }

		if ($this->_prioritySet) {
		    foreach ($finalResults as $i => $rate) {
		        $priceArr[$i] = $rate['price'];
		        $priority[$i] = $rate['priority'];
		    }

			array_multisort($priceArr, SORT_ASC, $priority, SORT_ASC, $finalResults);
			$previousPrice=-100;
			$previousPriority="";
			foreach ($finalResults as $data) {
				if ($previousPrice==$data['price'] && $data['priority']!=$previousPriority  && is_numeric($data['priority']) && is_numeric($previousPriority)) {
					continue;
				} else {
					$previousPrice=$data['price'];
					$previousPriority=$data['priority'];
					$absoluteResults[]=$data;
				}
			}
		} else {
			$absoluteResults=$finalResults;
		}
		if ($conditionName=="highest" && !$this->_ignoreAdditionalItemPrice)
		{
			foreach ($absoluteResults as $key=>$data) {
				$absoluteResults[$key]['price'] = $data['price'] + $data['additional_price'];
				if ($data['qty']>1 && $data['multiprice']>0 && !$data['override']) {
					$absoluteResults[$key]['price'] = $absoluteResults[$key]['price'] + $data['multiprice']*($data['qty']-1);
				}
				$tempAlgorithm=explode("=",$data['algorithm'],2);
				if (strtolower($tempAlgorithm[0]) == ('ai'))
				{
					if ($data['qty']=1 && $data['multiprice']>0 && !$data['override']) {
						$absoluteResults[$key]['price'] = $absoluteResults[$key]['price'] + $data['multiprice']*($data['qty']);
					}
				}
			}
		}

		if ($this->_maxPriceSet) {
			foreach ($absoluteResults as $key=>$data) {
				if ($data['max_price']!=-1 && $data['price']>$data['max_price']) {
					$absoluteResults[$key]['price'] = $data['max_price'];
				}
			}
		}

		return $absoluteResults;
    }
    
    private function getCustomerGroupCode() {
    	
    	if ($ruleData = Mage::registry('rule_data')) {
            $gId = $ruleData->getCustomerGroupId();
            return Mage::getModel('customer/group')->load($gId)->getCode();
    	} else {
    		return Mage::getModel('customer/group')->load(
			Mage::getSingleton('customer/session')->getCustomerGroupId())->getCode();
    	}
    	 
    }
    
	protected function processZipcode($readAdaptor, $customerPostcode,&$twoPhaseFiltering,
		&$zipString, &$shortMatchPostcode, &$longMatchPostcode ) {
			
        $postcodeFilter = Mage::getStoreConfig("carriers/productmatrix/postcode_filter");       
        
		$customerPostcode = trim($customerPostcode);
		$twoPhaseFiltering = false;
		if ($postcodeFilter == 'numeric' && is_numeric($customerPostcode)) {			
			$zipString = ' AND '.$customerPostcode.' BETWEEN dest_zip AND dest_zip_to )';
			
		} else if ($postcodeFilter == 'uk' && strlen($customerPostcode)>4) {
			$twoPhaseFiltering = true;
			$longPostcode=substr_replace($customerPostcode,"",-3);
			$longMatchPostcode = trim($longPostcode);
			$shortMatchPostcode = preg_replace('/\d/','', $longMatchPostcode);
			$shortMatchPostcode = $readAdaptor->quoteInto(" AND STRCMP(LOWER(dest_zip),LOWER(?)) = 0)", $shortMatchPostcode);
		}  else if ($postcodeFilter == 'both') {
			if (is_numeric($customerPostcode)){
				$zipString = ' AND '.$customerPostcode.' BETWEEN dest_zip AND dest_zip_to )';
			} else {
				$twoPhaseFiltering = true;
				$longPostcode=substr_replace($customerPostcode,"",-3);
				$longMatchPostcode = trim($longPostcode);
				$shortMatchPostcode = preg_replace('/\d/','', $longMatchPostcode);
				$shortMatchPostcode = $readAdaptor->quoteInto(" AND STRCMP(LOWER(dest_zip),LOWER(?)) = 0)", $shortMatchPostcode);
			}
		} else if ($postcodeFilter == 'canada') { 
			// first search complete postcode
			// then search exact match on first 3 chars
			// then search range
			$shortPart = substr($customerPostcode,0,3);
			if (strlen($shortPart) < 3 || !is_numeric($shortPart[1]) || !ctype_alpha($shortPart[2])) {
				$zipString = $readAdaptor->quoteInto(" AND ? LIKE dest_zip )", $customerPostcode);
			} else {
				$suffix = strtoupper($shortPart[2]);
				$zipFromRegExp='^'.$shortPart[0].'[0-'.$shortPart[1].'][A-'.$suffix.']$';
				$zipToRegExp='^'.$shortPart[0].'['.$shortPart[1].'-9]['.$suffix.'-Z]$';
				$shortMatchPostcode = $readAdaptor->quoteInto(" AND dest_zip REGEXP ?", $zipFromRegExp).$readAdaptor->quoteInto(" AND dest_zip_to REGEXP ? )",$zipToRegExp );
				$longMatchPostcode = $customerPostcode;
				$twoPhaseFiltering = true;
			}
		} else if ($postcodeFilter == 'can_numeric') { 
			if (is_numeric($customerPostcode)){
				$zipString = ' AND '.$customerPostcode.' BETWEEN dest_zip AND dest_zip_to )';
			} else {
				// first search complete postcode
				// then search exact match on first 3 chars
				// then search range
				$shortPart = substr($customerPostcode,0,3);
				if (strlen($shortPart) < 3 || !is_numeric($shortPart[1]) || !ctype_alpha($shortPart[2])) {
					$zipString = $readAdaptor->quoteInto(" AND ? LIKE dest_zip )", $customerPostcode);
				} else {
					$suffix = strtoupper($shortPart[2]);
					$zipFromRegExp='^'.$shortPart[0].'[0-'.$shortPart[1].'][A-'.$suffix.']$';
					$zipToRegExp='^'.$shortPart[0].'['.$shortPart[1].'-9]['.$suffix.'-Z]$';
					$shortMatchPostcode = $readAdaptor->quoteInto(" AND dest_zip REGEXP ?", $zipFromRegExp).$readAdaptor->quoteInto(" AND dest_zip_to REGEXP ? )",$zipToRegExp );
					$longMatchPostcode = $customerPostcode;
					$twoPhaseFiltering = true;
				}
			}
		} else {
			 $zipString = $readAdaptor->quoteInto(" AND ? LIKE dest_zip )", $customerPostcode);
		}
		
		if ($this->_debug) {
        	Mage::helper('wsalogger/log')->postDebug('productmatrix','Postcode Filter',$postcodeFilter);	
			Mage::helper('wsalogger/log')->postDebug('productmatrix','Postcode Range Search String',$zipString);	
        	if ($twoPhaseFiltering) {
        		Mage::helper('wsalogger/log')->postDebug('productmatrix','Postcode 2 Phase Search String','short match:'.$shortMatchPostcode.
        			', long match:'.$longMatchPostcode);	
        	}
    	}
				
	}
    

    private function mergeHighest($indResults,&$baseResults)
    {
    	foreach ($baseResults as $key=>$result)
    	{
    	   	$found=false;
    		foreach ($indResults as $indKey=>$data) {
    			if ($result['delivery_type']==$data['delivery_type']) {
    				if (!$baseResults[$key]['override'] && ($data['price']>$baseResults[$key]['price'] || $data['override'])) { // if higher get higher
						$baseResults[$key]['price']=$data['price'];
						$baseResults[$key]['additional_price']+=$baseResults[$key]['multiprice']*$baseResults[$key]['qty'];
						$baseResults[$key]['multiprice']=$data['multiprice'];
						$baseResults[$key]['qty']=$data['qty'];
					} else {
						$baseResults[$key]['additional_price']+=$data['multiprice']*$data['qty'];
					}
					if ($baseResults[$key]['max_price']<$data['max_price']) {
						$baseResults[$key]['max_price']=$data['max_price'];
					}
					$indResults[$indKey]['found']=true;
					$found=true;
					break;
				}
    		}
    		if (!$found && !$this->_starIncludeAll && !$baseResults[$key]['showall']) {
    		    // no match so remove
    			if ($this->_debug) {
					Mage::helper('wsalogger/log')->postInfo('productmatrix','Delivery Type - No Match Found - Removing',$baseResults[$key]['delivery_type']);				
				}
    			$baseResults[$key]="";
    		}
    	}
    	// get show all items
    	foreach ($indResults as $key=>$result)
    	{
    		if ( !$found && $result['showall']) {
    			$baseResults[]=$result;
    			$indResults[$key]['found']=true;
    		}
    	}

    	if ($this->_starIncludeAll) {
    	   	// check for missing
	    	foreach ($indResults as $data) {
	    		if (empty($data['found'])) {
	    			$baseResults[]=$data;
	    		}
	    	}
    	} else {
	     	// unset here so we dont upset the apple cart
	    	foreach ($baseResults as $key=>$result) {
	    		if (empty($baseResults[$key])) {
	    			unset($baseResults[$key]);
	    		}
	    	}
    	}
    }




    /**
     * Merge results together, ignore any not in base result set
     * @param $indResults
     * @param $baseResults - passed by reference
     * @return unknown_type
     */
    private function mergeResults($indResults,&$baseResults)
    {
    	if ($this->_debug) {
    		Mage::helper('wsalogger/log')->postDebug('productmatrix','merge result to add',$indResults);
    		Mage::helper('wsalogger/log')->postDebug('productmatrix','merge base results',$baseResults);
    	}
    	foreach ($baseResults as $key=>$result)
    	{
    		$found=false;
    		foreach ($indResults as $indKey=>$data) {
    			if ($result['delivery_type']==$data['delivery_type']) {
					$baseResults[$key]['found']=true;
					$indResults[$indKey]['found']=true;
    				if ($baseResults[$key]['max_price']<$data['max_price']) {
						$baseResults[$key]['max_price']=$data['max_price'];
					}
    				if ($baseResults[$key]['override']) {
    					$found=true;
    					break;
    				} else if ($data['override']) {
    					$baseResults[$key]['price']=$data['price'];
    					$baseResults[$key]['override']=true;
    					$baseResults[$key]['package_id']=$baseResults[$key]['package_id'].",".$data['package_id'];
						$found=true;
						break;
    				} else {
						// add to existing
						$baseResults[$key]['price']+=$data['price'];
						$baseResults[$key]['package_id']=$baseResults[$key]['package_id'].",".$data['package_id'];
						$found=true;
						break;
    				}
				}
    		}
    		if (!$found && !$baseResults[$key]['showall']) {  // no match
    			if ( !$this->_starIncludeAll) {
    				if ($this->_debug) {
						Mage::helper('wsalogger/log')->postInfo('productmatrix','Delivery Type - No Match Found - Removing',$baseResults[$key]['delivery_type']);				
					}
    				$baseResults[$key]="";
    			} else {
    				if ($result['package_id']!="" && count($this->_structuredItems)>1 && $indResults[0]['package_id']!="" && !$baseResults[$key]['showall'] ) {
    					if ($this->_debug) {
							Mage::helper('wsalogger/log')->postInfo('productmatrix','Delivery Type - No Match Found - Removing',$baseResults[$key]['delivery_type']);				
						}
    					$baseResults[$key]="";
    				}
    			}
    		}
    	}
    	if ($this->_starIncludeAll) {
    	   	// check for missing
	    	foreach ($indResults as $key=>$data) {
	    		if (empty($data['found']) && $data['package_id']=="") {
	    			$indResults[$key]['found']=true;
	    			$baseResults[]=$data;
	    		}
	    	}

	    	// this was changed to be ==package_id from != - reason unclear
	    	if ($this->_appendStarRates) {
		    	foreach ($baseResults as $key=>$result) {
		    		if ($result!="" && !$baseResults[$key]['showall'] && $this->_hasEmptyPackages && $result['package_id']=="") {
		    			if ($this->_debug) {
							Mage::helper('wsalogger/log')->postInfo('productmatrix','Delivery Type - No Match Found - Removing',$baseResults[$key]['delivery_type']);				
						}
	    				$baseResults[$key]="";
		    		}
		    	}
	    	} else {
		    	foreach ($baseResults as $key=>$result) {
		    		if ($result!=""  && !$baseResults[$key]['showall'] && (!isset($result['found']) || !$result['found'])) {
		    			if ($this->_debug) {
							Mage::helper('wsalogger/log')->postInfo('productmatrix','Delivery Type - No Match Found - Removing',$baseResults[$key]['delivery_type']);				
						}
	    				$baseResults[$key]="";
		    		}
		    	}
	    	}
    	}
    	foreach ($indResults as $key=>$result)
    	{
    		if ($result['showall'] && (!array_key_exists('found',$result) || !$result['found'])) {
    			$baseResults[]=$result;
    		}
    	}
    	// unset here so we dont upset the apple cart
    	foreach ($baseResults as $key=>$result) {
    		if (empty($baseResults[$key])) {
    			unset($baseResults[$key]);
    		}
    	}

    }

    private function mergeOrdered($indResults,&$baseResults) {

   		//this also supports sorting

   		if (!array_key_exists('order',$indResults[0]) || !array_key_exists('order',$baseResults[0])) {
    		return;
    	}
    	// just take the first
    	$order=$indResults[0]['order'];
    	if ($order<$baseResults[0]['order']) {
    		reset($baseResults);
    		$baseResults=$indResults;
    	}


    }

    private function mergeLowest($indResults,&$baseResults)
    {

    	foreach ($baseResults as $key=>$result)
    	{
    	   	$found=false;
    		foreach ($indResults as $indKey=>$data) {
    			if ($result['delivery_type']==$data['delivery_type']) {
					// if lower get lower
					if ($data['price']<$baseResults[$key]['price']) {
						$baseResults[$key]['price']=$data['price'];
					}
					$indResults[$indKey]['found']=true;
					$found=true;
					break;
				}
    		}
    		if (!$found && !$this->_starIncludeAll) {
    			// no match so remove
    			$baseResults[$key]="";
    		}
    	}

    	if ($this->_starIncludeAll) {
    	   	// check for missing
	    	foreach ($indResults as $data) {
	    		if (empty($data['found'])) {
	    			$baseResults[]=$data;
	    		}
	    	}
    	} else {
	    	// unset here so we dont upset the apple cart
	    	foreach ($baseResults as $key=>$result) {
	    		if (empty($baseResults[$key])) {
	    			unset($baseResults[$key]);
	    		}
	    	}
    	}
    }
	private function runSelectStmt($read,$structuredItem,$searchPOBox)
	{
		$conditionName=$this->_request->getPMConditionName();
		if ($this->_twoPhaseFiltering) {
			$switchSearches=13;
		} else {
			$switchSearches=9;
		}
		for ($j=0;$j<$switchSearches;$j++)
		{
			$select = $this->getSwitchSelect($read,$j,$searchPOBox);

			if ($structuredItem['package_id']=='include_all' || $structuredItem['package_id']=='none') {
				$select->where('package_id=?','');
			} else {
				$select->where('package_id=?', $structuredItem['package_id']);
			}

			$select->where('weight_from_value<?', $structuredItem['weight']);
			$select->where('weight_to_value>=?', $structuredItem['weight']);
			$select->where('price_from_value<?', $structuredItem['price']);
			$select->where('price_to_value>=?', $structuredItem['price']);
			$select->where('item_from_value<?', $structuredItem['qty']);
			$select->where('item_to_value>=?', $structuredItem['qty']);

			$groupArr[0]="STRCMP(LOWER(customer_group),LOWER('".$this->_customerGroupCode."')) =0";
			$groupArr[1]="customer_group=''";
			$select->where(join(' OR ', $groupArr));

			$select->where('website_id=?', $this->_request->getWebsiteId());

			$select->order('notes ASC');
			$select->order('price ASC');
			$select->order('algorithm ASC');
			/*
			pdo has an issue. we cannot use bind
			*/
			try {
				$row = $read->fetchAll($select);
			} catch (Exception $e) { 
				 	Mage::helper('wsalogger/log')->postWarning('productmatrix','SQL Exception',$e);		
			}

			if (!empty($row)) {
				if ($this->_debug) {
					Mage::helper('wsalogger/log')->postDebug('productmatrix','SQL Select',$select->getPart('where'));				
					Mage::helper('wsalogger/log')->postDebug('productmatrix','SQL Result',$row);				
				}
				$newdata=array();
				$priorityData=array();
				foreach ($row as $data) {
					if ($data['price']==-1) {

						$exclusionItem=array ( 'package_id' => $structuredItem['package_id'],
											   'delivery_type' => $data['delivery_type']);
						$this->_exclusionList[]=$exclusionItem;
						$this->_minusOne=true;
						continue;
					}
					$data['priority']=0;
					$data['multiprice']="";
					$data['additional_price']=0;
					$data['qty']=0;
					$data['max_price']=-1;
					$data['override']=false;
					$data['showall']=false;
					$data['set_cart']=false;
					$decrease = -1;
					$priorityTypes=array();
					$data['method_name']=$data['delivery_type'];
					if ($data['algorithm']!="") {
						$algorithm_array=explode("&",$data['algorithm']);  // Multi-formula extension
						reset($algorithm_array);
						$skipData=false;
						foreach ($algorithm_array as $algorithm_single) {
							$algorithm=explode("=",$algorithm_single,2);
							if (!empty($algorithm) && count($algorithm)==2) {
								if (strtolower($algorithm[0])=="w") {
									// weight based
									$weightIncrease=explode("@",$algorithm[1]);
									if (!empty($weightIncrease) && count($weightIncrease)==2 ) {
										$weightDifference=	$structuredItem['weight']-$data['weight_from_value'];
										$quotient=$weightDifference / $weightIncrease[0];
										$data['price']=$data['price']+$weightIncrease[1]*$quotient;
									}
								}else if (strtolower($algorithm[0])=="wa") {
									$weightIncrease=explode("@",$algorithm[1]);
								if (!empty($weightIncrease) && count($weightIncrease)==2 ) {
										$weight= $structuredItem['weight'];
										$quotient=$weight / $weightIncrease[0];
										$data['price']=$data['price']+$weightIncrease[1]*$quotient;
									}
								} else if (strtolower($algorithm[0])=="wc") {
									// weight based
									$weightIncrease=explode("@",$algorithm[1]);
									if (!empty($weightIncrease) && count($weightIncrease)==2 ) {
										$weightDifference=	$structuredItem['weight']-$data['weight_from_value'];
										$quotient=ceil($weightDifference / $weightIncrease[0]);
										$data['price']=$data['price']+$weightIncrease[1]*$quotient;
									}
								}else if (strtolower($algorithm[0])=="wca") {
									// weight based
									$weightIncrease=explode("@",$algorithm[1]);
									if (!empty($weightIncrease) && count($weightIncrease)==2 ) {
										$weight= $structuredItem['weight'];
										$quotient=ceil($weight / $weightIncrease[0]);
										$data['price']=$data['price']+$weightIncrease[1]*$quotient;
									}
								}else if (strtolower($algorithm[0])=="p" ) {
									$this->_prioritySet=true;
									$data['priority']=$algorithm[1];
								} else if (strtolower($algorithm[0])=="alt" ) {
									$priorityTypes=explode(",",$algorithm[1]);
									
									
								} else if (strtolower($algorithm[0])=="m" ) {
									$this->_maxPriceSet = true;
									$data['max_price']=$algorithm[1];
								} else if (strtolower($algorithm[0])=="i" ) {
									if (strtolower($algorithm[1])=='ignore') {
										$this->_ignoreAdditionalItemPrice=true;
									} else {
										if ($conditionName=='per_package') {
											$data['price']+=$algorithm[1]*($structuredItem['qty']-$data['item_from_value']);
										} else {
											$data['multiprice']=$algorithm[1];
											$data['qty']=$structuredItem['qty'];
										}
									}
								} else if (strtolower($algorithm[0])=="io" ) {
									if ($conditionName=='per_package') {
										$data['price']+=$algorithm[1]*($structuredItem['qty']-$data['item_from_value']);
									} else {
										$data['multiprice']=$algorithm[1];
										$data['qty']=1;
									}
								} else if (strtolower($algorithm[0])=="im") {									
									$itemIncrease=explode("@",$algorithm[1]);
									if (!empty($itemIncrease) && count($itemIncrease)==2 ) {
										$qty=$structuredItem['qty'];
										$quotient=ceil($qty / $itemIncrease[0]);
										if ($conditionName=='per_package') {
											$data['price']=$data['price']+$itemIncrease[1]*$quotient;
										} else {
											$data['multiprice']=$data['price']+$itemIncrease[1]*$quotient;
											$data['qty']=$structuredItem['qty'];											
										}
									}
								} else if (strtolower($algorithm[0])=="ai" ) {
									if ($conditionName=='per_package') {
										$data['price']+=$algorithm[1]*($structuredItem['qty']);
									} else {
										$data['multiprice']=$algorithm[1];
										$data['qty']=$structuredItem['qty'];
									}
								} else if (strtolower($algorithm[0])=="a" ) {
									if ($this->_request->getUpsDestType()=='RES') {
										if ($algorithm[1]!='residential') {
											$skipData = true;
											break;
										}
									} else {
										if ($algorithm[1]!='commercial') {
											$skipData = true;
											break;
										}
									}
								} else if (strtolower($algorithm[0])=="o" ) {
									$data['order']=$algorithm[1];
								} else if (strtolower($algorithm[0])=="%" ) {
									$perSplit=explode("+",$algorithm[1]);
									if (!empty($perSplit) && count($perSplit)==2) {
										$percentage = $perSplit[0];
										$flatAdd = $perSplit[1];
									} else {
										$percentage = $algorithm[1];
										$flatAdd = 0;
									}
									$percPrice=($structuredItem['price']*$percentage/100)+$flatAdd;
									if ($percPrice>$data['price']) {
										$data['price']=$percPrice;
									}
								} else if (strtolower($algorithm[0])=="r") {
									$decrease=$algorithm[1];
								} else if (strtolower($algorithm[0])=="c") {
									$data['method_name']=$algorithm[1];
								} else if (strtolower($algorithm[0])=="setcart") {
									$data['set_cart']=$algorithm[1];
								}
								 else if (strtolower($algorithm[0])=="instock" && strtolower($algorithm[1])=="true") {
									if (!$this->_stockFound) {
										if ($this->checkOutOfStock($this->_request)) {
											$skipData = true;
											break;
										}
									} else {
										if ($this->_outofstock) {
											$skipData = true;
											break;
										}
									}
							/*	} else if (strtolower($algorithm[0])=="z") {
									if (strtolower($algorithm[1])=="custom" && !$structuredItem['options_present']) {
										$skipData = true;
										break;
									} else if (strtolower($algorithm[1])=="notcustom" && $structuredItem['options_present']) {
										$skipData = true;
										break;
									}*/
								} else if (strtolower($algorithm[0])=="instock" && strtolower($algorithm[1])=="false") {
									if (!$this->_stockFound) {
										if (!$this->checkOutOfStock($this->_request)) {
											$skipData = true;
											break;
										}
									} else {
										if (!$this->_outofstock) {
											$skipData = true;
											break;
										}
									}
								}
							} else {
								switch ($algorithm_single) {
									case "OVERRIDE":
										$data['override']=true;
										break;
									case "OVERRIDE_SINGLE":
										if (count($this->_structuredItems)<3) {
											$data['override']=true;
										}
										break;
									case "SHOWALL":
										$data['showall']=true;
										break;
								}
							}

						}
						if ($skipData) { continue; }
						if ($decrease>-1) {
							$data['price'] = $data['price']-$decrease;
							if ($data['price']<0) { $data['price']=0; }
						}
					}

					if ($conditionName=='per_product') {
						// for each unique product in basket for this package id (e.g. product A&B of package id Z)
						$data['price']=$data['price']*$structuredItem['unique'];
					} else if ($conditionName=='per_item' || $conditionName=='per_item_bare') {
						// foreach item in basket for this package_id (e.g. 3*product A of package id Z)
						if(!$data['set_cart']){
							$data['price']=$data['price']*$structuredItem['qty'];
						}
					}else if ($conditionName=='per_product_bare') {
						$data['price']=$data['price']*$structuredItem['unique'];
					}
					else if ($structuredItem['package_id']!='include_all' && $conditionName=='per_item_surcharge'  ) {
						$data['price']=$data['price']*$structuredItem['qty'];
					}
					
					if (count($priorityTypes>0)) {
						$data['priority']=0;
						$priorityNum=1;
						$this->_prioritySet=true;
						foreach ($priorityTypes as $priorityType) {
							$copyData = $data;
							$copyData['priority']=$priorityNum;
							$priorityNum++;
							$copyData['delivery_type']=$priorityType;
							$copyData['method_name']=$priorityType;
							$priorityData[]=$copyData;
						}
						
					}
					
					$newdata[]=$data;
				}
				if (count($priorityData)>0) {
					$newdata = array_merge($newdata, $priorityData);
				}
				if (!empty($newdata)) {
    				return $newdata;
				} else {
					return;
				}
			}
		}
	}
	
	private function checkOutOfStock($request) {
		$items = $request->getAllItems();
    	foreach($items as $item) {
    		if ($item->getBackorders() != Mage_CatalogInventory_Model_Stock::BACKORDERS_NO) {
    			$this->_outofstock=true;
    		}
    	}
    	$this->_stockFound=true;
    	return $this->_outofstock;
    }

	private function getSwitchSelect($read,$j,$searchPOBox) {

		if ($searchPOBox) {
			$destCity='POBOX';
		} else {
			$destCity=$this->_request->getDestCity();
		}


		//$select = $read->select()->from($table);
		$select = $read->select()->from(array('productmatrix'=>$this->_table),
						array(	'pk'=>'pk',
								'price'=>'price',
								'delivery_type'=>'delivery_type',
								'package_id'=>'package_id',
								'weight_from_value'=>'weight_from_value',
								'item_from_value'=>'item_from_value',
								'algorithm'=>'algorithm',
								'notes'=>'notes',
								'cost'=>'cost'));

		if ($this->_twoPhaseFiltering) {
			switch($j) {
				case 0:
					$select->where(
						$read->quoteInto(" (dest_country_id=? ", $this->_request->getDestCountryId()).
							$read->quoteInto(" AND dest_region_id=? ", $this->_request->getDestRegionId()).
							$read->quoteInto(" AND STRCMP(LOWER(dest_city),LOWER(?)) = 0  ", $destCity).
    						$read->quoteInto(" AND STRCMP(LOWER(dest_zip),LOWER(?)) = 0)", $this->_longMatchPostcode)
						);
					break;
				case 1:
					$select->where(
						$read->quoteInto(" (dest_country_id=? ", $this->_request->getDestCountryId()).
							$read->quoteInto(" AND dest_region_id=? ", $this->_request->getDestRegionId()).
							$read->quoteInto(" AND STRCMP(LOWER(dest_city),LOWER(?)) = 0  ", $destCity).
							$this->_shortMatchPostcode
					);
					break;
				case 2:
					$select->where(
						$read->quoteInto(" (dest_country_id=? ", $this->_request->getDestCountryId()).
							$read->quoteInto(" AND dest_region_id=?  AND dest_city=''", $this->_request->getDestRegionId()).
							$read->quoteInto(" AND STRCMP(LOWER(dest_zip),LOWER(?)) = 0 )", $this->_longMatchPostcode)
						);
					break;
				case 3:
					$select->where(
						$read->quoteInto(" (dest_country_id=? ", $this->_request->getDestCountryId()).
							$read->quoteInto(" AND dest_region_id=?  AND dest_city=''", $this->_request->getDestRegionId()).
    						$this->_shortMatchPostcode
					);
					break;
				case 4:
					$select->where(
						$read->quoteInto(" (dest_country_id=? ", $this->_request->getDestCountryId()).
							$read->quoteInto(" AND dest_region_id=? ", $this->_request->getDestRegionId()).
							$read->quoteInto(" AND STRCMP(LOWER(dest_city),LOWER(?)) = 0  AND dest_zip='')", $destCity)
						);
					break;
				case 5:
					$select->where(
					   $read->quoteInto("  (dest_country_id=? ", $this->_request->getDestCountryId()).
							$read->quoteInto(" AND STRCMP(LOWER(dest_city),LOWER(?)) = 0  AND dest_region_id='0'", $destCity).
							$read->quoteInto(" AND STRCMP(LOWER(dest_zip),LOWER(?)) = 0 )", $this->_longMatchPostcode)
					   );
					break;
				case 6:
					$select->where(
					   $read->quoteInto("  (dest_country_id=? ", $this->_request->getDestCountryId()).
							$read->quoteInto(" AND STRCMP(LOWER(dest_city),LOWER(?)) = 0  AND dest_region_id='0'", $destCity).
							$this->_shortMatchPostcode
					   );
					break;
				case 7:
					$select->where(
					   $read->quoteInto("  (dest_country_id=? ", $this->_request->getDestCountryId()).
							$read->quoteInto(" AND STRCMP(LOWER(dest_city),LOWER(?)) = 0  AND dest_region_id='0' AND dest_zip='') ", $destCity)
					   );
					break;
				case 8:
					$select->where(
						$read->quoteInto("  (dest_country_id=? AND dest_region_id='0' AND dest_city='' ", $this->_request->getDestCountryId()).
							$read->quoteInto(" AND STRCMP(LOWER(dest_zip),LOWER(?)) = 0 )", $this->_longMatchPostcode)
							);
					break;
				case 9:
					$select->where(
						$read->quoteInto("  (dest_country_id=? AND dest_region_id='0' AND dest_city='' ", $this->_request->getDestCountryId()).
							$this->_shortMatchPostcode
						);
					break;
				case 10:
					$select->where(
					   $read->quoteInto("  (dest_country_id=? ", $this->_request->getDestCountryId()).
							$read->quoteInto(" AND dest_region_id=? AND dest_city='' AND dest_zip='') ", $this->_request->getDestRegionId())
					   );
					break;

				case 11:
					$select->where(
					   $read->quoteInto("  (dest_country_id=? AND dest_region_id='0' AND dest_city='' AND dest_zip='') ", $this->_request->getDestCountryId())
					);
					break;

				case 12:
					$select->where(
							"  (dest_country_id='0' AND dest_region_id='0' AND dest_zip='')"
				);
					break;
			}

		} else {
			switch($j) {
				case 0:
					$select->where(
						$read->quoteInto(" (dest_country_id=? ", $this->_request->getDestCountryId()).
							$read->quoteInto(" AND dest_region_id=? ", $this->_request->getDestRegionId()).
							$read->quoteInto(" AND STRCMP(LOWER(dest_city),LOWER(?)) = 0  ", $destCity).
							$this->_zipSearchString
						);
					break;
				case 1:
					$select->where(
						$read->quoteInto(" (dest_country_id=? ", $this->_request->getDestCountryId()).
							$read->quoteInto(" AND dest_region_id=?  AND dest_city=''", $this->_request->getDestRegionId()).
							$this->_zipSearchString
						);
					break;
				case 2:
					$select->where(
						$read->quoteInto(" (dest_country_id=? ", $this->_request->getDestCountryId()).
							$read->quoteInto(" AND dest_region_id=? ", $this->_request->getDestRegionId()).
							$read->quoteInto(" AND STRCMP(LOWER(dest_city),LOWER(?)) = 0  AND dest_zip='')", $destCity)
						);
					break;
				case 3:
					$select->where(
					   $read->quoteInto("  (dest_country_id=? ", $this->_request->getDestCountryId()).
							$read->quoteInto(" AND STRCMP(LOWER(dest_city),LOWER(?)) = 0  AND dest_region_id='0'", $destCity).
							$this->_zipSearchString
					   );
					break;
				case 4:
					$select->where(
					   $read->quoteInto("  (dest_country_id=? ", $this->_request->getDestCountryId()).
							$read->quoteInto(" AND STRCMP(LOWER(dest_city),LOWER(?)) = 0  AND dest_region_id='0' AND dest_zip='') ", $destCity)
					   );
					break;
				case 5:
					$select->where(
						$read->quoteInto("  (dest_country_id=? AND dest_region_id='0' AND dest_city='' ", $this->_request->getDestCountryId()).
							$this->_zipSearchString
						);
					break;
				case 6:
					$select->where(
					   $read->quoteInto("  (dest_country_id=? ", $this->_request->getDestCountryId()).
							$read->quoteInto(" AND dest_region_id=? AND dest_city='' AND dest_zip='') ", $this->_request->getDestRegionId())
					   );
					break;

				case 7:
					$select->where(
					   $read->quoteInto("  (dest_country_id=? AND dest_region_id='0' AND dest_city='' AND dest_zip='') ", $this->_request->getDestCountryId())
					);
					break;

				case 8:
					$select->where(
							"  (dest_country_id='0' AND dest_region_id='0' AND dest_zip='')"
				);
					break;
			}
		}


		return $select;
	}

	private function populateStructuredItems($items, $conditionName)
	{
		$specialPrice = 0;
		$specialWeight = 0;
		$this->_structuredItems=array();
		$this->_useParent = Mage::getStoreConfig("carriers/productmatrix/parent_group");
		if ($this->_debug) {
			Mage::helper('wsalogger/log')->postDebug('productmatrix','Settings','Use Parent:'.$this->_useParent);				
		}
		switch ($this->_useParent) {
			case "none":
				$this->_useBundleParent=false;
				$this->_useConfigurableParent=false;
				break;
			case "both":
				$this->_useBundleParent=true;
				$this->_useConfigurableParent=true;
				break;
			case "bundle":
				$this->_useBundleParent=true;
				$this->_useConfigurableParent=false;
				break;
			case "configurable":
				$this->_useBundleParent=false;
				$this->_useConfigurableParent=true;
				break;
			default:
				$this->_useBundleParent=false;
				$this->_useConfigurableParent=false;
				break;

		}

		$filterPrice = in_array('filter_subtotal',$this->_options);
		$useDiscountValue = in_array('use_discounted',$this->_options);
		$splitCustom = in_array('split_custom',$this->_options);
		$useBase = in_array('use_base',$this->_options);
		$useTax = in_array('use_tax',$this->_options);
		$runningCartPrice = 0;

		$version = Mage::helper('wsacommon')->getVersion();
		$freeship=$this->_request->getIgnoreFreeItems();
		if  ($version == 1.6 || $version == 1.7 || $version == 1.8) { 
			// freemethodweight not supported. Do what we did before
			$freeship=true;
		} 
		foreach($items as $item) {

			$weight=0;
			$qty=0;
			$price=0;	
			$temp;		

			if($item->getProduct()->isVirtual()){
				if (!Mage::helper('wsacommon/shipping')->getVirtualItemTotals($item, $weight,$qty,$price,$this->_useBundleParent,
					$freeship,$temp,$useDiscountValue, false, $useBase, $useTax)) {
						continue;
				}
			}
			else {				
				if (!Mage::helper('wsacommon/shipping')->getItemTotals($item, $weight,$qty,$price,$this->_useBundleParent,
					$freeship,$temp,$useDiscountValue, false, $useBase, $useTax)) {
						continue;
				}
			}
			
			$runningCartPrice += $price;

			if ($item->getParentItem()!=null &&
				$this->_useBundleParent &&   $item->getProductType() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE ) {
					// must be a bundle/configurable
					$product = Mage::getModel('catalog/product')->getResourceCollection()
					    ->addAttributeToSelect('package_id')
					    ->addAttributeToSelect('shipping_qty')
					    ->addAttributeToFilter('entity_id',$item->getParentItem()->getProductId());
			} else if ($item->getParentItem()!=null &&
				$this->_useConfigurableParent &&   $item->getProductType() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE ) {
					$product = Mage::getModel('catalog/product')->getResourceCollection()
					    ->addAttributeToSelect('package_id')
					    ->addAttributeToSelect('shipping_qty')
					    ->addAttributeToFilter('entity_id',$item->getParentItem()->getProductId());
			} else if ($item->getProductType() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE && !$this->_useConfigurableParent ) {
				if ($item->getHasChildren()) {
                	foreach ($item->getChildren() as $child) {
                		$product = Mage::getModel('catalog/product')->getResourceCollection()
						    ->addAttributeToSelect('package_id')
						    ->addAttributeToSelect('shipping_qty')
						    ->addAttributeToFilter('entity_id',$child->getProductId());
						    break;
                	}
				}
			} else {
				$product = Mage::getModel('catalog/product')->getResourceCollection()
					    ->addAttributeToSelect('package_id')
					    ->addAttributeToSelect('shipping_qty')
					    ->addAttributeToFilter('entity_id',$item->getProductId());
			}



			// if find a surcharge check to see if it is on the item or the order
			// if on the order then add to the surcharge_order_price if > than previous
			// if on the item then multiple by qty and add to the surcharge_price
			foreach($product as $object) {
				continue;
			}
			
			if (!is_object($product)) {
				$this->_structuredItems[] = "";
				Mage::helper('wsalogger/log')->postCritical('productmatrix','Fatal Error','Item/Product is Malformed');				
				break;
			}

			$shipQty=$object->getData('shipping_qty');

			if ($shipQty=="" || !is_numeric($shipQty)) {
				$shipQty=1;
			}

			if (in_array('group_text',$this->_options)) {
				$packageId = $object->getData('package_id');
			} else {
				$packageId = $object->getAttributeText('package_id');
			}
			
			if (empty($packageId)) { $packageId='none'; $this->_hasEmptyPackages=true; }
			
			if ($splitCustom) {
				$options = Mage::helper('catalog/product_configuration')->getCustomOptions($item);
				if (!empty($options)  && is_array($options[0])) {
					$packageId .= '-OPTIONS';
				} 
			}
			
			$found=false;


			if ($conditionName=='per_item_bare' || $conditionName=='per_item_surcharge' ||  $conditionName=='per_product_bare') {
				$prodArray=array( 'package_id'  => $packageId,
						  'qty' 				=> $qty,
						  'weight'				=> $weight/$qty,
						  'price'				=> $price/$qty,
						  'unique'		 		=> 1);
				$this->_structuredItems[]=$prodArray;

			} else {

				foreach($this->_structuredItems as $key=>$structuredItem) {
					if ($structuredItem['package_id']==$packageId) {
						// have already got this package id
						$this->_structuredItems[$key]['qty']=$this->_structuredItems[$key]['qty']+$qty*$shipQty;
						$this->_structuredItems[$key]['weight']=$this->_structuredItems[$key]['weight']+ $weight;
						$this->_structuredItems[$key]['price']=$this->_structuredItems[$key]['price']+ $price;
						$this->_structuredItems[$key]['unique']+=1;
						$found=true;
						break;
					}
				}

				if (!$found){
					$prodArray=array( 'package_id'  => $packageId,
							  'qty' 				=> $qty*$shipQty,
							  'weight'				=> $weight,
							  'price'				=> $price,
					          'unique'				=> 1);
					$this->_structuredItems[]=$prodArray;

				}
			}
			// also add to include_all package Id
			if ($this->_starIncludeAll ) {
				if ($packageId=="SPECIAL_FREE") {
					$specialPrice += $price;
					$specialWeight += $weight;
				} else {
					$found=false;
					if ($useDiscountValue) {
						$groupPrice = $this->_request->getPackageValueWithDiscount();
					} else {
						$groupPrice = $price;
					}
					foreach($this->_structuredItems as $key=>$structuredItem) {
						if ($structuredItem['package_id']=='include_all') {
							$this->_structuredItems[$key]['qty']=$this->_structuredItems[$key]['qty']+$qty*$shipQty;
							$this->_structuredItems[$key]['weight']=$this->_structuredItems[$key]['weight']+ $weight;
							if (!$useDiscountValue) {
								$this->_structuredItems[$key]['price']=$this->_structuredItems[$key]['price']+ $price;
							}
							$this->_structuredItems[$key]['unique']+=1;
							$found=true;
							break;
						}
					}
					if (!$found) {
						$prodArray=array( 'package_id'  => 'include_all',
						  'qty' 				=> $qty*$shipQty,
						  'weight'				=> $weight,
						  'price'				=> $groupPrice,
						  'unique'				=> 1);
						$this->_structuredItems[]=$prodArray;
					}
				}
			}
		}
		
		if ($filterPrice) {
			foreach($this->_structuredItems as $key=>$structuredItem) {
				$this->_structuredItems[$key]['price'] = $runningCartPrice - $specialPrice;
				$this->_structuredItems[$key]['weight'] = $this->_request->getPackageWeight() - $specialWeight;
			}
		}
		if ($this->_debug) {
			Mage::helper('wsalogger/log')->postDebug('productmatrix','Structured Items',$this->_structuredItems);				
		}
	}
	


	/**
	 * CSV Import routine
	 * @param $object
	 * @return unknown_type
	 */
    public function uploadAndImport(Varien_Object $object)
    {
        $csvFile = $_FILES["groups"]["tmp_name"]["productmatrix"]["fields"]["import"]["value"];
        $csvName = $_FILES["groups"]["name"]["productmatrix"]["fields"]["import"]["value"];
        $dataStored = false;
		$session = Mage::getSingleton('adminhtml/session');
        	
        if (!empty($csvFile)) {

            $csv = trim(file_get_contents($csvFile));
            
			Mage::helper('wsacommon/shipping')->saveCSV($csv, $csvName);
			
            $table = Mage::getSingleton('core/resource')->getTableName('productmatrix_shipping/productmatrix');

            $websiteId = $object->getScopeId();
            $websiteModel = Mage::app()->getWebsite($websiteId);

            if (!empty($csv)) {
                $exceptions = array();
                $csvLines = explode("\n", $csv);
                $csvLine = array_shift($csvLines);
                $csvLine = $this->_getCsvValues($csvLine);
                if (count($csvLine) < 15) {
                    $exceptions[0] = Mage::helper('shipping')->__('Invalid Product Matrix File Format');
                }

                $countryCodes = array();
                $regionCodes = array();
                foreach ($csvLines as $k=>$csvLine) {
                    $csvLine = $this->_getCsvValues($csvLine);
                    if (count($csvLine) > 0 && count($csvLine) < 15) {
                        $exceptions[0] = Mage::helper('shipping')->__('Invalid Product Matrix File Format %s',$csvLine);
                    } else {
                        $splitCountries = explode(",", trim($csvLine[0]));
                    	$splitRegions = explode(",", trim($csvLine[1]));
                        foreach ($splitCountries as $country) {
                        	$countryCodes[] = trim($country);
                    	}
                    	foreach ($splitRegions as $region) {
                        	$regionCodes[] = $region;
                    	}
                   	}
                }

             	if (empty($exceptions)) {
                    $connection = $this->_getWriteAdapter();

                     $condition = array(
                        $connection->quoteInto('website_id = ?', $websiteId),
                    );
                    $connection->delete($table, $condition);



                }
                if (!empty($exceptions)) {
                    throw new Exception( "\n" . implode("\n", $exceptions) );
                }


                if (empty($exceptions)) {
                	$data = array();
                    $countryCodesToIds = array();
                    $regionCodesToIds = array();
                    $countryCodesIso2 = array();
                    $counter=0;

                    $countryCollection = Mage::getResourceModel('directory/country_collection')->addCountryCodeFilter($countryCodes)->load();
                    foreach ($countryCollection->getItems() as $country) {
                        $countryCodesToIds[$country->getData('iso3_code')] = $country->getData('country_id');
                        $countryCodesToIds[$country->getData('iso2_code')] = $country->getData('country_id');
                        $countryCodesIso2[] = $country->getData('iso2_code');
                    }

                    $regionCollection = Mage::getResourceModel('directory/region_collection')
                        ->addRegionCodeFilter($regionCodes)
                        ->addCountryFilter($countryCodesIso2)
                        ->load();


                    foreach ($regionCollection->getItems() as $region) {
                        $regionCodesToIds[$countryCodesToIds[$region->getData('country_id')]][$region->getData('code')] = $region->getData('region_id');
                    }

                    foreach ($csvLines as $k=>$csvLine) {
                        $csvLine = $this->_getCsvValues($csvLine);
                        $splitCountries = explode(",", trim($csvLine[0]));
                        $splitRegions = explode(",", trim($csvLine[1]));
                        $splitPostcodes = explode(",",trim($csvLine[3]));
                        $customerGroups = explode(",",trim($csvLine[12]));

						if ($csvLine[2] == '*' || $csvLine[2] == '') {
							$city = '';
						} else {
							$city = $csvLine[2];
						}

						if ($csvLine[4] == '*' || $csvLine[4] == '') {
							$zip_to = '';
						} else {
							$zip_to = $csvLine[4];
						}


						if ($csvLine[5] == '*' || $csvLine[5] == '') {
							$package_id = '';
						} else {
							$package_id = $csvLine[5];
						}

                    	if ( $csvLine[6] == '*' || $csvLine[6] == '') {
							$weight_from = -1;
						} else if (!$this->_isPositiveDecimalNumber($csvLine[6])) {
							$exceptions[] = Mage::helper('shipping')->__('Invalid weight From "%s" in the Row #%s', $csvLine[6], ($k+1));
                    	} else {
							$weight_from = (float)$csvLine[6];
						}

						if ( $csvLine[7] == '*' || $csvLine[7] == '') {
							$weight_to = 10000000;
						} else if (!$this->_isPositiveDecimalNumber($csvLine[7])) {
							$exceptions[] = Mage::helper('shipping')->__('Invalid weight To "%s" in the Row #%s', $csvLine[7], ($k+1));
						}
						else {
							$weight_to = (float)$csvLine[7];
						}

						if ( $csvLine[8] == '*' || $csvLine[8] == '') {
							$price_from = -1;
						} else if (!$this->_isPositiveDecimalNumber($csvLine[8]) ) {
							$exceptions[] = Mage::helper('shipping')->__('Invalid price From "%s" in the Row #%s',  $csvLine[8], ($k+1));
						} else {
							$price_from = (float)$csvLine[8];
						}

						if ( $csvLine[9] == '*' || $csvLine[9] == '') {
							$price_to = 10000000;
						} else if (!$this->_isPositiveDecimalNumber($csvLine[9])) {
							$exceptions[] = Mage::helper('shipping')->__('Invalid price To "%s" in the Row #%s', $csvLine[9], ($k+1));
						} else {
							$price_to = (float)$csvLine[9];
						}

						if ( $csvLine[10] == '*' || $csvLine[10] == '') {
							$item_from = 0;
						} else if (!$this->_isPositiveDecimalNumber($csvLine[10]) ) {
							$exceptions[] = Mage::helper('shipping')->__('Invalid item From "%s" in the Row #%s',  $csvLine[10], ($k+1));
						} else {
							$item_from = (float)$csvLine[10];
						}

						if ( $csvLine[11] == '*' || $csvLine[11] == '') {
							$item_to = 10000000;
						} else if (!$this->_isPositiveDecimalNumber($csvLine[11])) {
							$exceptions[] = Mage::helper('shipping')->__('Invalid item To "%s" in the Row #%s', $csvLine[11], ($k+1));
						} else {
							$item_to = (float)$csvLine[11];
						}


                      	foreach ($customerGroups as $customer_group) {
						
	                    	if ($customer_group == '*') {
								$customer_group = '';
							} 

	                      	foreach ($splitCountries as $country) {
	
	                      		$country=trim($country);
	
	                        	if (empty($countryCodesToIds) || !array_key_exists($country, $countryCodesToIds)) {
		                        	$countryId = '0';
		                            if ($country != '*' && $country != '') {
		                                $exceptions[] = Mage::helper('shipping')->__('Invalid Country "%s" in the Row #%s', $country, ($k+1));
		                                break;
		                            }
		                        } else {
		                            $countryId = $countryCodesToIds[$country];
		                        }
	
	                        	foreach ($splitRegions as $region) {
	
	                        		if (!isset($countryCodesToIds[$country])
			                            || !isset($regionCodesToIds[$countryCodesToIds[$country]])
			                            || !array_key_exists($region, $regionCodesToIds[$countryCodesToIds[$country]])) {
			                            $regionId = '0';
				                        if ($region != '*' && $region != '') {
			                            	$exceptions[] = Mage::helper('shipping')->__('Invalid Region/State "%s" in the Row #%s', $region, ($k+1));
			                            	break;
			                            }
			                        } else {
			                            $regionId = $regionCodesToIds[$countryCodesToIds[$country]][$region];
			                        }
	
	                        		foreach ($splitPostcodes as $postcode) {
										if ($postcode == '*' || $postcode == '') {
											$zip = '';
											$new_zip_to = '';
										} else {
											$zip_str = explode("-", $postcode);
											if(count($zip_str) != 2)
											{
												$zip = trim($postcode);
												if (ctype_digit($postcode) && trim($zip_to) == '') {
													$new_zip_to = trim($postcode);
												} else $new_zip_to = $zip_to;
											}
											else {
												$zip = trim($zip_str[0]);
												$new_zip_to = trim($zip_str[1]);
											}
										}
	
										if (count($csvLine)==15) {
											$data[] = array('website_id'=>$websiteId, 'dest_country_id'=>$countryId, 'dest_region_id'=>$regionId,
												'dest_city'=>$city, 'dest_zip'=>$zip, 'dest_zip_to'=>$new_zip_to,
												'package_id'=>$package_id,
												'weight_from_value'=>$weight_from,'weight_to_value'=>$weight_to,
												'price_from_value'=>$price_from,'price_to_value'=>$price_to,
												'item_from_value'=>$item_from,'item_to_value'=>$item_to,
												'customer_group'=>$customer_group,
												'price'=>$csvLine[13], 'delivery_type'=>$csvLine[14]);
										} else {
											$data[] = array('website_id'=>$websiteId, 'dest_country_id'=>$countryId, 'dest_region_id'=>$regionId,
											'dest_city'=>$city, 'dest_zip'=>$zip, 'dest_zip_to'=>$new_zip_to,
											'package_id'=>$package_id,
											'weight_from_value'=>$weight_from,'weight_to_value'=>$weight_to,
											'price_from_value'=>$price_from,'price_to_value'=>$price_to,
											'item_from_value'=>$item_from,'item_to_value'=>$item_to,
											'customer_group'=>$customer_group,
											'price'=>$csvLine[13], 'algorithm'=>$csvLine[14], 'delivery_type'=>$csvLine[15], 'notes'=>$csvLine[16]);
										}
	
										$dataDetails[] = array('country'=>$country, 'region'=>$region);
										$counter++;
		                        	}
	                        	}
	                        }
                      	}

                        $dataStored = false;
                   		if (!empty($exceptions)) {
			            	break;
			            }
	                    if ($counter>3000) {
		                    foreach($data as $k=>$dataLine) {
		                        try {
		                            $connection->insert($table, $dataLine);
		                        } catch (Exception $e) {
		                            $exceptions[] = Mage::helper('shipping')->__('Duplicate Row #%s (Country "%s", Region/State "%s", Zip "%s")', ($k+1), $dataLine['dest_country_id'], $dataLine['dest_region_id'], $dataLine['dest_zip']);
		                            $exceptions[] = $e;
		                       }
		                    }
		                    if (!empty($exceptions)) {
			                	break;
			                }
	                		Mage::helper('wsacommon/shipping')->updateStatus($session,count($data));
			               	unset($data);
			                unset($dataDetails);
			                $counter=0;
			                $dataStored = true;
		                }
                    }
                }
                if (empty($exceptions) && !$dataStored) {
                	foreach($data as $k=>$dataLine) {
			           try {
			           	$connection->insert($table, $dataLine);
			           } catch (Exception $e) {
			           	$exceptions[] = Mage::helper('shipping')->__('Duplicate Row #%s (Country "%s", Region/State "%s", Zip "%s", Delivery Type "%s")', ($k+1), $dataDetails[$k]['country'], 
			           					$dataDetails[$k]['region'], $dataLine['dest_zip'], $dataLine['delivery_type']);
			            Mage::log($e);
			            break;
			           }
	                }
	                Mage::helper('wsacommon/shipping')->updateStatus($session,count($data));
			        
                }
            	if (!empty($exceptions)) {
            		throw new Exception( "\n" . implode("\n", $exceptions) );
                }
            }
        }
    }

    private function _getCsvValues($string, $separator=",")
    {
        $elements = explode($separator, trim($string));
        for ($i = 0; $i < count($elements); $i++) {
            $nquotes = substr_count($elements[$i], '"');
            if ($nquotes %2 == 1) {
                for ($j = $i+1; $j < count($elements); $j++) {
                    if (substr_count($elements[$j], '"') > 0) {
                        // Put the quoted string's pieces back together again
                        array_splice($elements, $i, $j-$i+1, implode($separator, array_slice($elements, $i, $j-$i+1)));
                        break;
                    }
                }
            }
            if ($nquotes > 0) {
                // Remove first and last quotes, then merge pairs of quotes
                $qstr =& $elements[$i];
                $qstr = substr_replace($qstr, '', strpos($qstr, '"'), 1);
                $qstr = substr_replace($qstr, '', strrpos($qstr, '"'), 1);
                $qstr = str_replace('""', '"', $qstr);
            }
            $elements[$i] = trim($elements[$i]);
        }
        return $elements;
    }

    private function _isPositiveDecimalNumber($n)
    {
        return preg_match ("/^[0-9]+(\.[0-9]*)?$/", $n);
    }
    public function getShippingCostsforSKU($sku)
    {
       $collection = Mage::getResourceModel('productmatrix_shipping/carrier_productmatrix_collection');
       return $collection->getSkuCosts($sku, $collection);

    }
}
