<?php

abstract class Fooman_PdfCustomiser_Block_Pdf_Abstract extends Mage_Core_Block_Template
{

    private $_pdfObject = null;
    public function setPdf (Fooman_PdfCustomiser_Model_Mypdf $pdf)
    {
        $this->_pdfObject = $pdf;
        return $this;
    }

    /**
     *
     * @return Fooman_PdfCustomiser_Model_Mypdf 
     */
    public function getPdf ()
    {
        return $this->_pdfObject;
    }
    
    private $_pdfHelper = null;
    public function setPdfHelper (Fooman_PdfCustomiser_Helper_Pdf $helper)
    {
        $this->_pdfHelper = $helper;
        return $this;        
    }

    /**
     *
     * @return Fooman_PdfCustomiser_Helper_Pdf
     */
    public function getPdfHelper ()
    {
        return $this->_pdfHelper;
    }
  
    private $_totals = null;
    public function setTotals (array $totals)
    {
        $this->_totals = $totals;
        return $this;        
    }

    /**
     *
     * @return array()
     */
    public function getTotals ()
    {
        return $this->_totals;
    }    
   
    
}