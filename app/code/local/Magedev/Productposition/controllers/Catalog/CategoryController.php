<?php 
/**
 * Set Category controller
 * @category   Category Save
 * @package    Magedev_Productposition
 * @author     Mage Developer(mage.devloper@gmail.com)
 */
require_once 'Mage/Adminhtml/controllers/Catalog/CategoryController.php';
class Magedev_Productposition_Catalog_CategoryController extends Mage_Adminhtml_Catalog_CategoryController
{ 
    /**
     * Category save
     */
    public function saveAction()
    {
        if (!$category = $this->_initCategory()) {
            return;
        }

        $storeId = $this->getRequest()->getParam('store');
        if ($data = $this->getRequest()->getPost()) {
            $category->addData($data['general']);
            if (!$category->getId()) {
                $parentId = $this->getRequest()->getParam('parent');
                if (!$parentId) {
                    if ($storeId) {
                        $parentId = Mage::app()->getStore($storeId)->getRootCategoryId();
                    }
                    else {
                        $parentId = Mage_Catalog_Model_Category::TREE_ROOT_ID;
                    }
                }
                $parentCategory = Mage::getModel('catalog/category')->load($parentId);
                $category->setPath($parentCategory->getPath());
            }
            /**
             * Check "Use Default Value" checkboxes values
             */
            if ($useDefaults = $this->getRequest()->getPost('use_default')) {
                foreach ($useDefaults as $attributeCode) {
                    $category->setData($attributeCode, null);
                }
            }

            $category->setAttributeSetId($category->getDefaultAttributeSetId());

            if (isset($data['category_products']) &&
                !$category->getProductsReadonly()) {
                // get new product associations
				$newProducts = array();
                parse_str($data['category_products'], $newProducts);

/* update product position Start*/
				
				// get unassigned(old) product position
				$oldProducts = $category->getProductsPosition();

				// merging new and old product position(|| assigned)
				$newlyAddedProducts 	= array_diff($newProducts, $oldProducts);
				$newlyDeletedProducts 	= array_diff($oldProducts, $newProducts);
				$products = $oldProducts + $newlyAddedProducts;
				foreach($newlyDeletedProducts as $key=>$val) {
					unset($products[$key]);
				}
				
				// handle newly associated products
				foreach($products as $productId => $position) {
					if (empty($position) || $position == 0) {
						$products[$productId] = max($products)+1;
					}
				}
				
				// handling unassocaited products (if any)
				asort($products);
				$productPositionIndex = array_flip($products);
				$shift = array_shift($productPositionIndex);
				array_unshift($productPositionIndex, 0, $shift);
				unset($productPositionIndex[0]);
				$products = array_flip($productPositionIndex);

/* update product position Ends*/

                $category->setPostedProducts($products);
            }

            Mage::dispatchEvent('catalog_category_prepare_save', array(
                'category' => $category,
                'request' => $this->getRequest()
            ));

            try {
                $category->save();
				$catgId = $category->getId();

                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('catalog')->__('Category saved'));
                $refreshTree = 'true';
            }
            catch (Exception $e){
                $this->_getSession()->addError($e->getMessage())
                    ->setCategoryData($data);
                $refreshTree = 'false';
            }
        }
        $url = $this->getUrl('*/*/edit', array('_current' => true, 'id' => $category->getId()));
        $this->getResponse()->setBody(
            '<script type="text/javascript">parent.updateContent("' . $url . '", {}, '.$refreshTree.');</script>'
        );
    }
    
	public function updateProductAction()
	{
		$storeId = $this->getRequest()->getParam('store_id');
		$field = $this->getRequest()->getParam('field');
		$data = $this->getRequest()->getPost();
		$value = $this->getRequest()->getPost('value');
		$productId = $this->getRequest()->getParam('productid');
		$_product = Mage::getModel('catalog/product')->load($productId);
		//$_product->setName($value);
	
	try {
		if($field == "inventory")
		{
			if(!is_numeric($value)){echo $value = 'Error: Please enter only numeric.';die;}
			$newQty = $value;
			$status = 0;
			if ($newQty>=0){$status=1;}
			  $stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($productId);
			  $stock->setQty($newQty)->setIsInStock((bool)$status);
			  // save stock record
			  if(!$stock->save()){$value = 'Error: found in request.';}
		  
		}else{
			
 			if($field == "price" || $field == "special_price"){if(!is_numeric($value)){echo $value = 'Error: Please enter only numeric.';die;}}
			
			$_product->setData($field, $value);
			if(isset($storeId) && $storeId!= ""){Mage::app()->setCurrentStore($storeId);}
			if(!$_product->save()){$value = 'Error: found in request.';}
			
		 }	
		if($field == 'status'){
			if($value == 1){$returnStatus = "Enabled";}else if($value == 2){$returnStatus = "Disabled";}
			$this->getResponse()->setBody($returnStatus);
		}else{$this->getResponse()->setBody($value);}
	 
	 } catch (Exception $e) {
            die($e->getMessage());
        }

	}
	public function getGridBackAfterUpdateAction()
    {
        if (!$category = $this->_initCategory()) {
            return;
        }
		
		$catId = $category->getId();
		
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('adminhtml/catalog_category_tab_enahancedproducts')
			//->setId($catId)
			->setData('id', $catId)
			->setData('store', '1')
			->toHtml()
        );
    }
	
	public function updateProductPositionsAction()
	{
		$categoryId 	= (int) $this->getRequest()->getParam('categoryId');
		$productId 		= (int) $this->getRequest()->getParam('productId');
		$productNewPosition	= (int) $this->getRequest()->getParam('productNewPosition');
		
		// Load Category
		$category = Mage::getModel('catalog/category')->load($categoryId);

		// Get Product Postions
		$prodductPositionArray = $category->getProductsPosition();
		asort($prodductPositionArray);
		
        // Update product position
		if (!isset($prodductPositionArray[$productId])) {
            $this->_fault('product is not assigned');
        }
		
		$i = 1;
		foreach($prodductPositionArray as $key => $val) {
			$positions[$key] = $i;
			$i++;
		}
		
		// current product postion
		$currentPosition = $positions[$productId];
		
		// adjust product position values in sequence for all associated products
		$positions = array_flip($positions);
		
		$pageNumber = $this->getRequest()->getParam('page');
		if(isset($pageNumber) && $pageNumber !="" )
		{	
			$pageLimit = $this->getRequest()->getParam('limit');
			if(!isset($pageLimit) && $pageLimit== "")
			{$pageLimit = 1000;}
			
			$pageNumber = $pageNumber-1;
			$noOfProducts = $pageNumber*$pageLimit;
			$productNewPosition = $noOfProducts + $productNewPosition;
		}
		
		if ($currentPosition < $productNewPosition) {
			$i = $currentPosition +1;
			// decrement indexes by 1 in position
			while($i <= $productNewPosition) {
				$positions[$i-1] = $positions[$i];
				$i++;
			}
		} else if ($currentPosition > $productNewPosition) {
			$i = $currentPosition - 1;
			// increment indexes by 1 in position
			while($i >= $productNewPosition) {
				$positions[$i+1] = $positions[$i];
				$i--;
			}
		}
		$positions[$productNewPosition] = $productId;
		$positions = array_flip($positions);
		
		
		$positions[$productId] = $productNewPosition;
		$category->setPostedProducts($positions);

        try {
          $category->save();
        } catch (Mage_Core_Exception $e) {
            $this->_fault('data_invalid', $e->getMessage());
        }
		
		// set grid after update
		//$this->getGridBackAfterUpdateAction();
		$this->enhancedgridAction();
	}
	
	public function enhancedgridAction()
    {

        if (!$category = $this->_initCategory(true)) {
            return;
        }
		$catId = $category->getId();
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('adminhtml/catalog_category_tab_enahancedproducts')
 			->toHtml()
        );
    }
 }
