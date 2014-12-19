<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2010-2011 Amasty (http://www.amasty.com)
 * @package Amasty_Pgrid
 */
class Amasty_Pgrid_Block_Adminhtml_Catalog_Category_Tab_Product extends Mage_Adminhtml_Block_Catalog_Category_Tab_Product
{
    protected function _getStore()
    {
        $storeId = (int) $this->getRequest()->getParam('store', 0);
        return Mage::app()->getStore($storeId);
    }

    protected function _prepareCollection()
    {
        parent::_prepareCollection();

        if (Mage::getStoreConfig('ampgrid/category/thumb'))
        {
            $this->getCollection()->joinAttribute('thumbnail', 'catalog_product/thumbnail', 'entity_id', null, 'left', $this->_getStore()->getId());
        }

        return Mage_Adminhtml_Block_Widget_Grid::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        if (Mage::getStoreConfig('ampgrid/additional/thumb'))
        {
            if (method_exists($this, "addColumnAfter"))
            {
                $this->addColumnAfter('thumb',
                    array(
                        'header'    => Mage::helper('catalog')->__('Thumbnail'),
                        'renderer'  => 'ampgrid/adminhtml_catalog_product_grid_renderer_thumb',
                        'index'		=> 'thumbnail',
                        'sortable'  => true,
                        'filter'    => false,
                        'width'     => 90,
                    ), 'entity_id');
            } else
            {
                // will add thumbnail column to be the first one
                $this->addColumn('thumb',
                    array(
                        'header'    => Mage::helper('catalog')->__('Thumbnail'),
                        'renderer'  => 'ampgrid/adminhtml_catalog_product_grid_renderer_thumb',
                        'index'		=> 'thumbnail',
                        'sortable'  => true,
                        'filter'    => false,
                        'width'     => 90,
                    ));
            }
        }

        parent::_prepareColumns();
        return Mage_Adminhtml_Block_Widget_Grid::_prepareColumns();
    }
}