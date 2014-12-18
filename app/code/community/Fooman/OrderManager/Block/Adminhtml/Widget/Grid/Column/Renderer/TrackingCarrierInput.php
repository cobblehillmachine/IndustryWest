<?php

class Fooman_OrderManager_Block_Adminhtml_Widget_Grid_Column_Renderer_TrackingCarrierInput extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Select
{
    public function render(Varien_Object $row)
    {
        $trackingCarriers = $this->getOptions($row);
        if ($trackingCarriers) {
            $colId = $this->getColumn()->getName() ? $this->getColumn()->getName() : $this->getColumn()->getId();
            $html = '<select style="width:60px;" name="'.$colId.'-'.$row->getId().'" rel="'.$row->getId().'" class="'.$colId.'" >';
            foreach ( $trackingCarriers as $val => $label){
                $selected = ( ($val == $this->getPreSelectCarrier()) ? ' selected="selected"' : '' );
                $html.= '<option '. $selected.' value="' . $val . '">' . $label . '</option>';
            }
            $html.='</select>';
        } else {
            $collection = Mage::getModel('sales/order_shipment_track')
                ->getCollection()
                ->addAttributeToSelect('title')
                ->setOrderFilter($row->getId());
            $carriers = array();
            foreach ($collection as $track) {
                $carriers[] = $track->getTitle();
            }
            $html = implode(' ,', $carriers);
        }

        return $html;
    }

    public function getOptions($row)
    {
        $carriers = Mage::getModel('ordermanager/sales_order_shipment_api')->getCarriers($row->getIncrementId());
        if (isset ($carriers['custom']) && $customTitle = Mage::helper('ordermanager')->getStoreConfig('settings/customtitle')) {
            $carriers['custom'] = $customTitle;
        } 
        return $carriers;
    }

    public function getPreSelectCarrier()
    {
        return Mage::helper('ordermanager')->getStoreConfig('settings/preselectedcarrier');

    }

    public function getFilter()
    {
        return false;
    }


}