<?php

class Fooman_OrderManager_Model_Sales_Order_Shipment_Api extends Mage_Sales_Model_Order_Shipment_Api_V2
{
    public function getCarriers ($orderIncrementId)
    {
        $order = Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);

        if (!$order->getId()) {
            $this->_fault('order_not_exists');
        }
        //only show return carrier options if it hasn't been shipped yet
        if ($order->canShip()) {
            try {
                $carriers = $this->_getCarriers($order);
            }catch (Exception $e) {
                $carriers = array();
                $carrierModels = Mage::getSingleton('shipping/config')->getActiveCarriers();
                foreach ($carrierModels as $code => $carrier) {
                    if ($carrier->isTrackingAvailable()) {
                        $carriers[$code] = $carrier->getConfigData('title');
                    }
                }            
                
            }
            return $carriers;
        }
        return false;
    }

    public function sendEmail($shipmentIncrementId)
    {
        $shipment = Mage::getModel('sales/order_shipment')->loadByIncrementId($shipmentIncrementId);

        if (!$shipment->getId()) {
            $this->_fault('not_exists');
        }

        $shipment->setEmailSent(true);

        try {
            $shipment->sendEmail(true);
            $shipment->save();
        } catch (Mage_Core_Exception $e) {
            $this->_fault('data_invalid', $e->getMessage());
        }

        return true;
    } 

}
