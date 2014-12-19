<?php

class Fooman_PdfCustomiser_Model_Shipment extends Fooman_PdfCustomiser_Model_Abstract
{
    /**
    * Creates PDF using the tcpdf library from array of shipments or orderIds
    * @param array $shipmentsGiven, $orderIds
    * @access public
    */
    public function getPdf($shipmentsGiven = array(),$orderIds = array(),$pdf = null, $suppressOutput = false)
    {

        if(empty($pdf) && empty($shipmentsGiven) && empty($orderIds)){
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('There are no printable documents related to selected orders'));
                return false;
        }

        //we will be working through an array of orderIds later - fill it up if only invoices is given
        if (!empty($shipmentsGiven)) {
            $allShipments = array();
            foreach ($shipmentsGiven as $shipmentGiven) {
                $currentOrderId = $shipmentGiven->getOrder()->getId();
                $orderIds[$currentOrderId] = $currentOrderId;
                $allShipments[$currentOrderId][] = $shipmentGiven;
            }
        }

        $this->_beforeGetPdf();

        $storeId = $order = Mage::getModel('sales/order')->load($orderIds[0])->getStoreId();

        //work with a new pdf or add to existing one
        if(empty($pdf)){
            $pdf = Mage::getModel('pdfcustomiser/mypdf', array('P', 'mm',  Mage::getStoreConfig('sales_pdf/all/allpagesize',$storeId), true, 'UTF-8', false));
        }
        $shipmentsCsv=array();
        foreach ($orderIds as $orderId) {
            $order = Mage::getModel('sales/order')->load($orderId);
            $printOrderAsPackingSlip =  Mage::getStoreConfig('sales_pdf/shipment/shipmentuseorder', $storeId);
            if(!empty($shipmentsGiven)) {
                $shipments = $allShipments[$orderId];
            }elseif ($printOrderAsPackingSlip) {
                $shipments = Mage::getResourceModel('sales/order_collection')->addAttributeToFilter('entity_id',$orderId)->load();
                //$shipments = array($order->prepareShipment());
            }
            else {
                $shipments = Mage::getResourceModel('sales/order_shipment_collection')
                    ->addAttributeToSelect('*')
                    ->setOrderFilter($orderId)
                    ->load();
            }

            if (count($shipments) > 0 || $shipments->getSize() > 0) {
                foreach ($shipments as $shipment) {
                    // create new Shipment helper
                    $shipmentHelper = Mage::helper('pdfcustomiser/pdf_shipment');
                    $shipment->load($shipment->getId());
                    $storeId = $shipment->getStoreId();
                    if ($storeId) {
                        $appEmulation = Mage::getSingleton('core/app_emulation');
                        $initial = $appEmulation->startEnvironmentEmulation($storeId,Mage_Core_Model_App_Area::AREA_FRONTEND,true);
                    }

                    $shipmentHelper->setStoreId($storeId);
                    $shipmentHelper->setSalesObject($shipment);
                    $pdf->setStoreId($storeId);
                    $pdf->setPdfHelper($shipmentHelper);
                    // set standard pdf info
                    $pdf->SetStandard($shipmentHelper);
                    if ($shipmentHelper->getPdfIntegratedLabels()){
                        $pdf->SetAutoPageBreak(true, 85);
                    }

                    // add a new page
                    $pdf->AddPage();
                    $pdf->setIncrementId($shipment->getIncrementId());

                    // Print the logo
                    $pdf->printHeader($shipmentHelper, $shipmentHelper->getPdfTitle());

                    // Prepare Line Items
                    $pdf->prepareLineItems($shipmentHelper, $shipment, $order);

                    // Prepare Top
                    $top = Mage::app()->getLayout()->createBlock('pdfcustomiser/pdf_block')->setPdfHelper($shipmentHelper)->setTemplate('fooman/pdfcustomiser/shipment/top.phtml')->toHtml();

                    $processor = Mage::helper('cms')->getBlockTemplateProcessor();
                    $processor->setVariables(
                            array(
                                'order' => $order,
                                'sales_object' => $shipment,
                                'billing_address'=> $pdf->PrepareCustomerAddress($shipmentHelper, $order, 'billing'),
                                'shipping_address'=> $pdf->PrepareCustomerAddress($shipmentHelper, $order, 'shipping'),
                                'payment'=> $pdf->PreparePayment ($shipmentHelper, $order, $shipment),
                                'shipping'=> $pdf->PrepareShipping ($shipmentHelper, $order, $shipment)
                            )
                    );
                    $top = $processor->filter($top);

                    //Prepare Totals
                    $totals = $this->PrepareTotals($shipmentHelper, $shipment);

                    //Prepare Bottom
                    $bottom = Mage::app()->getLayout()->createBlock('pdfcustomiser/pdf_block')
                            ->setPdf($pdf)
                            ->setPdfHelper($shipmentHelper)
                            ->setTotals($totals)
                            ->setTemplate('fooman/pdfcustomiser/shipment/bottom.phtml')
                            ->toHtml();
                    $processor->setVariables(
                            array(
                                'order' => $order,
                                'sales_object' => $shipment
                            )
                    );
                    $bottom = $processor->filter($bottom);

                    //Prepare Items
                    $items = Mage::app()->getLayout()->createBlock('pdfcustomiser/pdf_items')->setPdf($pdf)->setPdfHelper($shipmentHelper)->setTemplate('fooman/pdfcustomiser/items.phtml')->toHtml();

                    //Put it all together
                    $pdf->writeHTML($top);
                    $pdf->SetFont($shipmentHelper->getPdfFont(), '', $shipmentHelper->getPdfFontsize('small'));
                    $pdf->writeHTML($items, true, false, false, false, '');
                    $pdf->SetFont($shipmentHelper->getPdfFont(), '', $shipmentHelper->getPdfFontsize());
                    //reset Margins in case there was a page break
                    $pdf->setMargins($shipmentHelper->getPdfMargins('sides'), $shipmentHelper->getPdfMargins('top'));
                    $pdf->writeHTML($bottom);

                    //print extra addresses for peel off labels
                    if ($shipmentHelper->getPdfIntegratedLabels()) {
                        $pdf->OutputCustomerAddresses($shipmentHelper,$order, $shipmentHelper->getPdfIntegratedLabels());
                    }

                    if ($shipment->getStoreId()) {
                        $appEmulation->stopEnvironmentEmulation($initial);
                    }
                    $pdf->setPdfAnyOutput(true);
                }
            }
        }

        //output PDF document
        if(!$suppressOutput) {
            if($pdf->getPdfAnyOutput()) {
                // reset pointer to the last page
                $pdf->lastPage();
                $pdf->Output(
                    preg_replace("/[^a-zA-Z]/", "", $shipmentHelper->getPdfTitle()) . '_' . Mage::getSingleton('core/date')->date('Y-m-d_H-i-s') . '.pdf',
                    $shipmentHelper->getNewWindow()
                );
                exit;
            } else {
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('There are no printable documents related to selected orders'));
            }
        }

        $this->_afterGetPdf();
        return $pdf;
    }

}
