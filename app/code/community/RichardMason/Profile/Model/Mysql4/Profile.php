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
class RichardMason_Profile_Model_Mysql4_Profile extends Mage_Core_Model_Mysql4_Abstract
{
	
    /**
     * Store model
     *
     * @var null|Mage_Core_Model_Store
     */
    protected $_store = null;

    
    public function _construct()
    {    
        // Note that the profiles_id refers to the key field in your database table.
        $this->_init('profile/profile', 'profile_id');
    }
    
    /**
     * Process page data before saving
     *
     * @param Mage_Core_Model_Abstract $object
     */
    protected function _beforeSave(Mage_Core_Model_Abstract $object)
    {
    	
        if (! $object->getId() && $object->getCreationTime() == "") {
            $object->setCreationTime(Mage::getSingleton('core/date')->gmtDate());
        }
        
        $format = Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT);
        if ($date = $object->getData('creation_time')) {
	        $object->setData('creation_time', Mage::app()->getLocale()->date($date, $format, null, false)
    		        ->toString(Varien_Date::DATETIME_INTERNAL_FORMAT)
        );
        }
        $object->setUpdateTime(Mage::getSingleton('core/date')->gmtDate());
        
        return $this;
    }

    /**
     *
     * @param Mage_Core_Model_Abstract $object
     */
    protected function _afterLoad(Mage_Core_Model_Abstract $object)
    {
        $select = $this->_getReadAdapter()->select()
            ->from($this->getTable('profile/profile_store'))
            ->where('profile_id = ?', $object->getId());

        if ($data = $this->_getReadAdapter()->fetchAll($select)) {
            $storesArray = array();
            foreach ($data as $row) {
                $storesArray[] = $row['store_id'];
            }
            $object->setData('store_id', $storesArray);
        }

        return parent::_afterLoad($object);
    }    
    
    /**
     * Assign page to store views
     *
     * @param Mage_Core_Model_Abstract $object
     */
    protected function _afterSave(Mage_Core_Model_Abstract $object)
    {
        $condition = $this->_getWriteAdapter()->quoteInto('profile_id = ?', $object->getId());
        $this->_getWriteAdapter()->delete($this->getTable('profile/profile_store'), $condition);

        foreach ((array)$object->getData('stores') as $store) {
            $storeArray = array();
            $storeArray['profile_id'] = $object->getId();
            $storeArray['store_id'] = $store;
            $this->_getWriteAdapter()->insert($this->getTable('profile/profile_store'), $storeArray);
        }

        return parent::_afterSave($object);
    }    
    
    /**
     * Retrieve select object for load object data
     *
     * @param string $field
     * @param mixed $value
     * @return Zend_Db_Select
     */
    protected function _getLoadSelect($field, $value, $object)
    {
        $select = parent::_getLoadSelect($field, $value, $object);

        if ($object->getStoreId()) {
            $select->join(
                        array('cps' => $this->getTable('profile/profile_store')),
                        $this->getMainTable().'.profile_id = `cps`.profile_id'
                    )
                    ->where('is_active=1 AND `cps`.store_id in (' . Mage_Core_Model_App::ADMIN_STORE_ID . ', ?) ', $object->getStoreId())
                    ->order('store_id DESC')
                    ->limit(1);
        }
        return $select;
    }
    
    /**
     * Check if page identifier exist for specific store
     * return page id if page exists
     *
     * @param   string $identifier
     * @param   int $storeId
     * @return  int
     */
    public function checkIdentifier($profile_id, $storeId)
    {
        $select = $this->_getReadAdapter()->select()->from(array('main_table'=>$this->getMainTable()), 'profile_id')
            ->join(
                array('cps' => $this->getTable('profile/profile_store')),
                'main_table.profile_id = `cps`.profile_id'
            )
            ->where('main_table.profile_id=?', $profile_id)
            ->where('main_table.is_active=1 AND `cps`.store_id in (' . Mage_Core_Model_App::ADMIN_STORE_ID . ', ?) ', $storeId)
            ->order('store_id DESC');

        return $this->_getReadAdapter()->fetchOne($select);
    }    
    
    /**
     * Get store ids to which specified item is assigned
     *
     * @param int $id
     * @return array
     */
    public function lookupStoreIds($id)
    {
        return $this->_getReadAdapter()->fetchCol($this->_getReadAdapter()->select()
            ->from($this->getTable('profile/profile_store'), 'store_id')
            ->where("{$this->getIdFieldName()} = ?", $id)
        );
    }

    /**
     * Set store model
     *
     * @param Mage_Core_Model_Store $store
     * @return Mage_Cms_Model_Mysql4_Page
     */
    public function setStore($store)
    {
        $this->_store = $store;
        return $this;
    }

    /**
     * Retrieve store model
     *
     * @return Mage_Core_Model_Store
     */
    public function getStore()
    {
        return Mage::app()->getStore($this->_store);
    }    
}