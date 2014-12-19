<?php
/**
 * Mason Web Development
 *
 * NOTICE OF LICENSE
 * This source file is subject to the Open Software License (OSL 3.0)
 * Part of the code of this file was obtained from:
 * -category    Mage
 * -package     Mage_Adminhtml
 * -copyright   Copyright (c) 2009 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * -license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *
 * @category    RichardMason
 * @package     RichardMason_Profile
 * @copyright   Copyright (c) 2009 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class RichardMason_Profile_Block_Adminhtml_Profile_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('profileBlockGrid');
        $this->setDefaultSort('profile_id');
        $this->setDefaultDir('ASC');
    }

    protected function _prepareCollection()
    {
		$collection = Mage::getModel('profile/profile')->getCollection();
		$collection->addFieldToFilter('category_id', Mage::registry('profile_category_id'));
        /* @var $collection Mage_Cms_Model_Mysql4_Block_Collection */
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $baseUrl = $this->getUrl();

        $this->addColumn('content_heading', array(
            'header'    => Mage::helper('profile')->getText(Mage::registry('profile_category_id'),'content_heading'),
            'align'     => 'left',
            'index'     => 'content_heading'
        ));

        /*
        if(Mage::registry('profile_category_id') != RichardMason_Profile_Model_Profile::CATEGORY_RELEASES) {
	        $this->addColumn('content', array(
	            'header'    => Mage::helper('profile')->getText(Mage::registry('profile_category_id'),'content'),
	            'align'     => 'left',
	            'index'     => 'content'
	        ));
        }
		*/
        
        if (!Mage::app()->isSingleStoreMode()) {
            $this->addColumn('store_id', array(
                'header'        => Mage::helper('profile')->__('Store View'),
                'index'         => 'store_id',
                'type'          => 'store',
                'store_all'     => true,
                'store_view'    => true,
                'sortable'      => false,
                'filter_condition_callback'
                                => array($this, '_filterStoreCondition'),
            ));
        }

        $this->addColumn('is_active', array(
            'header'    => Mage::helper('profile')->__('Status'),
            'index'     => 'is_active',
            'type'      => 'options',
            'options'   => array(
                0 => Mage::helper('profile')->__('Disabled'),
                1 => Mage::helper('profile')->__('Enabled')
            ),
        ));

        $this->addColumn('creation_time', array(
            'header'    => Mage::helper('profile')->__('Date Created'),
            'index'     => 'creation_time',
            'type'      => 'datetime',
        ));

        $this->addColumn('update_time', array(
            'header'    => Mage::helper('profile')->__('Last Modified'),
            'index'     => 'update_time',
            'type'      => 'datetime',
        ));

        return parent::_prepareColumns();
    }

    protected function _afterLoadCollection()
    {
        $this->getCollection()->walk('afterLoad');
        parent::_afterLoadCollection();
    }

    protected function _filterStoreCondition($collection, $column)
    {
        if (!$value = $column->getFilter()->getValue()) {
            return;
        }

        $this->getCollection()->addStoreFilter($value);
    }

    /**
     * Row click url
     *
     * @return string
     */
    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array('profile_id' => $row->getId(), 'category_id' => Mage::registry('profile_category_id') ));
    }
	
}