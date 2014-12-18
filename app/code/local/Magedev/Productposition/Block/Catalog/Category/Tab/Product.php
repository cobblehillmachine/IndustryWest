<?php
/**
 * Disable the Administrator to set number in Position column
 *
 * @category   Magedev
 * @package    Magedev_Productposition
 * @author      Magento Core Team <core@magentocommerce.com>
 */
 
require_once 'Mage/Adminhtml/Block/Catalog/Category/Tab/Product.php';
class Magedev_Productposition_Block_Catalog_Category_Tab_Product extends Mage_Adminhtml_Block_Catalog_Category_Tab_Product
{
    protected function _prepareColumns()
    {
		$cols = parent::_prepareColumns();
 		/*$this->addColumn('position', array( // just update the position disable for category products tab
			'header'    => Mage::helper('catalog')->__('Position'),
			'class'		=> 'a-center',
			'width'     => '1',
			'type'      => 'number',
			'index'     => 'position',
		));*/ 
		
		$this->addColumn('position', array(
            'header'    => Mage::helper('catalog')->__('Position'),
            'width'     => '1',
 			'sortable'  => false,
	 		'filter'  => false,
            'type'      => 'number',
            'index'     => 'position',
            'editable'  => !$this->getCategory()->getProductsReadonly()
            //'renderer'  => 'adminhtml/widget_grid_column_renderer_input'
			
        ));
		
		return $cols;
    }
}

