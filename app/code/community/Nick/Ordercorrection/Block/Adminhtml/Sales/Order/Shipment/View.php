<?php

class Nick_Ordercorrection_Block_Adminhtml_Sales_Order_Shipment_View extends Mage_Adminhtml_Block_Sales_Order_Shipment_View
{
	 public function __construct() {
     
	 	parent::__construct();
		$order = $this->getShipment()->getId();
		
		if(Mage::helper('ordercorrection/data')->checkRole()) {	
			
			if (Mage::getStoreConfig('ordercorrection/general/enable_shipment')) {
				
				$message = Mage::helper('sales')->__('Are you sure you want to delete ALL shipments for this order?');
				$this->_addButton('deleteall', array(
					'label'     => Mage::helper('sales')->__('Delete ALL Shipments'),
					'class'     => 'delete',
					'onclick'   => 'deleteConfirm(\''.$message.'\', \'' . $this->getDeleteShipmentsUrl() . '\')',
					)
				);
					
				$message = Mage::helper('sales')->__('Are you sure you want to delete THIS shipment for this order?');
				$this->_addButton('delete', array(
					'label'     => Mage::helper('sales')->__('Delete Shipment'),
					'class'     => 'delete',
					'onclick'   => 'deleteConfirm(\''.$message.'\', \'' . $this->getDeleteShipmentUrl() . '\')',
					)
				);
			}
		}
	}
	
	public function getDeleteShipmentsUrl()
    {
        return $this->getUrl('ordercorrection/delete/deleteshipments/action', array('oid'=>Mage::registry('current_shipment')->getOrderId()));
    }
	
	public function getDeleteShipmentUrl()
    {
        return $this->getUrl('ordercorrection/delete/deleteshipment/action', array('oid'=>Mage::registry('current_shipment')->getOrderId(), 'iid'=>Mage::registry('current_shipment')->getId()));
    }


}