<?php

class Fooman_OrderManager_Block_Adminhtml_Widget_Grid_Column_Renderer_TrackingNumberInput extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Input
{
    public function render(Varien_Object $row)
    {
        if ($this->_canShip($row)) {
            $colId = $this->getColumn()->getName() ? $this->getColumn()->getName() : $this->getColumn()->getId();
            $html = '<input name="'.$colId.'-'.$row->getId().'" rel="'.$row->getId().'" class="input-text '.$colId.'"
                            value="' . $row->getData($this->getColumn()->getIndex()) . '" />';
        } else {
            $trackingNumbers = array();
            $collection = Mage::getModel('sales/order_shipment_track')
                ->getCollection()
                ->setOrderFilter($row->getId());
            if (version_compare(Mage::getVersion(),'1.7.0.0','>=')){
                //enterprise editions
                if(version_compare(Mage::getVersion(),'1.11.0.0','>=')){
                    $collection->addAttributeToSelect('track_number');
                    $new = true;
                }
                else {
                    $collection->addAttributeToSelect('number'); 
                    $new = false;
                }
            } else {
                //community editions
                if(version_compare(Mage::getVersion(),'1.6.0.0','>=')){
                    $collection->addAttributeToSelect('track_number');
                    $new = true;
                }
                else {
                    $collection->addAttributeToSelect('number'); 
                    $new = false;
                }
            }
            if($collection){
                $maxLength = Mage::helper('ordermanager')->getStoreConfig('settings/trackingnumbercharacterlengthtodisplay');
                foreach ($collection as $track) {
                    $number = $new ? $track->getTrackNumber() : $track->getNumber();
                    $trackingNumbers[] = strlen($number)>$maxLength?  substr($number,
                                    0, ($maxLength/2)-2).'...'.  substr($number,
                                    -($maxLength/2)):$number;
                }
            }
            $html = '<small>'.implode(', ', $trackingNumbers).'</small>';
        }

        return $html;
    }

    private function _canShip(Varien_Object $row)
    {
        $order = Mage::getModel('sales/order')->load($row->getId());
        if (!$order->getId()) {
            return false;
        }
        return $order->canShip();

    }

    public function getFilter()
    {
        return false;
    }

}
