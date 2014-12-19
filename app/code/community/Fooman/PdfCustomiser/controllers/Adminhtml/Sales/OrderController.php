<?php

require_once BP.'/app/code/community/Fooman/EmailAttachments/controllers/Admin/OrderController.php';

class Fooman_PdfCustomiser_Adminhtml_Sales_OrderController extends Fooman_EmailAttachments_Admin_OrderController
{

    public function pdfinvoicesAction()
    {
        $orderIds = $this->getRequest()->getPost('order_ids');
        if(sizeof($orderIds)){
            $pdf = Mage::getModel('sales/order_pdf_invoice')->getPdf(null,$orderIds);
        } else {
            $this->_getSession()->addError($this->__('There are no printable documents related to selected orders'));
            $this->_redirect('*/*/');
        }
        $this->_redirect('*/*/');
    }

    public function pdfshipmentsAction(){
        $orderIds = $this->getRequest()->getPost('order_ids');
        if(sizeof($orderIds)){
            $pdf = Mage::getModel('sales/order_pdf_shipment')->getPdf(null,$orderIds);
        } else {
            $this->_getSession()->addError($this->__('There are no printable documents related to selected orders'));
            $this->_redirect('*/*/');
        }   
        $this->_redirect('*/*/');
    }

    public function pdfcreditmemosAction(){
        $orderIds = $this->getRequest()->getPost('order_ids');
        if(sizeof($orderIds)){
            $pdf = Mage::getModel('sales/order_pdf_creditmemo')->getPdf(null,$orderIds);
        } else {
            $this->_getSession()->addError($this->__('There are no printable documents related to selected orders'));
            $this->_redirect('*/*/');
        }
        $this->_redirect('*/*/');
    }


    public function pdfdocsAction(){
        $orderIds = $this->getRequest()->getPost('order_ids');
        if(sizeof($orderIds)){
            $pdf = Mage::getModel('sales/order_pdf_invoice')->getPdf(null,$orderIds,null,true);
            $pdf = Mage::getModel('sales/order_pdf_shipment')->getPdf(null,$orderIds,$pdf,true);
            $pdf = Mage::getModel('pdfcustomiser/order')->getPdf(null,$orderIds,$pdf,true);
            $pdf = Mage::getModel('sales/order_pdf_creditmemo')->getPdf(null,$orderIds,$pdf,false,'orderDocs_');
        } else {
            $this->_getSession()->addError($this->__('There are no printable documents related to selected orders'));
            $this->_redirect('*/*/');
        }
        $this->_redirect('*/*/');
    }

    public function pdfordersAction(){
        $orderIds = $this->getRequest()->getPost('order_ids');
        if(sizeof($orderIds)){
            $pdf = Mage::getModel('pdfcustomiser/order')->getPdf(null,$orderIds);
        } else {
            $this->_getSession()->addError($this->__('There are no printable documents related to selected orders'));
            $this->_redirect('*/*/');
        }
        $this->_redirect('*/*/');
    }

    public function pdfpickingAction(){
        $orderIds = $this->getRequest()->getPost('order_ids');
        if(sizeof($orderIds)){
            $pdf = Mage::getModel('pdfcustomiser/order')->getPicking(null,$orderIds);
        } else {
            $this->_getSession()->addError($this->__('There are no printable documents related to selected orders'));
            $this->_redirect('*/*/');
        }
        $this->_redirect('*/*/');
    }

    public function printAction()
    {
        if ($orderId = $this->getRequest()->getParam('order_id')) {
            if ($order = Mage::getModel('sales/order')->load($orderId)) {
                $pdf = Mage::getModel('pdfcustomiser/order')->getPdf(null,array($orderId));
            }
        }
        else {
            $this->_forward('noRoute');
        }
    }

}