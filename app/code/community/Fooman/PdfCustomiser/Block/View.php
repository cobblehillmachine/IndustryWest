<?php
class Fooman_PdfCustomiser_Block_View extends Fooman_EmailAttachments_Block_View {

    public function getPrintUrl()
    {
        return $this->getUrl('pdfcustomiser/adminhtml_sales_order/print', array(
            'order_id' => $this->getOrder()->getId()
        ));
    }
}