<?php

class Nick_Ordercorrection_Model_System_Config_Source_Roles extends Mage_Eav_Model_Entity_Attribute_Source_Abstract
{
    protected $_options;

    public function toOptionArray($isMultiselect=false)
    {
        if (!$this->_options)
        {
            $this->getAllOptions();
        	
        }
        return $this->_options;
    }
    
    public function getAllOptions()
    {
        if (!$this->_options) {
        	$this->_options = array();
        	
			$collection = Mage::getModel('admin/roles')->getCollection();
			foreach($collection as $item)
			{

				
	        	$this->_options[] = array('value' => $item->getRoleId() ,'label' => $item->getRoleName());			
			}
        }
        return $this->_options;
    }
    
   
}