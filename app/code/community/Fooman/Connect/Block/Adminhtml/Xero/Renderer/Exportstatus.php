<?php

/*
 * @author     Kristof Ringleff
 * @package    Fooman_Connect
 * @copyright  Copyright (c) 2010 Fooman Limited (http://www.fooman.co.nz)
 */

class Fooman_Connect_Block_Adminhtml_Xero_Renderer_Exportstatus
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract {

    public function render(Varien_Object $row) {
        $status = $row->load($row->getId())->getXeroExportStatus();
        switch($status) {
            case Fooman_Connect_Model_XeroOauth::CA_XERO_STATUS_NOT_EXPORTED:
                return Mage::helper('foomanconnect')->__('Not Exported');
                break;
            case Fooman_Connect_Model_XeroOauth::CA_XERO_STATUS_ORDER_EXPORTED:
                return Mage::helper('foomanconnect')->__('Exported');
                break;
            case Fooman_Connect_Model_XeroOauth::CA_XERO_STATUS_ORDER_ATTEMPTED_BUT_FAILED:
                return Mage::helper('foomanconnect')->__('Attempted but failed');
                break;
            case Fooman_Connect_Model_XeroOauth::CA_XERO_STATUS_ORDER_WONT_EXPORT:
                return Mage::helper('foomanconnect')->__('Export not needed');
                break;

            default:
                return Mage::helper('foomanconnect')->__('Not Exported');

        }
    }
}