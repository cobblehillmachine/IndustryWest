<?php
class Fooman_PdfCustomiser_Helper_Data extends Fooman_EmailAttachments_Helper_Data
{
    /**
     * convert pdf object to string and attach to mail object
     *
     * @param        $pdf
     * @param        $mailObj
     * @param string $name
     *
     * @return mixed
     * @access public
     */
    public function addAttachment($pdf, $mailObj, $name = "order.pdf")
    {
        try {
            $file = $pdf->Output(Mage::getSingleton('core/date')->date('Y-m-d_H-i-s') . '.pdf', 'S');
            $mailObj->getMail()->createAttachment($file, 'application/pdf', Zend_Mime::DISPOSITION_ATTACHMENT, Zend_Mime::ENCODING_BASE64, $name . '.pdf');
        } catch (Exception $e) {
            Mage::log(Mage::helper('pdfcustomiser')->__('Caught error while attaching pdf: %s'), $e->getMessage());
        }
        return $mailObj;
    }

}