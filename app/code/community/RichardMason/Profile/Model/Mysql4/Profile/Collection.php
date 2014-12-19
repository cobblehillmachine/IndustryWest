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
class RichardMason_Profile_Model_Mysql4_Profile_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
	
    protected $_previewFlag;
	
    public function _construct()
    {
        parent::_construct();
        $this->_init('profile/profile');
        
        $this->_map['fields']['profile_id'] = 'main_table.profile_id';
    }
    
    public function setFirstStoreFlag($flag = false)
    {
        $this->_previewFlag = $flag;
        return $this;
    }

    protected function _afterLoad()
    {
        if ($this->_previewFlag) {
            $items = $this->getColumnValues('profile_id');
            if (count($items)) {
                $select = $this->getConnection()->select()
                        ->from($this->getTable('profile/profile_store'))
                        ->where($this->getTable('profile/profile_store').'.profile_id IN (?)', $items);
                if ($result = $this->getConnection()->fetchPairs($select)) {
                    foreach ($this as $item) {
                        if (!isset($result[$item->getData('profile_id')])) {
                            continue;
                        }
                        if ($result[$item->getData('profile_id')] == 0) {
                            $stores = Mage::app()->getStores(false, true);
                            $storeId = current($stores)->getId();
                            $storeCode = key($stores);
                        } else {
                            $storeId = $result[$item->getData('profile_id')];
                            $storeCode = Mage::app()->getStore($storeId)->getCode();
                        }
                        $item->setData('_first_store_id', $storeId);
                        $item->setData('store_code', $storeCode);
                    }
                }
            }
        }

        parent::_afterLoad();
    }
    
    
    /**
     * Add Filter by store
     *
     * @param int|Mage_Core_Model_Store $store
     * @return Mage_Cms_Model_Mysql4_Page_Collection
     */
    public function addStoreFilter($store, $withAdmin = true)
    {
        if (!$this->getFlag('store_filter_added')) {
            if ($store instanceof Mage_Core_Model_Store) {
                $store = array($store->getId());
            }

            $this->getSelect()->join(
                array('store_table' => $this->getTable('profile/profile_store')),
                'main_table.profile_id = store_table.profile_id',
                array()
            )
            ->where('store_table.store_id in (?)', ($withAdmin ? array(0, $store) : $store))
            ->group('main_table.profile_id');

            $this->setFlag('store_filter_added', true);
        }

        return $this;
    }
        
}