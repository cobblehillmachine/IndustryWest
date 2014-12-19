<?php

class Fooman_PdfCustomiser_Helper_Pdf_Order extends Fooman_PdfCustomiser_Helper_Pdf
{
    /**
     * return column order and width for order
     * either use default or json_decode value from Advanced field in the back-end
     *
     * @param void
     *
     * @return array
     * @access public
     */
    public function getColumnOrderAndWidth()
    {
        if (!isset($this->_parameters[$this->getStoreId()]['ordercolumnwidths'])) {
            if (Mage::getStoreConfig('sales_pdf/order/ordercolumnwidths', $this->getStoreId())) {
                $this->_parameters[$this->getStoreId()]['ordercolumnwidths'] = json_decode(Mage::getStoreConfig('sales_pdf/order/ordercolumnwidths', $this->getStoreId()), true);
            } else {
                $this->_parameters[$this->getStoreId()]['ordercolumnwidths'] = $this->getDefaultColumnOrderAndWidth();
            }
        }
        return $this->_parameters[$this->getStoreId()]['ordercolumnwidths'];
    }

    /**
     * get main heading for invoice title ie ORDER CONFIRMATION
     *
     * @param void
     *
     * @return string
     * @access public
     */
    public function getPdfTitle()
    {
        if (!isset($this->_parameters[$this->getStoreId()]['ordertitle'])) {
            $this->_parameters[$this->getStoreId()]['ordertitle'] = Mage::getStoreConfig('sales_pdf/order/ordertitle', $this->getStoreId());
        }
        return $this->_parameters[$this->getStoreId()]['ordertitle'];
    }

    /**
     * return which addresses to display
     *
     * @param void
     *
     * @return  string billing/shipping/both
     * @access public
     */
    public function getPdfAddresses()
    {
        if (!isset($this->_parameters[$this->getStoreId()]['orderaddresses'])) {
            $this->_parameters[$this->getStoreId()]['orderaddresses'] = Mage::getStoreConfig('sales_pdf/order/orderaddresses', $this->getStoreId());
        }
        return $this->_parameters[$this->getStoreId()]['orderaddresses'];
    }

    /**
     * custom text for underneath order
     *
     * @param void
     *
     * @return  string
     * @access public
     */
    public function getPdfCustom()
    {
        if (!isset($this->_parameters[$this->getStoreId()]['ordercustom'])) {
            $this->_parameters[$this->getStoreId()]['ordercustom'] = Mage::getStoreConfig('sales_pdf/order/ordercustom', $this->getStoreId());
        }
        return $this->_parameters[$this->getStoreId()]['ordercustom'];
    }

    /**
     * returns the text preceding the order increment id
     *
     * @param void
     *
     * @return string
     * @access public
     */
    public function getNumberText()
    {
        return Mage::helper('sales')->__('Order #');
    }

    /**
     * return which columns the user chosen to display on the invoice
     *
     * @param void
     *
     * @return string
     * @access public
     */
    public function getPdfColumns()
    {
        if (!isset($this->_parameters[$this->getStoreId()]['ordercolumns'])) {
            $this->_parameters[$this->getStoreId()]['ordercolumns'] = Mage::getStoreConfig('sales_pdf/order/ordercolumns', $this->getStoreId());
        }
        return $this->_parameters[$this->getStoreId()]['ordercolumns'];
    }

}