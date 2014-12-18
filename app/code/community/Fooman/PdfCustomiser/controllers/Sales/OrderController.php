<?php

require_once BP.'/app/code/core/Mage/Sales/controllers/OrderController.php';


class Fooman_PdfCustomiser_Sales_OrderController extends Mage_Sales_OrderController {

    public function printAction() {
        if (!$this->_loadValidOrder()) {
            return;
        }
        $order = Mage::registry('current_order');
        $pdf = Mage::getModel('pdfcustomiser/order')->getPdf(null,array($order->getId()));

    }

    public function printInvoiceAction() {
        $invoiceId = (int) $this->getRequest()->getParam('invoice_id');
        if ($invoiceId) {
            $invoice = Mage::getModel('sales/order_invoice')->load($invoiceId);
            $order = $invoice->getOrder();
            if ($this->_canViewOrder($order)) {
                $pdf = Mage::getModel('pdfcustomiser/invoice')->getPdf(array($invoice));
            }
            else {
                $this->_redirect('*/*/history');
            }
        }
        else {
            $orderId = (int) $this->getRequest()->getParam('order_id');
            $order = Mage::getModel('sales/order')->load($orderId);
            if ($this->_canViewOrder($order)) {
                $pdf = Mage::getModel('sales/order_pdf_invoice')->getPdf(null,array($orderId));
            }
            else {
                $this->_redirect('*/*/history');
            }
        }
    }

    public function printShipmentAction() {
        $shipmentId = (int) $this->getRequest()->getParam('shipment_id');
        if ($shipmentId) {
            $shipment = Mage::getModel('sales/order_shipment')->load($shipmentId);
            $order = $shipment->getOrder();
            if ($this->_canViewOrder($order)) {
                $pdf = Mage::getModel('pdfcustomiser/shipment')->getPdf(array($shipment));
            }
            else {
                $this->_redirect('*/*/history');
            }
        }
        else {
            $orderId = (int) $this->getRequest()->getParam('order_id');
            $order = Mage::getModel('sales/order')->load($orderId);
            if ($this->_canViewOrder($order)) {
                $pdf = Mage::getModel('sales/order_pdf_shipment')->getPdf(null,array($orderId));
            }
            else {
                $this->_redirect('*/*/history');
            }
        }
    }

    public function printCreditmemoAction() {
        $creditmemoId = (int) $this->getRequest()->getParam('creditmemo_id');
        if ($creditmemoId) {
            $creditmemo = Mage::getModel('sales/order_creditmemo')->load($creditmemoId);
            $order = $creditmemo->getOrder();
            if ($this->_canViewOrder($order)) {
                $pdf = Mage::getModel('pdfcustomiser/creditmemo')->getPdf(array($creditmemo));
            }
            else {
                $this->_redirect('*/*/history');
            }
        }
        else {
            $orderId = (int) $this->getRequest()->getParam('order_id');
            $order = Mage::getModel('sales/order')->load($orderId);
            if ($this->_canViewOrder($order)) {
                $pdf = Mage::getModel('sales/order_pdf_creditmemo')->getPdf(null,array($orderId));
            }
            else {
                $this->_redirect('*/*/history');
            }
        }
    }

}