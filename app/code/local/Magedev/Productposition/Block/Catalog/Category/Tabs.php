<?php
/**
 * Custom enhanced Product Position Category Tab
 *
 * @category   Magedev
 * @package    Magedev
 */
require_once 'Mage/Adminhtml/Block/Catalog/Category/Tabs.php';
class Magedev_Productposition_Block_Catalog_Category_Tabs extends Mage_Adminhtml_Block_Catalog_Category_Tabs
{ 
    protected function _prepareLayout()
    {
	   parent::_prepareLayout(); 
	    
        $this->addTab('productpos', array(
            'label'     => Mage::helper('catalog')->__('Update Product Position'),
            'content'   => $this->getLayout()->createBlock('adminhtml/catalog_category_tab_enahancedproducts', 'category.product.enahancedproducts.grid')->toHtml(),
        ));
    }
}
