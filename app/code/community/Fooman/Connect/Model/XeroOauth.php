<?php

/*
 * @author     Kristof Ringleff
 * @package    Fooman_Connect
 * @copyright  Copyright (c) 2010 Fooman Limited (http://www.fooman.co.nz)
 */

class Fooman_Connect_Model_XeroOauth {

    const URL_ROOT = 'https://api.xero.com';
    const URL_LICENSE = 'https://secure.fooman.co.nz/xero/';
    const API_VERSION = 'version/2';

    //Entry points for Fooman Connect
    const CA_INVOICE_PATH ="/api.xro/2.0/Invoice";
    const CA_INVOICES_PATH ="/api.xro/2.0/Invoices";
    const CA_CREDITNOTE_PATH = "/api.xro/2.0/CreditNotes";
    const CA_CONTACTS_PATH = "/api.xro/2.0/Contacts";
    const CA_TRACKING_PATH ="/api.xro/2.0/TrackingCategories";
    const CA_ACCOUNTS_PATH ="/api.xro/2.0/Accounts";
    const CA_TAXRATES_PATH ="/api.xro/2.0/TaxRates";
    const CA_ITEMS ="/api.xro/2.0/Items";
    const CA_XERO_INVOICE_LINK = "https://go.xero.com/AccountsReceivable/View.aspx?InvoiceID=";

    const CA_XERO_ROUNDING_ACCT = "860";

    const CA_XERO_STATUS_NOT_EXPORTED ='0';
    const CA_XERO_STATUS_ORDER_EXPORTED ='1';
    const CA_XERO_STATUS_ORDER_ATTEMPTED_BUT_FAILED ='2';
    const CA_XERO_STATUS_ORDER_WONT_EXPORT ='3';

    const CA_LOG_FILENAME = "xero.log";

    const CA_CRON_PROCESS_AT_ONCE = 10;

    private $_xeroAccessToken = '';

    private $_xeroTaxRatesDownloaded = array();

    public $xeroVersionDefaults =
        array('nz'=> array('code'=>'nz','name'=>'New Zealand',
                           'taxrates'=>array('IN'=>array(12.5000=>'INPUT',15.0000=>'INPUT2','default'=>'INPUT2'),
                                             'OUT'=>array(12.5000=>'OUTPUT',15.0000=>'OUTPUT2','default'=>'OUTPUT2')
                           )),
              'uk'=> array('code'=>'uk','name'=>'United Kingdom',
                           'taxrates'=>array('IN'=>array(20.0000=>'INPUT2',17.5000=>'INPUT',15.0000=>'SRINPUT',5.0000=>'RRINPUT','default'=>'INPUT2'),
                                             'OUT'=>array(20.0000=>'OUTPUT2',17.5000=>'OUTPUT',15.0000=>'SROUTPUT',5.0000=>'RROUTPUT','default'=>'OUTPUT2')
                           )),
              'global'=> array('code'=>'global','name'=>'Global',
                               'taxrates'=>array('IN'=>array(0.0000=>'INPUT','default'=>'INPUT'),
                                                 'OUT'=>array(0.0000=>'OUTPUT','default'=>'OUTPUT')
                               )),
              'aus'=> array('code'=>'aus','name'=>'Australia',
                            'taxrates'=>array('IN'=>array(10.0000=>'INPUT','default'=>'INPUT'),
                                              'OUT'=>array(10.0000=>'OUTPUT','default'=>'OUTPUT')
                            ))

        );

    /**
     * return the current session of the admin user
     *
     * @param  void
     * @return Mage_Adminhtml_Model_Session
     */
    public function getSession() {
        $_session = Mage::getSingleton('adminhtml/session');
        return $_session;
    }

    /**
     * Return current Oauth Token for accessing remote service
     *
     * @param  void
     * @return Zend_Oauth_Token_Access
     */
    public function getXeroAccessToken() {
        return $this->_xeroAccessToken;
    }


    /**
     * Initialise a consumer for use with Xero's Oauth
     *
     * @param   void
     * @return  Zend_Oauth_Consumer
     */

    public function initConsumer() {
        try{
            $this->_xeroAccessToken = '';

            $configuration =  $this->getConfiguration();
            $oauthConfiguration = new Zend_Oauth_Config($configuration);
            $consumer = new Zend_Oauth_Consumer($configuration);
            $xeroAccessToken= new Zend_Oauth_Token_Access();
            $xeroAccessToken->setToken($configuration['consumerKey']);
            $xeroAccessToken->setTokenSecret($configuration['consumerSecret']);
            //$xeroAccessToken = new Zend_Oauth_Http_AccessToken($consumer);
            $this->_xeroAccessToken = $xeroAccessToken;
            return $consumer;
        }catch (Exception $e){
            $this->getSession()->addError(Mage::helper('foomanconnect')->__('Oauth error: %s'),$e->getMessage());
            return false;
        }
    }

    /**
     * Get configuration settings to work with Xero's Oauth
     *
     * @param   void
     * @return  array
     */
    public function getConfiguration() {
        try {
            $rsaPrivateKey = new Zend_Crypt_Rsa_Key_Private(Mage::helper('core')->decrypt(Mage::helper('foomanconnect')->getMageStoreConfig('privatekey')),Mage::helper('core')->decrypt(Mage::helper('foomanconnect')->getMageStoreConfig('privatekeypassword')));
        }catch (Exception $e) {
            $this->getSession()->addError(Mage::helper('foomanconnect')->__('Private Key error: %s'.$e->getMessage().openssl_error_string()));
            throw new Exception(Mage::helper('foomanconnect')->__('Private Key error: %s'.$e->getMessage().openssl_error_string()));
        }
        return array(
            'useragent'=>'Fooman Magento',
            'siteUrl' => self::URL_ROOT,
            'signatureMethod' => 'RSA-SHA1',
            'consumerKey' => Mage::helper('core')->decrypt(Mage::helper('foomanconnect')->getMageStoreConfig('consumerkey')),
            'consumerSecret' => Mage::helper('core')->decrypt(Mage::helper('foomanconnect')->getMageStoreConfig('consumersecret')),
            'requestTokenUrl'=>self::URL_ROOT.'/oauth/RequestToken',
            'accessTokenUrl'=>self::URL_ROOT.'/oauth/AccessToken',
            'authorizeUrl'=>self::URL_ROOT.'/oauth/Authorize',
            'rsaPrivateKey'=> $rsaPrivateKey
        );
    }

    /**
     * check if configuration parameters have been entered
     * minimum required Consumer Key, Consumer Secret and Private Key
     *
     * @return bool
     */
    public function isConfigured() {
        $consumerkey = Mage::helper('core')->decrypt(Mage::helper('foomanconnect')->getMageStoreConfig('consumerkey'));
        $consumersecret = Mage::helper('core')->decrypt(Mage::helper('foomanconnect')->getMageStoreConfig('consumersecret'));
        $privatekey = Mage::helper('core')->decrypt(Mage::helper('foomanconnect')->getMageStoreConfig('privatekey'));
        return (!empty($consumerkey) && !empty($consumersecret)&& !empty($privatekey));
    }

    /**
     * error checking of response returned by server
     * turn response into SimpleXMLElement
     *
     * @param Zend_Http_Response $response
     * @return SimpleXMLElement $resultXml
     */
    public function handleResponse($response=null) {
        if ($response===null) {
            throw new Exception("Empty Response. Please check your settings.");
        }
        if (!$response instanceof Zend_Http_Response) {
            throw new Exception("Wrong Response. Please check your settings.");
        }
        $responseBody = $response->getBody();
        if (!$responseBody) {
            throw new Exception("Please use a valid ApiKey and Save Config");
        }
        if(strpos($responseBody,'token_expired') > 0 ) {
            $this->getSession()->unsXeroAccessToken();
            $this->getSession()->addError(Mage::helper('foomanconnect')->__('Your access to Xero has expired and you were redirected to reauthorize access. Please repeat your last action.'));
            $consumer = $this->initConsumer();
        }
        elseif(strpos($responseBody,'oauth_problem') === 0 ) {
            $this->getSession()->unsXeroAccessToken();
            $this->getSession()->addError(Mage::helper('foomanconnect')->__('Oauth error: %s',$responseBody));
            $consumer = $this->initConsumer();
        }
        try {
            $resultXml = new SimpleXMLElement(preg_replace('/(<\?xml[^?]+?)utf-16/i', '$1utf-8', $responseBody));
        }catch (Exception $e) {
            Mage::log($responseBody,null,self::CA_LOG_FILENAME);
            Mage::log($e->getMessage(),null,self::CA_LOG_FILENAME);
            throw new Exception("Result is not a valid xml reponse.");
            return false;
        }
        return $resultXml;
    }

    /**
     *  Triggered by Magento's crontab for automatic order exports
     *  @param void
     */
    public function cron() {
        if(Mage::helper('foomanconnect')->getMageStoreConfig('xeroautomatic')) {
            $errors ='';
            $successes='';

            //start logging
            if(Mage::helper('foomanconnect')->getMageStoreConfig('xerologenabled') && Mage::getStoreConfig('dev/log/active')) {
                Mage::log(Mage::helper('foomanconnect')->__('Cron Export to Xero started'),null,self::CA_LOG_FILENAME);
                $keepXeroLog=true;
            }else {
                $keepXeroLog=false;
            }
            $orders = $this->getOrdersWithStatus(false,self::CA_XERO_STATUS_NOT_EXPORTED,self::CA_CRON_PROCESS_AT_ONCE);
            if ($orders->count()) {
                foreach ($orders as $order) {
                    $result = false;
                    try {
                        $orderId = Mage::helper('sales')->__("Order").' '.$order->getId();
                        //let's create the draft invoice
                        $result = $this->exportOrderToXero($order);
                    }
                    catch (Exception $e ) {
                        $errors = empty($errors)?$orderId.": ".$e->getMessage():$errors."\n".$orderId.": ".$e->getMessage();
                    }
                    if($result) {
                        $successes .=empty($successes)?$orderId:','.$orderId;
                    }
                }
            }

            //Creditmemos
            $creditmemos = $this->getCreditmemosWithStatus(false,self::CA_XERO_STATUS_NOT_EXPORTED,self::CA_CRON_PROCESS_AT_ONCE);
            if ($creditmemos->count()) {
                foreach ($creditmemos as $creditmemo) {
                    $result = false;
                    try {
                        $creditmemoId = Mage::helper('sales')->__("Creditmemo").' '.$creditmemo->getId();
                        //let's create the draft invoice
                        $result = $this->exportCreditmemoToXero($creditmemo);
                    }
                    catch (Exception $e ) {
                        $errors = empty($errors)?$creditmemoId.": ".$e->getMessage():$errors."\n".$creditmemoId.": ".$e->getMessage();
                    }
                    if($result) {
                        $successes .=empty($successes)?$creditmemoId:','.$creditmemoId;
                    }
                }
            }

            //Add results to log
            if($keepXeroLog) {
                if(!empty($errors)) {
                    Mage::log($errors,null,self::CA_LOG_FILENAME);
                }
                if(!empty($successes)) {
                    Mage::log(Mage::helper('foomanconnect')->__('Successfully exported').': '.$successes,null,self::CA_LOG_FILENAME);
                }
                Mage::log(Mage::helper('foomanconnect')->__('Cron Export to Xero finished'),null,self::CA_LOG_FILENAME);
            }
        }

    }

    private function _createContactXml ($orderItems)
    {
        $res = array_walk_recursive($orderItems, array($this, '_filterValuesforXml'));
        //set up connection parameters
        $fooman = new Zend_Http_Client();
        $storeUrl = Mage::getStoreConfig('web/unsecure/base_url',Mage::app()->getStore());
        $fooman->setHeaders('Authorization', 'Bearer '.Mage::helper('foomancommon')->convertSerialToId(Mage::helper('foomanconnect')->getMageStoreConfig('serial')));
        $fooman->setUri(self::URL_LICENSE.'customer.xml');
        try {
            //connect to Fooman Server
            $result = $fooman->setParameterPost('store_url', $storeUrl)
                ->setParameterPost('order_data', json_encode($orderItems))
                ->request('POST');

        }catch (Exception $e){
            throw new Exception("Can't connect to license server.");
            return false;
        }
        return $result->getBody();
    }

    private function createXeroItemsXml($orderItems = NULL)
    {
        $res = array_walk_recursive($orderItems, array($this, '_filterValuesforXml'));
        //set up connection parameters
        $fooman = new Zend_Http_Client();
        $storeUrl = Mage::getStoreConfig('web/unsecure/base_url',Mage::app()->getStore());
        $fooman->setHeaders('Authorization', 'Bearer '.Mage::helper('foomancommon')->convertSerialToId(Mage::helper('foomanconnect')->getMageStoreConfig('serial')));
        $fooman->setUri(self::URL_LICENSE.'items.xml');
        try {
            //connect to Fooman Server
            $result = $fooman->setParameterPost('store_url', $storeUrl)
                ->setParameterPost('order_data', json_encode($orderItems))
                ->request('POST');

        }catch (Exception $e){
            throw new Exception("Can't connect to license server.");
            return false;
        }
        return $result->getBody();
    }

    /**
     * Construct xml string for passing to Xero
     *
     * @param array $orderItems
     * @return string $xml
     */
    private function createXeroXml($orderItems=NULL, $entryPoint = 'order.xml') {

        $res = array_walk_recursive($orderItems, array($this, '_filterValuesforXml'));
        //set up connection parameters
        $fooman = new Zend_Http_Client();
        $storeUrl = Mage::getStoreConfig('web/unsecure/base_url',Mage::app()->getStore());
        $fooman->setHeaders('Authorization', 'Bearer '.Mage::helper('foomancommon')->convertSerialToId(Mage::helper('foomanconnect')->getMageStoreConfig('serial')));
        $fooman->setUri(self::URL_LICENSE.$entryPoint);
        try {
            //connect to Fooman Server
            $result = $fooman->setParameterPost('store_url', $storeUrl)
                ->setParameterPost('order_data', json_encode($orderItems))
                ->request('POST');

        }catch (Exception $e){
            throw new Exception("Can't connect to license server.");
            return false;
        }
        return $result->getBody();

        setlocale(LC_ALL, 'en_US.UTF8');
        $xml=iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE',$xml);
        Mage::log($xml,null,self::CA_LOG_FILENAME);
        return $xml;

    }


    private function createXeroCreditmemoXml($items=NULL)
    {
        return $this->createXeroXml($items,'creditmemo.xml');
    }

    /**
     * Retrieve information from Order and prepare for export to Xero
     *
     * @param $salesModel
     *
     * @throws Exception
     * @return array $data
     */
    public function loadOrderData($salesModel) {

        $data=array();
        $discounts = array();

        //return if no order
        if (!$salesModel) {
            return false;
        }
        if($salesModel instanceof Mage_Sales_Model_Order) {
            $isOrder = true;
            $order = $salesModel;
            $data['url'] = Mage::helper('adminhtml')->getUrl('adminhtml/sales_order/view/order_id/'.$salesModel->getId(), array('_nosid' => true, '_nosecret' => true));
        } elseif ($salesModel instanceof Mage_Sales_Model_Order_Creditmemo) {
            $isOrder = false;
            $order = $salesModel->getOrder();
            $data['url'] = Mage::helper('adminhtml')->getUrl('adminhtml/sales_creditmemo/view/creditmemo_id/'.$salesModel->getId(), array('_nosid' => true, '_nosecret' => true));
        }

        $storeId = $order->getStore()->getId();

        // prepare Invoice Data
        $tempBillingAddress=$order->getBillingAddress();
        $tempBillingStreet=$tempBillingAddress->getStreet();

        $data['customerId']=$order->getCustomerId();
        $data['customerFirstname']=$order->getCustomerFirstname()?$order->getCustomerFirstname():$tempBillingAddress->getFirstname();
        $data['customerLastname']=$order->getCustomerLastname()?$order->getCustomerLastname():$tempBillingAddress->getLastname();

        $data['customerEmail']=$order->getCustomerEmail();
        $data['taxNumber']= $this->_retrieveTaxNumber($order);

        $data['billingStreet1']=$tempBillingStreet[0];
        $data['billingStreet2']=isset($tempBillingStreet[1])?$tempBillingStreet[1]:"";
        $data['billingCity']=$order->getBillingAddress()->getCity();
        $data['billingPostcode']=$order->getBillingAddress()->getPostcode();
        $data['billingCountry']=$order->getBillingAddress()->getCountry();
        $data['billingRegion']=$order->getBillingAddress()->getRegion();
        $data['billingTelephone']=$order->getBillingAddress()->getTelephone();
        $data['shippingDescription']=$order->getShippingDescription();
        $data['shippingTaxTypeOverride'] = true;

        if(Mage::helper('foomanconnect')->getMageStoreConfig('xeronumbering', $storeId)){
            $data['incrementId']='';
            $data['reference']=$salesModel->getIncrementId();
        } else {
            //Xero needs unique numbers across credit notes and invoices
            //add prefix here
            if ($salesModel instanceof Mage_Sales_Model_Order_Creditmemo) {
                $prefix = Mage::helper('foomanconnect')->getMageStoreConfig('xerocreditnoteprefix', $storeId);
            } else {
                $prefix = '';
            }
            /*if ($isOrder) {
                $invoiceIncrementId = $salesModel->getInvoiceCollection()->getFirstItem()->getIncrementId();
            }
            $data['incrementId'] = $isOrder ? $invoiceIncrementId : $prefix . $salesModel->getIncrementId();
            */

            $data['incrementId']=$prefix.$salesModel->getIncrementId();
            $data['reference']=$isOrder?'':$order->getIncrementId();
        }
        $date = Mage::app()->getLocale()->storeDate(
            $salesModel->getStore(),
            strtotime($salesModel->getCreatedAt()),
            false
        );
        $data['createdAt']=$date->toString("YYYY-MM-dd");
        $data['rounding']=0;
        $data['taxAdjustments']=0;
        $data['cumulativeAdjustedTax']=0;
        $data['cumulativeAdjustedTotal']=0;

        $data['xeroAccountCodeSale'] = Mage::helper('foomanconnect')->getMageStoreConfig('xeroaccountcodesale', $storeId);
        $data['xeroAccountCodeDiscounts'] = Mage::helper('foomanconnect')->getMageStoreConfig('xeroaccountcodediscounts', $storeId);
        $data['xeroAccountRounding'] = self::CA_XERO_ROUNDING_ACCT;
        $xeroTracking = Mage::helper('foomanconnect')->getMageStoreConfig('xerotracking', $storeId);

        if (!empty($xeroTracking)) {
            $xeroTracking = explode('|',$xeroTracking);
            $data['xeroTrackingCategoryID']=$xeroTracking[0];
            $data['xeroTrackingName'] = $xeroTracking[1];
            $data['xeroTrackingOption'] = $xeroTracking[2];
        }else {
            $data['xeroTrackingCategoryID']= "";
            $data['xeroTrackingName'] = "";
            $data['xeroTrackingOption'] = "";
        }

        if(Mage::helper('foomanconnect')->getMageStoreConfig('xerotransfercurrency', $storeId) == 'base') {

            $data['shippingAmount']=$salesModel->getBaseShippingAmount();
            $data['shippingTaxAmount']=$salesModel->getBaseShippingTaxAmount();
            $data['shippingTaxType']=$this->_getShippingTaxType($data['shippingAmount'], $data['shippingTaxAmount'], $storeId);
            $data['xeroAccountCodeShipping'] = Mage::helper('foomanconnect')->getMageStoreConfig('xeroaccountcodeshipping', $storeId);

            $data['surchargeAmount']=$salesModel->getBaseFoomanSurchargeAmount();
            if (!$isOrder) {
                $data['refundAmount']=$salesModel->getBaseAdjustmentPositive();
                $data['refundAmountFee']=-1*$salesModel->getBaseAdjustmentNegative();
            } else {
                $data['refundAmount']=0;
                $data['refundAmountFee']=0;
            }
            $data['surchargeTaxAmount']=$salesModel->getBaseFoomanSurchargeTaxAmount();
            $data['surchargeTaxType']=$this->_getSurchargeTaxType($data['surchargeAmount'], $data['surchargeTaxAmount'], $storeId);
            $data['xeroAccountCodeSurcharge'] = Mage::helper('foomanconnect')->getMageStoreConfig('xeroaccountcodesurcharge', $storeId);

            $data['discountAmount']=$salesModel->getBaseDiscountAmount();

            $data['taxAmount']=$salesModel->getBaseTaxAmount();
            $data['grandTotal']=$salesModel->getBaseGrandTotal();
            $data['subTotal']=sprintf("%01.4f", (float)$salesModel->getBaseSubtotal()+(float)$data['shippingAmount']+(float)$data['surchargeAmount']+(float)$salesModel->getBaseDiscountAmount());
            $data['currencyCode']=$salesModel->getStoreCurrencyCode();

            // Loop through the ordered items and add as Line Items
            $items = $isOrder?$salesModel->getAllItems():$salesModel->getAllItems();
            foreach ($items as $item) {
                $adjustment=0;
                $taxAdjustment=0;

                if ($isOrder) {
                    $orderItem = $item;
                } else {
                    $orderItem = Mage::getModel('sales/order_item')->load($item->getOrderItemId());
                }

                //Sort by Magentos internal id
                $magentoItemId=$isOrder?$item->getItemId():$item->getEntityId();

                //get product
                $product = Mage::getModel('catalog/product')->load($item->getProductId());

                //if it is a configurable overwrite the parents name and sku
                $fixedBundlePrice = false;
                $checkHasParent = $isOrder ? $item->getParentItemId() : $item->getParentId();
                if (!empty($checkHasParent)) {
                    $parentItem = Mage::getModel('sales/order_item')->load($checkHasParent);
                    if($parentItem->getId()){
                        if ($parentItem->getProductType() == 'configurable') {
                            $magentoItemId = $parentItem->getId();
                            $data['invoiceLines'][$magentoItemId]['sku'] = $item->getSku();
                            if(strlen($item->getSku()) <= 30) {
                                $data['invoiceLines'][$magentoItemId]['itemCode'] = $item->getSku();
                            }
                            $data['invoiceLines'][$magentoItemId]['qtyOrdered'] = $isOrder ? $item->getQtyOrdered() : $item->getQty();
                            $data['invoiceLines'][$magentoItemId]['name'] = $item->getName();
                            continue;
                        }
                        if ($parentItem->getProductType() == 'bundle') {
                            $parentProduct = Mage::getModel('catalog/product')->load($parentItem->getProductId());
                            $fixedBundlePrice = $parentProduct->getPriceType() == Mage_Bundle_Model_Product_Price::PRICE_TYPE_FIXED;
                        }
                    }
                } elseif ($item->getProductType() == 'bundle') {
                    $fixedBundlePrice = $product->getPriceType() == Mage_Bundle_Model_Product_Price::PRICE_TYPE_FIXED;
                }

                $productOptions = $item->getProductOptions();
                $bundleItemsWithPrices = 0;
                if(isset($productOptions['bundle_options'])){
                    foreach ($productOptions['bundle_options'] as $bundleOption) {
                        foreach ($bundleOption['value'] as $bundleValue) {
                            $bundleItemsWithPrices += $bundleValue['qty'] *  round($bundleValue['price'] * $order->getBaseToOrderRate(),2);
                        }
                    }
                }

                if (($item->getProductType() == 'bundle' && !$fixedBundlePrice )|| ($item->getProductType() != 'bundle') && $fixedBundlePrice){
                    //the parent bundle item is only passed in as a 0.00 amount item

                    $taxType = 'NONE';
                    if(isset($productOptions['bundle_selection_attributes'])) {
                        $parentQty = $isOrder?$parentItem->getQtyOrdered():$parentItem->getQty();
                        $bundleSelectionAttributes = unserialize($productOptions['bundle_selection_attributes']);
                        $adjustment=$parentQty*$bundleSelectionAttributes['qty'] * round($bundleSelectionAttributes['price'] * $order->getBaseToOrderRate(),2);
                        //magento calculates tax on the parent item not the child
                        $taxAdjustment=$this->_checkForTaxRoundingAdjustment($item,-$adjustment,'base',$parentItem->getTaxPercent());

                        $data['invoiceLines'][$magentoItemId]['sku'] = $item->getSku();
                        if(strlen($item->getSku()) <= 30) {
                            $data['invoiceLines'][$magentoItemId]['itemCode'] = $item->getSku();
                        }
                        $data['invoiceLines'][$magentoItemId]['qtyOrdered'] = $parentQty*$bundleSelectionAttributes['qty'];
                        $data['invoiceLines'][$magentoItemId]['name'] = $item->getName();
                        $data['invoiceLines'][$magentoItemId]['taxAmount'] = sprintf("%01.4f",$taxAdjustment);
                        $data['invoiceLines'][$magentoItemId]['taxType'] = $this->_getTaxType($parentItem, $storeId);
                        $data['invoiceLines'][$magentoItemId]['price'] = sprintf("%01.4f",round($bundleSelectionAttributes['price'] * $order->getBaseToOrderRate(),2));
                        $data['invoiceLines'][$magentoItemId]['xeroAccountCodeSale'] = $product->getXeroSalesAccountCode();
                        $data['invoiceLines'][$magentoItemId]['lineTotalNoAdjust'] = sprintf("%01.4f", (float)$item->getBaseRowTotalInclTax());
                        $data['invoiceLines'][$magentoItemId]['lineTotal'] = sprintf("%01.4f",$adjustment);

                    } else {
                        $data['invoiceLines'][$magentoItemId]['sku'] = $item->getSku();
                        if(strlen($item->getSku()) <= 30) {
                            $data['invoiceLines'][$magentoItemId]['itemCode'] = $item->getSku();
                        }
                        $data['invoiceLines'][$magentoItemId]['qtyOrdered'] = $isOrder?$item->getQtyOrdered():$item->getQty();
                        $data['invoiceLines'][$magentoItemId]['name'] = $item->getName();
                        $data['invoiceLines'][$magentoItemId]['taxAmount'] = sprintf("%01.4f",0);
                        $data['invoiceLines'][$magentoItemId]['taxType'] = 'NONE';
                        $data['invoiceLines'][$magentoItemId]['price'] = sprintf("%01.4f",0);
                        $data['invoiceLines'][$magentoItemId]['xeroAccountCodeSale'] = $product->getXeroSalesAccountCode();
                        $data['invoiceLines'][$magentoItemId]['lineTotalNoAdjust'] = sprintf("%01.4f", (float)$item->getBaseRowTotalInclTax());
                        $data['invoiceLines'][$magentoItemId]['lineTotal'] = sprintf("%01.4f",0);
                    }
                    //keep running totals
                    $data['rounding']-=$adjustment;
                    $data['taxAdjustments']+= $taxAdjustment;
                    $data['cumulativeAdjustedTax'] += $data['invoiceLines'][$magentoItemId]['taxAmount'];
                    $data['cumulativeAdjustedTotal'] += $data['invoiceLines'][$magentoItemId]['lineTotal'];
                } else {

                    $itemTaxAmount = ($item->getBaseTaxBeforeDiscount()?$item->getBaseTaxBeforeDiscount():$item->getBaseTaxAmount()+$item->getBaseHiddenTaxAmount());

                    //Check if Magento rounded incorrectly or if we are distributing prices from a fixed bundle
                    if ($bundleItemsWithPrices) {
                        $bundleAdjustment = ($isOrder ? $item->getQtyOrdered() : $item->getQty()) * -$bundleItemsWithPrices;
                        //$adjustment = $this->_checkForRoundingAdjustment($item, 'base', $bundleAdjustment);
                        $currentRowTotal = $item->getBaseRowTotal()+$itemTaxAmount;
                        $adjustment=  $bundleAdjustment + $this-> _checkForRoundUnitPrice ($currentRowTotal, ($isOrder ? $item->getQtyOrdered() : $item->getQty()));
                    } else {
                        $adjustment = $this->_checkForRoundingAdjustment($item, 'base');
                    }
                    $taxAdjustment=$this->_checkForTaxRoundingAdjustment($item,$adjustment,'base');


                    Mage::log('$itemTaxAmount '.$itemTaxAmount.' adjust '.$adjustment.'taxadjust '.$taxAdjustment,null,self::CA_LOG_FILENAME);
                    $data['invoiceLines'][$magentoItemId]['sku'] = $item->getSku();
                    if(strlen($item->getSku()) <= 30) {
                        $data['invoiceLines'][$magentoItemId]['itemCode'] = $item->getSku();
                    }
                    $data['invoiceLines'][$magentoItemId]['qtyOrdered'] = $isOrder?$item->getQtyOrdered():$item->getQty();
                    $data['invoiceLines'][$magentoItemId]['name'] = $item->getName();
                    $data['invoiceLines'][$magentoItemId]['taxAmount'] = $itemTaxAmount -$taxAdjustment;
                    $data['invoiceLines'][$magentoItemId]['taxType'] = $this->_getTaxType($item, $storeId);
                    $data['invoiceLines'][$magentoItemId]['price'] = round($item->getBasePrice()-$bundleItemsWithPrices,2);
                    $data['invoiceLines'][$magentoItemId]['xeroAccountCodeSale'] = $product->getXeroSalesAccountCode();

                    $data['invoiceLines'][$magentoItemId]['lineTotalNoAdjust'] = sprintf("%01.4f", (float)$item->getBaseRowTotalInclTax());
                    $data['invoiceLines'][$magentoItemId]['lineTotal'] = sprintf("%01.4f", (float)$item->getBaseRowTotal() + $data['invoiceLines'][$magentoItemId]['taxAmount'] +$adjustment);

                    if(!Mage::getStoreConfig('tax/calculation/apply_after_discount', $storeId) || !$this->_keepTaxAdjustmentInRowTotal($data['invoiceLines'][$magentoItemId],$adjustment, $taxAdjustment)) {
                        $data['invoiceLines'][$magentoItemId]['lineTotal'] = sprintf("%01.4f", (float)$data['invoiceLines'][$magentoItemId]['lineTotal'] + $taxAdjustment);
                    }

                    //check line total once more for adjustments
                    $adjustment2 = $this->_checkForRoundUnitPrice($data['invoiceLines'][$magentoItemId]['lineTotal'], $data['invoiceLines'][$magentoItemId]['qtyOrdered']);
                    $data['invoiceLines'][$magentoItemId]['lineTotal'] += $adjustment2;

                    //keep running totals
                    $data['rounding']-=$adjustment+$adjustment2;
                    $data['taxAdjustments']-= $taxAdjustment;
                    $data['cumulativeAdjustedTax'] += $data['invoiceLines'][$magentoItemId]['taxAmount'];
                    $data['cumulativeAdjustedTotal'] += $data['invoiceLines'][$magentoItemId]['lineTotal'];

                    if($item->getBaseDiscountAmount()>0) {
                        $taxType = $data['invoiceLines'][$magentoItemId]['taxType'];
                        $discounts[$taxType]['discountTaxPercent'] = $item->getTaxPercent();
                        if($item->getBaseTaxBeforeDiscount() >0){
                            if(isset($discounts[$taxType]['discountAmount'])) {
                                $discounts[$taxType]['discountAmount']+=round($item->getBaseDiscountAmount()+$item->getBaseTaxBeforeDiscount()-$item->getBaseTaxAmount(),2);
                                $discounts[$taxType]['discountTaxAmount']+=round($item->getBaseTaxBeforeDiscount()-$item->getBaseTaxAmount(),2);
                            }else {
                                $discounts[$taxType]['discountAmount']=round($item->getBaseDiscountAmount()+$item->getBaseTaxBeforeDiscount()-$item->getBaseTaxAmount(),2);
                                $discounts[$taxType]['discountTaxAmount']=round($item->getBaseTaxBeforeDiscount()-$item->getBaseTaxAmount(),2);
                            }
                        }else {
                            if(isset($discounts[$taxType]['discountAmount'])) {
                                $discounts[$taxType]['discountAmount']+=$item->getBaseDiscountAmount();
                                $discounts[$taxType]['discountTaxAmount']+=$item->getBaseHiddenTaxAmount();
                            }else {
                                $discounts[$taxType]['discountAmount']=$item->getBaseDiscountAmount();
                                $discounts[$taxType]['discountTaxAmount']=$item->getBaseHiddenTaxAmount();
                            }
                        }
                    }
                }
            }
        }else { //transfer order currency
            $data['shippingAmount']=$salesModel->getShippingAmount();
            $data['shippingTaxAmount']=$salesModel->getShippingTaxAmount();
            $data['shippingTaxType']=$this->_getShippingTaxType($data['shippingAmount'], $data['shippingTaxAmount'], $storeId);
            $data['xeroAccountCodeShipping'] = Mage::helper('foomanconnect')->getMageStoreConfig('xeroaccountcodeshipping', $storeId);

            $data['surchargeAmount']=$salesModel->getFoomanSurchargeAmount();
            if (!$isOrder) {
                $data['refundAmount']=$salesModel->getAdjustmentPositive();
                $data['refundAmountFee']=-1*$salesModel->getAdjustmentNegative();
            } else {
                $data['refundAmount']=0;
                $data['refundAmountFee']=0;
            }
            $data['surchargeTaxAmount']=$salesModel->getFoomanSurchargeTaxAmount();
            $data['surchargeTaxType']=$this->_getSurchargeTaxType($data['surchargeAmount'], $data['surchargeTaxAmount'], $storeId);
            $data['xeroAccountCodeSurcharge'] = Mage::helper('foomanconnect')->getMageStoreConfig('xeroaccountcodesurcharge', $storeId);

            $data['discountAmount']=$salesModel->getDiscountAmount();

            $data['taxAmount']=$salesModel->getTaxAmount();
            $data['grandTotal']=$salesModel->getGrandTotal();
            $data['subTotal']=sprintf("%01.4f", (float)$salesModel->getSubtotal()+(float)$data['shippingAmount']+(float)$data['surchargeAmount']+(float)$salesModel->getDiscountAmount());
            $data['currencyCode']=$salesModel->getOrderCurrencyCode();

            // Loop through the ordered items and add as Line Items
            $items = $isOrder?$salesModel->getAllItems():$salesModel->getAllItems();
            foreach ($items as $item) {
                $adjustment=0;
                $taxAdjustment=0;

                if ($isOrder) {
                    $orderItem = $item;
                } else {
                    $orderItem = Mage::getModel('sales/order_item')->load($item->getOrderItemId());
                }

                //Sort by Magentos internal id
                $magentoItemId=$isOrder?$item->getItemId():$item->getEntityId();

                //get product
                $product = Mage::getModel('catalog/product')->load($item->getProductId());

                //if it is a configurable overwrite the parents name and sku
                $fixedBundlePrice = false;
                $checkHasParent = $isOrder ? $item->getParentItemId() : $item->getParentId();
                if (!empty($checkHasParent)) {
                    $parentItem = Mage::getModel('sales/order_item')->load($checkHasParent);
                    if($parentItem->getId()){
                        if ($parentItem->getProductType() == 'configurable') {
                            $magentoItemId = $parentItem->getId();
                            $data['invoiceLines'][$magentoItemId]['sku'] = $item->getSku();
                            if(strlen($item->getSku()) <= 30) {
                                $data['invoiceLines'][$magentoItemId]['itemCode'] = $item->getSku();
                            }
                            $data['invoiceLines'][$magentoItemId]['qtyOrdered'] = $isOrder ? $item->getQtyOrdered() : $item->getQty();
                            $data['invoiceLines'][$magentoItemId]['name'] = $item->getName();
                            continue;
                        }
                        if ($parentItem->getProductType() == 'bundle') {
                            $parentProduct = Mage::getModel('catalog/product')->load($parentItem->getProductId());
                            $fixedBundlePrice = $parentProduct->getPriceType() == Mage_Bundle_Model_Product_Price::PRICE_TYPE_FIXED;
                        }
                    }
                } elseif ($item->getProductType() == 'bundle') {
                    $fixedBundlePrice = $product->getPriceType() == Mage_Bundle_Model_Product_Price::PRICE_TYPE_FIXED;
                }

                $productOptions = $item->getProductOptions();
                $bundleItemsWithPrices = 0;
                if(isset($productOptions['bundle_options'])){
                    foreach ($productOptions['bundle_options'] as $bundleOption) {
                        foreach ($bundleOption['value'] as $bundleValue) {
                            $bundleItemsWithPrices += $bundleValue['qty'] *  round($bundleValue['price'],2);
                        }
                    }
                }

                if (($item->getProductType() == 'bundle' && !$fixedBundlePrice )|| ($item->getProductType() != 'bundle') && $fixedBundlePrice){
                    //the parent bundle item is only passed in as a 0.00 amount item

                    $taxType = 'NONE';
                    if(isset($productOptions['bundle_selection_attributes'])) {
                        $parentQty = $isOrder?$parentItem->getQtyOrdered():$parentItem->getQty();
                        $bundleSelectionAttributes = unserialize($productOptions['bundle_selection_attributes']);
                        $adjustment=$parentQty*$bundleSelectionAttributes['qty']*round($bundleSelectionAttributes['price'],2);
                        //magento calculates tax on the parent item not the child
                        $taxAdjustment=$this->_checkForTaxRoundingAdjustment($item,-$adjustment,null,$parentItem->getTaxPercent());

                        $data['invoiceLines'][$magentoItemId]['sku'] = $item->getSku();
                        if(strlen($item->getSku()) <= 30) {
                            $data['invoiceLines'][$magentoItemId]['itemCode'] = $item->getSku();
                        }
                        $data['invoiceLines'][$magentoItemId]['qtyOrdered'] = $parentQty*$bundleSelectionAttributes['qty'];
                        $data['invoiceLines'][$magentoItemId]['name'] = $item->getName();
                        $data['invoiceLines'][$magentoItemId]['taxAmount'] = sprintf("%01.4f",$taxAdjustment);
                        $data['invoiceLines'][$magentoItemId]['taxType'] = $this->_getTaxType($parentItem, $storeId);
                        $data['invoiceLines'][$magentoItemId]['price'] = sprintf("%01.4f",round($bundleSelectionAttributes['price'],2));
                        $data['invoiceLines'][$magentoItemId]['xeroAccountCodeSale'] = $product->getXeroSalesAccountCode();
                        $data['invoiceLines'][$magentoItemId]['lineTotalNoAdjust'] = sprintf("%01.4f", (float)$item->getRowTotalInclTax());
                        $data['invoiceLines'][$magentoItemId]['lineTotal'] = sprintf("%01.4f",$adjustment);

                    } else {
                        $data['invoiceLines'][$magentoItemId]['sku'] = $item->getSku();
                        if(strlen($item->getSku()) <= 30) {
                            $data['invoiceLines'][$magentoItemId]['itemCode'] = $item->getSku();
                        }
                        $data['invoiceLines'][$magentoItemId]['qtyOrdered'] = $isOrder?$item->getQtyOrdered():$item->getQty();
                        $data['invoiceLines'][$magentoItemId]['name'] = $item->getName();
                        $data['invoiceLines'][$magentoItemId]['taxAmount'] = sprintf("%01.4f",0);
                        $data['invoiceLines'][$magentoItemId]['taxType'] = 'NONE';
                        $data['invoiceLines'][$magentoItemId]['price'] = sprintf("%01.4f",0);
                        $data['invoiceLines'][$magentoItemId]['xeroAccountCodeSale'] = $product->getXeroSalesAccountCode();
                        $data['invoiceLines'][$magentoItemId]['lineTotalNoAdjust'] = sprintf("%01.4f", (float)$item->getRowTotalInclTax());
                        $data['invoiceLines'][$magentoItemId]['lineTotal'] = sprintf("%01.4f",0);
                    }
                    //keep running totals
                    $data['rounding']-=$adjustment;
                    $data['taxAdjustments']+= $taxAdjustment;
                    $data['cumulativeAdjustedTax'] += $data['invoiceLines'][$magentoItemId]['taxAmount'];
                    $data['cumulativeAdjustedTotal'] += $data['invoiceLines'][$magentoItemId]['lineTotal'];
                } else {

                    $itemTaxAmount = ($item->getTaxBeforeDiscount()?$item->getTaxBeforeDiscount():$item->getTaxAmount()+$item->getHiddenTaxAmount());

                    //Check if Magento rounded incorrectly or if we are distributing prices from a fixed bundle
                    if ($bundleItemsWithPrices) {
                        $bundleAdjustment = ($isOrder ? $item->getQtyOrdered() : $item->getQty()) * -$bundleItemsWithPrices;
                        //$adjustment = $this->_checkForRoundingAdjustment($item, null, $bundleAdjustment);
                        $currentRowTotal = $item->getRowTotal()+$itemTaxAmount;
                        $adjustment=  $bundleAdjustment + $this-> _checkForRoundUnitPrice ($currentRowTotal, ($isOrder ? $item->getQtyOrdered() : $item->getQty()));
                    } else {
                        $adjustment = $this->_checkForRoundingAdjustment($item);
                    }
                    $taxAdjustment=$this->_checkForTaxRoundingAdjustment($item,$adjustment);

                    $data['invoiceLines'][$magentoItemId]['sku'] = $item->getSku();
                    if(strlen($item->getSku()) <= 30) {
                        $data['invoiceLines'][$magentoItemId]['itemCode'] = $item->getSku();
                    }
                    $data['invoiceLines'][$magentoItemId]['qtyOrdered'] = $isOrder?$item->getQtyOrdered():$item->getQty();
                    $data['invoiceLines'][$magentoItemId]['name'] = $item->getName();
                    $data['invoiceLines'][$magentoItemId]['taxAmount'] = $itemTaxAmount -$taxAdjustment;
                    $data['invoiceLines'][$magentoItemId]['taxType'] = $this->_getTaxType($item, $storeId);
                    $data['invoiceLines'][$magentoItemId]['price'] = round($item->getPrice()-$bundleItemsWithPrices,2);
                    $data['invoiceLines'][$magentoItemId]['xeroAccountCodeSale'] = $product->getXeroSalesAccountCode();

                    $data['invoiceLines'][$magentoItemId]['lineTotalNoAdjust'] = sprintf("%01.4f", (float)$item->getRowTotalInclTax());
                    $data['invoiceLines'][$magentoItemId]['lineTotal'] = sprintf("%01.4f", (float)$item->getRowTotal() + $data['invoiceLines'][$magentoItemId]['taxAmount'] +$adjustment);
                    if(!Mage::getStoreConfig('tax/calculation/apply_after_discount', $storeId) && !$this->_keepTaxAdjustmentInRowTotal($data['invoiceLines'][$magentoItemId],$adjustment, $taxAdjustment)) {
                        $data['invoiceLines'][$magentoItemId]['lineTotal'] = sprintf("%01.4f", (float)$data['invoiceLines'][$magentoItemId]['lineTotal'] + $taxAdjustment);
                    }

                    //check line total once more for adjustments
                    $adjustment2 = $this->_checkForRoundUnitPrice($data['invoiceLines'][$magentoItemId]['lineTotal'], $data['invoiceLines'][$magentoItemId]['qtyOrdered']);
                    $data['invoiceLines'][$magentoItemId]['lineTotal'] += $adjustment2;

                    //keep running totals
                    $data['rounding']-=$adjustment+$adjustment2;
                    $data['taxAdjustments']-= $taxAdjustment;
                    $data['cumulativeAdjustedTax'] += $data['invoiceLines'][$magentoItemId]['taxAmount'];
                    $data['cumulativeAdjustedTotal'] += $data['invoiceLines'][$magentoItemId]['lineTotal'];

                    if($item->getDiscountAmount()>0) {
                        $taxType = $data['invoiceLines'][$magentoItemId]['taxType'];
                        $discounts[$taxType]['discountTaxPercent'] = $item->getTaxPercent();
                        if($item->getTaxBeforeDiscount() >0){
                            if(isset($discounts[$taxType]['discountAmount'])) {
                                $discounts[$taxType]['discountAmount']+=round($item->getDiscountAmount()+$item->getTaxBeforeDiscount()-$item->getTaxAmount(),2);
                                $discounts[$taxType]['discountTaxAmount']+=round($item->getTaxBeforeDiscount()-$item->getTaxAmount(),2);
                            }else {
                                $discounts[$taxType]['discountAmount']=round($item->getDiscountAmount()+$item->getTaxBeforeDiscount()-$item->getTaxAmount(),2);
                                $discounts[$taxType]['discountTaxAmount']=round($item->getTaxBeforeDiscount()-$item->getTaxAmount(),2);
                            }
                        }else {
                            if(isset($discounts[$taxType]['discountAmount'])) {
                                $discounts[$taxType]['discountAmount']+=$item->getDiscountAmount()+$item->getHiddenTaxAmount();
                                $discounts[$taxType]['discountTaxAmount']+=$item->getHiddenTaxAmount();
                            }else {
                                $discounts[$taxType]['discountAmount']=$item->getDiscountAmount()+$item->getHiddenTaxAmount();
                                $discounts[$taxType]['discountTaxAmount']=$item->getHiddenTaxAmount();
                            }
                        }
                    }
                }
            }
        }

        $i=1;
        foreach($discounts as $discountTaxType=>$discount){
            $discountKey = 'discount'.$i;

            if(!Mage::getStoreConfig('tax/calculation/apply_after_discount', $storeId)){
                $discountTaxType = 'NONE';
                $data['subTotal'] = sprintf("%01.4f", (float)$data['subTotal'] - $discount['discountTaxAmount']);
                $discountTaxAmount = '0.0000';
            } else {
                if(Mage::getStoreConfig('tax/calculation/apply_discount_tax', $storeId)){
                    $discountTaxAmount =  round(($discount['discountAmount']*($discount['discountTaxPercent']/100)),2);
                    $discount['discountAmount'] = $discount['discountAmount'] + $discountTaxAmount;
                } else {
                    $discountTaxAmount =  round($discount['discountAmount']-($discount['discountAmount']/(1 + $discount['discountTaxPercent']/100)),2);
                }
            }

            $data['invoiceLines'][$discountKey]['sku'] = '';
            $data['invoiceLines'][$discountKey]['qtyOrdered'] = '1';
            $data['invoiceLines'][$discountKey]['name'] = 'Discount';
            $data['invoiceLines'][$discountKey]['taxAmount'] = round(-1*$discountTaxAmount,2);
            $data['invoiceLines'][$discountKey]['taxType'] = $discountTaxType;
            $data['invoiceLines'][$discountKey]['price'] = round(-1*($discount['discountAmount']),2);
            $data['invoiceLines'][$discountKey]['lineTotal'] = round(-1*($discount['discountAmount']),2);
            //keep runnning totals
            $data['cumulativeAdjustedTax'] += $data['invoiceLines'][$discountKey]['taxAmount'];
            $data['cumulativeAdjustedTotal'] += $data['invoiceLines'][$discountKey]['lineTotal'];
            $data['discountAmount'] += -1*$data['invoiceLines'][$discountKey]['lineTotal'];
            $i++;
        }

        //discount amount left that wasn't distributed on items ...
        if($data['discountAmount'] < 0) {
            $discountKey = 'discount'.$i;
            $data['invoiceLines'][$discountKey]['sku'] = '';
            $data['invoiceLines'][$discountKey]['qtyOrdered'] = '1';
            $data['invoiceLines'][$discountKey]['name'] = 'Discount';
            $data['invoiceLines'][$discountKey]['taxAmount'] = '0.0000';
            $data['invoiceLines'][$discountKey]['taxType'] = 'NONE';
            $data['invoiceLines'][$discountKey]['price'] = round($data['discountAmount'],2);
            $data['invoiceLines'][$discountKey]['lineTotal'] = round($data['discountAmount'],2);

            //keep runnning totals
            $data['cumulativeAdjustedTax'] += $data['invoiceLines'][$discountKey]['taxAmount'];
            $data['cumulativeAdjustedTotal'] += $data['invoiceLines'][$discountKey]['lineTotal'];

        }

        if($data['surchargeAmount']>0) {
            $data['invoiceLines']['surcharge']['sku'] = '';
            $data['invoiceLines']['surcharge']['qtyOrdered'] = '1';
            $data['invoiceLines']['surcharge']['name'] = $salesModel->getFoomanSurchargeDescription();
            $data['invoiceLines']['surcharge']['taxAmount'] =$data['surchargeTaxAmount'];
            $data['invoiceLines']['surcharge']['taxType'] = $data['surchargeTaxType'];

            $data['invoiceLines']['surcharge']['price'] = $data['surchargeAmount'] ;
            $data['invoiceLines']['surcharge']['lineTotal'] = $data['surchargeAmount']+$data['surchargeTaxAmount'];
            //keep runnning totals
            $data['cumulativeAdjustedTax'] += $data['surchargeTaxAmount'];
            $data['cumulativeAdjustedTotal'] += $data['invoiceLines']['surcharge']['lineTotal'];
        }

        if($data['refundAmount']!=0){
            $data['invoiceLines']['adjustment']['sku'] = '';
            $data['invoiceLines']['adjustment']['qtyOrdered'] = '1';
            $data['invoiceLines']['adjustment']['name'] = 'Adjustment Refund';
            $data['invoiceLines']['adjustment']['taxAmount'] ='0.0000';
            $data['invoiceLines']['adjustment']['taxType'] = Mage::helper('foomanconnect')->getMageStoreConfig('xerodefaultzerotaxrate', $storeId);
            $data['invoiceLines']['adjustment']['price'] = $data['refundAmount'] ;
            $data['invoiceLines']['adjustment']['lineTotal'] = $data['refundAmount'];
            $data['invoiceLines']['adjustment']['xeroAccountCodeSale'] = Mage::helper('foomanconnect')->getMageStoreConfig('xeroaccountcoderefunds', $storeId);

            //keep runnning totals
            $data['cumulativeAdjustedTax'] += $data['invoiceLines']['adjustment']['taxAmount'];
            $data['cumulativeAdjustedTotal'] += $data['invoiceLines']['adjustment']['lineTotal'];
        }

        if($data['refundAmountFee']!=0){
            $data['invoiceLines']['adjustment-fee']['sku'] = '';
            $data['invoiceLines']['adjustment-fee']['qtyOrdered'] = '1';
            $data['invoiceLines']['adjustment-fee']['name'] = 'Adjustment Fee';
            $data['invoiceLines']['adjustment-fee']['taxAmount'] ='0.0000';
            $data['invoiceLines']['adjustment-fee']['taxType'] = Mage::helper('foomanconnect')->getMageStoreConfig('xerodefaultzerotaxrate', $storeId);
            $data['invoiceLines']['adjustment-fee']['price'] = $data['refundAmountFee'] ;
            $data['invoiceLines']['adjustment-fee']['lineTotal'] = $data['refundAmountFee'];
            $data['invoiceLines']['adjustment-fee']['xeroAccountCodeSale'] = Mage::helper('foomanconnect')->getMageStoreConfig('xeroaccountcoderefunds', $storeId);

            //keep runnning totals
            $data['cumulativeAdjustedTax'] += $data['invoiceLines']['adjustment-fee']['taxAmount'];
            $data['cumulativeAdjustedTotal'] += $data['invoiceLines']['adjustment-fee']['lineTotal'];
        }

        /*
        if($salesModel->getMicoRushprocessingprice()>0) {
            $data['invoiceLines']['rushProcessing']['sku'] = '';
            $data['invoiceLines']['rushProcessing']['qtyOrdered'] = '1';
            $data['invoiceLines']['rushProcessing']['name'] = 'Production & Packaging';
            $data['invoiceLines']['rushProcessing']['taxAmount'] =  round($salesModel->getMicoRushprocessingprice()-$salesModel->getMicoRushprocessingprice()/1.2,2);
            $data['invoiceLines']['rushProcessing']['taxType'] = 'OUTPUT2';

            $data['invoiceLines']['rushProcessing']['price'] = $salesModel->getMicoRushprocessingprice();
            $data['invoiceLines']['rushProcessing']['lineTotal'] = $salesModel->getMicoRushprocessingprice()+$data['invoiceLines']['rushProcessing']['taxAmount'];
            //keep runnning totals
            $data['cumulativeAdjustedTax'] += $data['invoiceLines']['rushProcessing']['taxAmount'];
            $data['cumulativeAdjustedTotal'] += $data['invoiceLines']['rushProcessing']['lineTotal'];
        }
        */

        //Magento can have a shipping tax amount even if no shipping costs apply ?!
        //Likely used to balance out tax amount - adjust for it here
        if ($data['shippingAmount'] == 0) {
            $data['taxAdjustments']+=$data['shippingTaxAmount'];
            $data['shippingTaxAmount'] = 0;
        }else {
            $shippingTaxAdjustment = $this->_checkForShippingTaxRoundingAdjustment($data);
            $data['shippingTaxAmount']-=$shippingTaxAdjustment;
            $data['taxAdjustments']+=$shippingTaxAdjustment;
            if(Mage::getConfig('tax/calculation/shipping_includes_tax', $storeId)) {
                $data['shippingAmount']+=$shippingTaxAdjustment;
            }
        }

        //keep runnning totals
        $data['cumulativeAdjustedTax'] += $data['shippingTaxAmount'];
        $data['cumulativeAdjustedTotal'] += $data['shippingAmount'] + $data['shippingTaxAmount'];

        $data['taxAmount']-=$data['taxAdjustments'];

        //trust cumulative adjusted totals

        Mage::log('using cumulative totals',null,self::CA_LOG_FILENAME);
        $data['taxAmount'] = $data['cumulativeAdjustedTax'];
        $data['subTotal'] = $data['grandTotal'] - $data['cumulativeAdjustedTax'];

        if (abs($data['grandTotal'] - $data['cumulativeAdjustedTotal']) > 0.0005) {
            $data['rounding'] = $data['grandTotal'] - $data['cumulativeAdjustedTotal'];
        } else {
            $data['rounding'] = 0;
        }
        $data['rounding'] = round($data['rounding'], 2);
        if(Mage::helper('foomanconnect')->getMageStoreConfig('xerologenabled', $storeId) && Mage::getStoreConfig('dev/log/active', $storeId)) {
            Mage::log($data,null,self::CA_LOG_FILENAME);
        }
        if ($data['grandTotal'] > 0 && ($data['rounding'] / $data['grandTotal']) > 0.02) {
            throw new Exception ('Rounding amount is higher than 2% of the total amount');
        }
        return $data;
    }


    /**
     * Take an order and export it to Xero. Returns true on success
     *
     * @param Mage_Sales_Model_Order $order
     *
     * @throws Exception
     * @return bool
     */
    public function exportOrderToXero($order) {

        //load the order
        $order = Mage::getModel('sales/order')->load($order->getId());

        //prepare the data we need
        $orderExportItems=$this->loadOrderData($order);

        $storeId = $order->getStore()->getId();

        //dont export orders with 0 total
        if($orderExportItems['grandTotal'] == 0 && !Mage::helper('foomanconnect')->getMageStoreConfig('xeroexportzero', $storeId)) {
            $order->setXeroExportStatus(self::CA_XERO_STATUS_ORDER_WONT_EXPORT)->save();
            return false;
        }

        if($this->initConsumer()) {
            $token =$this->getXeroAccessToken();

            //Export to Xero if we have an access token, order, export is enabled
            if ($token && $orderExportItems && Mage::helper('foomanconnect')->getMageStoreConfig('xeroenabled', $storeId)) {
                // load or set settings

                //start logging
                if(Mage::helper('foomanconnect')->getMageStoreConfig('xerologenabled', $storeId) && Mage::getStoreConfig('dev/log/active', $storeId)) {
                    Mage::log("Export to Xero Started",null,self::CA_LOG_FILENAME);
                    Mage::log(Mage::helper('foomancommon')->convertSerialToId(Mage::helper('foomanconnect')->getMageStoreConfig('serial')),null,self::CA_LOG_FILENAME);
                    $keepXeroLog=true;
                }else {
                    $keepXeroLog=false;
                }
                $retry = true;

                //copy the order data to a draft invoice in Xero
                try {

                    //connect to Xero Server and post new invoice
                    $client =  $token->getHttpClient($this->getConfiguration());

                    //make sure items are up to date in Xero
                    $client->setUri(self::URL_ROOT.self::CA_ITEMS);
                    $response = $client->setParameterPost('xml', $this->createXeroItemsXml($orderExportItems))->request('POST');
                    $resultXml = $this->handleResponse($response);

                    //export order
                    $client->setUri(self::URL_ROOT.self::CA_INVOICES_PATH);
                    $xml = $this->createXeroXml($orderExportItems);
                    setlocale(LC_ALL, 'en_US.UTF8');
                    $xml = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE',$xml);
                    $response = $client->setParameterPost('xml', $xml)->request('POST');

                    //Check if the response contains an error message, for example access token has expired
                    $resultXml = $this->handleResponse($response);

                    if (!((string)$resultXml->Status=="OK")) {
                        //not successful - flag ExportedToXero as attempted but failed
                        //better to save the order twice since the ValidationError could error out by itself
                        $resultSave = $order->setXeroExportStatus(self::CA_XERO_STATUS_ORDER_ATTEMPTED_BUT_FAILED)->save();

                        if($retry && isset($resultXml->Elements->DataContractBase->Contact->ContactID)) {
                            $orderExportItems['xeroContactID'] = $this->updateCustomerDetailsInXero($orderExportItems);
                            $xml = $this->createXeroXml($orderExportItems);
                            setlocale(LC_ALL, 'en_US.UTF8');
                            $xml = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE',$xml);
                            //connect to Xero Server and post new invoice
                            $client =  $token->getHttpClient($this->getConfiguration());
                            $client->setUri(self::URL_ROOT.self::CA_INVOICES_PATH);
                            $response = $client->setParameterPost('xml', $xml)->request('POST');

                            //Check if the response contains an error message, for example access token has expired
                            $resultXml = $this->handleResponse($response);
                        }
                    }

                    if (!((string)$resultXml->Status=="OK")) {
                        $errorsFromXero = '';
                        $errorsFromXero[] = (string)$resultXml->Message;
                        if(isset($resultXml->Elements->DataContractBase->ValidationErrors->ValidationError)) {
                            foreach ($resultXml->Elements->DataContractBase->ValidationErrors->ValidationError as $validationError) {
                                $errorsFromXero[] = (string)$validationError->Message;
                            }
                        }
                        $order->setXeroLastValidationErrors(serialize($errorsFromXero))->save();
                        throw new Exception("The Xero Server returned:".$client->getLastResponse()->asString());
                    }

                    //we have been successful - flag ExportedToXero
                    $order->setXeroExportStatus(self::CA_XERO_STATUS_ORDER_EXPORTED);
                    $order->setXeroInvoiceId((string)$resultXml->Invoices->Invoice->InvoiceID);
                    $order->setXeroInvoiceNumber((string)$resultXml->Invoices->Invoice->InvoiceNumber);
                    if(Mage::helper('foomanconnect')->getMageStoreConfig('xeronumbering', $storeId)){
                        $order->setIncrementId((string)$resultXml->Invoices->Invoice->InvoiceNumber);
                        if(!$order->getEmailSent()){
                            $order->sendNewOrderEmail();
                            $order->setEmailSent(true);
                        }
                    }
                    $order->setXeroLastValidationErrors('')->save();
                    if($keepXeroLog) {
                        Mage::log("Magento Order #".$order->getIncrementId()." saved in Xero as ID ".$resultXml->Invoices->Invoice->InvoiceNumber." [".$resultXml->Invoices->Invoice->InvoiceID."]",null,self::CA_LOG_FILENAME);
                    }
                    unset($order);
                    return true;

                } catch (Exception $e) {
                    //we don't want to stop the process so only keep a log entry
                    if($keepXeroLog) {
                        Mage::log("Caught exception: ". $e->getMessage(),null,self::CA_LOG_FILENAME);
                    }
                }
            }
        }
        unset($order);
        return false;
    }

    /**
     * Take a creditmemo and export it to Xero. Returns true on success
     *
     * @param Mage_Sales_Model_Order_Creditmemo $creditmemo
     *
     * @throws Exception
     * @return bool
     */
    public function exportCreditmemoToXero($creditmemo) {

        //load the credit memo
        $creditmemo = Mage::getModel('sales/order_creditmemo')->load($creditmemo->getId());

        //prepare the data we need
        $creditmemoExportItems=$this->loadOrderData($creditmemo);

        $storeId = $creditmemo->getStore()->getId();

        //dont export credit memos with 0 total
        if($creditmemoExportItems['grandTotal'] == 0 && !Mage::helper('foomanconnect')->getMageStoreConfig('xeroexportzero', $storeId)) {
            $creditmemo->setXeroExportStatus(self::CA_XERO_STATUS_ORDER_WONT_EXPORT)->save();
            return false;
        }

        if($this->initConsumer()) {
            $token =$this->getXeroAccessToken();

            //Export to Xero if we have an access token, order, export is enabled
            if ($token && $creditmemoExportItems && Mage::helper('foomanconnect')->getMageStoreConfig('xeroenabled', $storeId)) {

                //start logging
                if(Mage::helper('foomanconnect')->getMageStoreConfig('xerologenabled') && Mage::getStoreConfig('dev/log/active',$storeId)) {
                    Mage::log("Export to Xero Started",null,self::CA_LOG_FILENAME);
                    $keepXeroLog=true;
                }else {
                    $keepXeroLog=false;
                }

                //copy the creditmemo data to a draft credit note in Xero
                try {

                    //connect to Xero Server and post new data
                    $client =  $token->getHttpClient($this->getConfiguration());
                    $client->setUri(self::URL_ROOT.self::CA_CREDITNOTE_PATH);
                    $response = $client->setParameterPost('xml', $this->createXeroCreditmemoXml($creditmemoExportItems))->request('POST');

                    //Check if the response contains an error message, for example access token has expired
                    $resultXml = $this->handleResponse($response);

                    if (!((string)$resultXml->Status=="OK")) {
                        //not successful - flag ExportedToXero as attempted but failed
                        //better to save the credit memo twice since the ValidationError could error out by itself
                        //$resultSave = $creditmemo->setXeroExportStatus(self::CA_XERO_STATUS_ORDER_ATTEMPTED_BUT_FAILED)->save();
                        $errorsFromXero = '';
                        $errorsFromXero[] = (string)$resultXml->Message;
                        if(isset($resultXml->Elements->DataContractBase->ValidationErrors->ValidationError)) {
                            foreach ($resultXml->Elements->DataContractBase->ValidationErrors->ValidationError as $validationError) {
                                $errorsFromXero[] = (string)$validationError->Message;
                            }
                        }
                        $creditmemo->setXeroLastValidationErrors(serialize($errorsFromXero))->save();
                        throw new Exception("The Xero Server returned:".$client->getLastResponse()->asString());
                    }else {
                        //we have been successful - flag ExportedToXero
                        $creditmemo->setXeroExportStatus(self::CA_XERO_STATUS_ORDER_EXPORTED);
                        $creditmemo->setXeroCreditnoteId((string)$resultXml->CreditNotes->CreditNote->CreditNoteID);
                        $creditmemo->setXeroCreditnoteNumber((string)$resultXml->CreditNotes->CreditNote->CreditNoteNumber);
                        if(Mage::helper('foomanconnect')->getMageStoreConfig('xeronumbering')){
                            $creditmemo->setIncrementId((string)$resultXml->CreditNotes->CreditNote->CreditNoteNumber);
                        }
                        $creditmemo->setXeroLastValidationErrors('')->save();
                        if($keepXeroLog) {
                            Mage::log("Magento Creditmemo #".$creditmemo->getIncrementId()." saved in Xero as ID ".$resultXml->CreditNotes->CreditNote->CreditNoteNumber." [".$resultXml->CreditNotes->CreditNote->CreditNoteID."]",null,self::CA_LOG_FILENAME);
                        }
                        unset($creditmemo);
                        return true;
                    }
                } catch (Exception $e) {
                    //we don't want to stop the process so only keep a log entry
                    if($keepXeroLog) {
                        Mage::log("Caught exception: ". $e->getMessage(),null,self::CA_LOG_FILENAME);
                    }
                }
            }
        }
        unset($creditmemo);
        return false;
    }


    /**
     * Retrieve payments reconciled against a certain order from Xero.
     *
     * @param Mage_Sales_Model_Order $order
     * @return array $returnArray
     */
    public function getPaymentsFromXero($order) {
        $returnArray = array();
        $resultXml=$this->getInvoiceFromXero($order);
        if($resultXml) {
            //we have been successful
            foreach ($resultXml->Invoices->Invoice->Payments as $payments) {
                foreach ($payments->Payment as $payment) {
                    $returnArray['payments'][]=array(
                        'date' => Mage::helper('core')->formatDate((string)$payment->Date,'medium'),
                        'amount' =>Mage::app()->getStore()->formatPrice((float)$payment->Amount)
                    );
                }
                $returnArray['amountDue']= Mage::app()->getStore()->formatPrice((float)$resultXml->Invoices->Invoice->AmountDue);

            }
        }
        return $returnArray;
    }

    /**
     * Retrieve invoices for a certain order from Xero.
     *
     * @param Mage_Sales_Model_Order $order
     *
     * @throws Exception
     * @return string $resultXml
     */
    public function getInvoiceFromXero($order) {

        if($this->isConfigured() && $order) {
            $_session = $this->getSession();

            if($this->initConsumer()) {
                //Check for Access Token / but we really should already have one after initConsumer
                if (!empty($this->_xeroAccessToken)) {
                    try {
                        $client =  $this->_xeroAccessToken->getHttpClient($this->getConfiguration());
                        $client->setUri(self::URL_ROOT.self::CA_INVOICE_PATH.'/'.$order->getXeroInvoiceId());
                        $client->setMethod(Zend_Http_Client::GET);
                        $response = $client->request();

                    } catch (Exception $e) {

                        $_session->addError($e->getMessage());
                    }
                }

                try {
                    //Check if the response contains an error message, for example access token has expired
                    $resultXml = $this->handleResponse($response);
                    if (!($resultXml->Status=="OK")) {
                        throw new Exception("Request Unsuccesful");
                    }else {
                        return $resultXml;
                    }
                } catch (Exception $e) {
                    Mage::log($e->getMessage());
                }
            }
        }
        return false;
    }

    /**
     * Update customer details in Xero with address details from order
     *
     * @param $orderItems
     *
     * @throws Exception
     * @return string $resultXml
     */
    public function updateCustomerDetailsInXero($orderItems) {


        if($this->isConfigured()) {
            $_session = $this->getSession();

            if($this->initConsumer()) {
                //Check for Access Token / but we really should already have one after initConsumer
                if (!empty($this->_xeroAccessToken)) {
                    try {
                        $client =  $this->_xeroAccessToken->getHttpClient($this->getConfiguration());
                        $client->setMethod(Zend_Http_Client::GET);
                        $client->setUri(self::URL_ROOT.self::CA_CONTACTS_PATH);
                        $client->setParameterGet(array(
                                'where'  => 'Name=="'.$orderItems['customerFirstname'].' '.$orderItems['customerLastname'].'"'
                            ));
                        $response = $client->request();

                    } catch (Exception $e) {
                        $_session->addError($e->getMessage());
                    }
                }

                try {
                    //Check if the response contains an error message, for example access token has expired

                    $resultXml = $this->handleResponse($response);

                    if (!($resultXml->Status=="OK")) {
                        throw new Exception("Contact Update Request Unsuccesful");
                    }else {
                        if(!isset($resultXml->Contacts->Contact)){
                            return false;
                        }
                        foreach ($resultXml->Contacts->Contact as $contact) {
                            $orderItems['xeroContactID'] = (string)$contact->ContactID;
                        }
                        $xml = '<?xml version="1.0" encoding="UTF-8" ?>';
                        $xml .= $this->_createContactXml($orderItems);
                        $client =  $this->_xeroAccessToken->getHttpClient($this->getConfiguration());
                        $client->setUri(self::URL_ROOT.self::CA_CONTACTS_PATH);
                        $response = $client->setParameterPost('xml', $xml)->request('POST');
                        $resultXml = $this->handleResponse($response);
                        return $orderItems['xeroContactID'];

                    }
                } catch (Exception $e) {
                    Mage::log($e->getMessage());
                }
            }
        }
        return false;
    }

    /**
     * Retrieve all orders for a certain Xero Export Status. Either return as order collection or array.
     *
     * @param bool       $asArray
     * @param int|string $status
     * @param bool|int   $pagesize
     *
     * @return array|object $array | Mage_Sales_Model_Mysql4_Order_Collection
     */
    public function getOrdersWithStatus($asArray=false, $status = self::CA_XERO_STATUS_NOT_EXPORTED,$pagesize=false) {

        $statusToExport=Mage::helper('foomanconnect')->getMageStoreConfig('xeroexportwithstatus');

        /*
         * Load orders which haven't been exported yet (= the attribute xero_export_status either doesn't exist or is false for an existing order)
         *
         * Original SQL Query
         * select sales_order.entity_id,sales_order_int.value from sales_order
         * left join (select entity_id,value from sales_order_int where `attribute_id` = $xeroExportStatusAttributeeId ) as sales_order_int
         * on sales_order.entity_id = sales_order_int.entity_id where sales_order_int.value = 0
         * OR sales_order_int.value IS NULL
        */
        $unexportedOrders = Mage::getModel('sales/order')->getCollection();

        switch ($status) {
            //status not exported or the attribute is blank
        case self::CA_XERO_STATUS_NOT_EXPORTED:
            $unexportedOrders
                ->addAttributeToSelect('entity_id')
                ->addAttributeToFilter('created_at', array('from' => Mage::helper('foomanconnect')->getMageStoreConfig('xeroorderstartdate')))
                ->addAttributeToFilter('xero_export_status', array('or'=> array(
                    0 => array('eq'=>$status),
                    1 => array('is' => new Zend_Db_Expr('null')))
                ), 'left')
                ->addAttributeToFilter('status', array('in' => explode(',',$statusToExport)));
            break;
            //status as requested
        default:
            $unexportedOrders
                ->addAttributeToSelect('entity_id')
                ->addAttributeToFilter('created_at', array('from' => Mage::helper('foomanconnect')->getMageStoreConfig('xeroorderstartdate')))
                ->addAttributeToFilter('xero_export_status', array('eq'=>$status))
                ->addAttributeToFilter('status', array('in' => explode(',',$statusToExport)));
        }

        if($pagesize) {
            $unexportedOrders->setPageSize($pagesize)->setCurPage(1);
        }
        //create array of OrderIds of unexportedOrders to return or return Collection
        if($asArray) {
            $ids=array();
            foreach ($unexportedOrders as $unexportedOrder) {
                $ids[] = $unexportedOrder->getId();
            }
            return $ids;
        }else {
            return $unexportedOrders;
        }
    }

    /**
     * Retrieve all creditmemos for a certain Xero Export Status. Either return as order collection or array.
     *
     * @param bool       $asArray
     * @param int|string $status
     * @param bool|int   $pagesize
     *
     * @return array|object $array | Mage_Sales_Model_Mysql4_Order_Creditmemo_Collection
     */
    public function getCreditmemosWithStatus($asArray=false, $status = self::CA_XERO_STATUS_NOT_EXPORTED, $pagesize=false) {

        $statusToExport=Mage::helper('foomanconnect')->getMageStoreConfig('xeroexportwithstatus');

        $unexportedCreditmemos = Mage::getModel('sales/order_creditmemo')->getCollection();

        switch ($status) {
            //status not exported or the attribute is blank
        case self::CA_XERO_STATUS_NOT_EXPORTED:
            $unexportedCreditmemos
                ->addAttributeToSelect('entity_id')
                ->addAttributeToFilter('created_at', array('from' => Mage::helper('foomanconnect')->getMageStoreConfig('xerocreditmemostartdate')))
                ->addAttributeToFilter('xero_export_status', array('or'=> array(
                    0 => array('eq'=>$status),
                    1 => array('is' => new Zend_Db_Expr('null')))
                ), 'left');
            break;
            //status as requested
        default:
            $unexportedCreditmemos
                ->addAttributeToSelect('entity_id')
                ->addAttributeToFilter('created_at', array('from' => Mage::helper('foomanconnect')->getMageStoreConfig('xerocreditmemostartdate')))
                ->addAttributeToFilter('xero_export_status', array('eq'=>$status));
        }

        if($pagesize) {
            $unexportedCreditmemos->setPageSize($pagesize)->setCurPage(1);
        }
        //create array of CreditmemoIds of unexportedCreditmemos to return or return Collection
        if($asArray) {
            $ids=array();
            foreach ($unexportedCreditmemos as $unexportedCreditmemo) {
                $ids[] = $unexportedCreditmemo->getId();
            }
            return $ids;
        }else {
            return $unexportedCreditmemos;
        }
    }

    /**
     * Retrieve taxcode as used in Xero for item in the following order:
     * 1. return Xero Rate as passed through from sales_convert_quote_item_to_order_item
     * 2. check for Default Tax Rate with zero tax
     * 3. check if tax rate matches default as mentioned here: http://blog.xero.com/developer/api/types/
     * 4. download all rates from Xero and match based on tax rate
     * 5. last fall back on a default defined in $xeroVersionDefaults
     *
     * @param Mage_Sales_Model_Order_Item $item
     * @param                             $storeId
     *
     * @return string
     */
    private function _getTaxType($item, $storeId) {

        if(!$item instanceof Mage_Sales_Model_Order_Item) {
            $item = $item->getOrderItem();
        }

        if($item->getXeroRate()) {
            if($item->getXeroRate()!='NONE') {
                $rates=explode(',',$item->getXeroRate());
                //only taking rates[0] at the moment until Xero supports multiple rates
                return $rates[0];
            }
        }
        if((float)$item->getTaxAmount() == 0) {
            return Mage::helper('foomanconnect')->getMageStoreConfig('xerodefaultzerotaxrate', $storeId);
        }
        $xeroVersionDefaults = $this->xeroVersionDefaults;
        $xeroVersionUsed = Mage::helper('foomanconnect')->getMageStoreConfig('xeroversion', $storeId);
        if(isset($xeroVersionDefaults[$xeroVersionUsed][$item->getTaxPercent()])) {
            return $xeroVersionDefaults[$xeroVersionUsed]['taxrates']['OUT'][$item->getTaxPercent()];
        }
        else {
            if(empty($this->_xeroTaxRatesDownloaded)) {
                $this->_xeroTaxRatesDownloaded=Mage::getModel('foomanconnect/system_taxOptions')->toOptionArray('options-only');
            }
            if(isset($this->_xeroTaxRatesDownloaded[(string)$item->getTaxPercent()])) {
                return $this->_xeroTaxRatesDownloaded[(string)$item->getTaxPercent()];
            }
            return $xeroVersionDefaults[$xeroVersionUsed]['taxrates']['OUT']['default'];
        }
    }

    private function _getShippingTaxType($shippingAmount, $shippingTaxAmount, $storeId)
    {
        $shippingTaxType = Mage::helper('foomanconnect')->getMageStoreConfig('xeroshippingtax', $storeId);
        if(empty($this->_xeroTaxRatesDownloaded)) {
            $this->_xeroTaxRatesDownloaded=Mage::getModel('foomanconnect/system_taxOptions')->toOptionArray('options-only');
        }
        $shippingTaxPercentage = Mage::getModel('foomanconnect/system_taxOptions')->toOptionArray($shippingTaxType);
        if((float)$shippingAmount > 0 && (float)$shippingTaxAmount == 0 && $shippingTaxPercentage <> 0) {
            return Mage::helper('foomanconnect')->getMageStoreConfig('xerodefaultzerotaxrate', $storeId);
        }

        return $shippingTaxType;
    }

    private function _getSurchargeTaxType($surchargeAmount, $surchargeTaxAmount, $storeId)
    {
        $surchargeTaxType = Mage::helper('foomanconnect')->getMageStoreConfig('xerosurchargetax', $storeId);
        $surchargeTaxPercentage= Mage::getModel('foomanconnect/system_taxOptions')->toOptionArray($surchargeTaxType);
        if((float)$surchargeTaxAmount == 0 && $surchargeTaxPercentage <>0) {
            return Mage::helper('foomanconnect')->getMageStoreConfig('xerodefaultzerotaxrate', $storeId);
        }

        return $surchargeTaxType;
    }

    /**
     * In some configurations Magento doesn't calculate tax on the row total resulting in unit prices with fractional prices
     * calculate needed adjustment to be applied towards the row total and subsequently the Rounding Account
     *
     * @param Mage_Sales_Model_Order_Item $item
     * @param string                      $type
     * @param int                         $bundleAdjustment
     *
     * @return float
     */
    private function _checkForRoundingAdjustment ($item, $type=null, $bundleAdjustment=0)
    {
        if($item instanceof Mage_Sales_Model_Order_Item) {
            $qty = $item->getQtyOrdered();
        } else {
            $qty = $item->getQty();
        }
        if ($item->getBasePriceInclTax() > 0) {
            if ($type == 'base') {
                $shouldBeTotal = round($item->getBasePriceInclTax(),2) * $qty;
                return round($shouldBeTotal - $item->getBaseRowTotalInclTax(), 2);
            } else {
                $shouldBeTotal = round($item->getPriceInclTax(),2) * $qty;
                return round($shouldBeTotal - $item->getRowTotalInclTax(), 2);
            }
        } else {
            if ($type == 'base') {
                if ($item->getBaseTaxBeforeDiscount() > 0) {
                    $currentRowTotal = $item->getBaseRowTotal() + $item->getBaseTaxBeforeDiscount();
                } else {
                    $currentRowTotal = $item->getBaseRowTotal() + $item->getBaseTaxAmount();
                }
            } else {
                if ($item->getTaxBeforeDiscount() > 0) {
                    $currentRowTotal = $item->getRowTotal() + $item->getTaxBeforeDiscount();
                } else {
                    $currentRowTotal = $item->getRowTotal() + $item->getTaxAmount();
                }
            }
            $resultingUnitPrice = $currentRowTotal/$qty;
            $resultingUnitPrice = floor($resultingUnitPrice*100)/100;
            $effectiveRowTotal = $resultingUnitPrice * $qty;
            return round($effectiveRowTotal - $currentRowTotal, 2);
        }
    }

    private function _checkForRoundUnitPrice ($currentRowTotal, $qty)
    {
        $resultingUnitPrice = round($currentRowTotal / $qty, 2);
        $effectiveRowTotal = $resultingUnitPrice * $qty;
        return round($effectiveRowTotal - $currentRowTotal, 2);
    }


    /**
     * In some configurations Magento doesn't calculate tax on the row total resulting in 0.01 tax differences
     * calculate needed adjustment to be applied towards the tax amount
     *
     * @param Mage_Sales_Model_Order_Item $item
     * @param                             $adjustment
     * @param string                      $type
     * @param bool                        $overrideTaxPercent
     *
     * @return float
     */

    private function _checkForTaxRoundingAdjustment($item,$adjustment,$type=null, $overrideTaxPercent=false) {
        if($item instanceof Mage_Sales_Model_Order_Item) {
            $orderItem = $item;
        } else {
            $orderItem = $item->getOrderItem();
        }
        if ($item->getRowTotal() >= $item->getRowTotalInclTax()) {
            if ($type == 'base') {
                if ($item->getBaseTaxBeforeDiscount()) {
                    $tax = $item->getBaseTaxBeforeDiscount();
                } else {
                    $tax = $item->getBaseTaxAmount();
                }
                $rowTotal = $item->getBaseRowTotal() + $tax + $adjustment;
            } else {
                if ($item->getTaxBeforeDiscount()) {
                    $tax = $item->getTaxBeforeDiscount();
                } else {
                    $tax = $item->getTaxAmount();
                }
                $rowTotal = $item->getRowTotal() + $tax + $adjustment;
            }
        } else {
            if ($type == 'base') {
                $rowTotal = $item->getBaseRowTotalInclTax()+$adjustment;
                $tax = $item->getBaseTaxAmount() + $item->getBaseHiddenTaxAmount();
            } else {
                $rowTotal = $item->getRowTotalInclTax()+$adjustment;
                $tax = $item->getTaxAmount() + $item->getHiddenTaxAmount();
            }
        }
        $taxRecalculated = $rowTotal - round($rowTotal / (1 + ($overrideTaxPercent?$overrideTaxPercent:$orderItem->getTaxPercent()) / 100), 2);
        return round($tax - $taxRecalculated, 2);
    }

    /**
     * Magento doesn't remember how it taxed shipping
     * work it out here and check for rounding issues
     *
     * @param array $data
     * @return float
     */
    private function _checkForShippingTaxRoundingAdjustment($data) {
        if ($data['shippingTaxType'] && $data['shippingTaxAmount'] > 0) {
            $rate = $this->_xeroTaxRatesDownloaded=Mage::getModel('foomanconnect/system_taxOptions')->toOptionArray($data['shippingTaxType']);
            if($rate && !is_array($rate)) {
                $totalShippingCharged = $data['shippingAmount']+$data['shippingTaxAmount'];
                $taxOnShipping = round($totalShippingCharged-($totalShippingCharged/(1+($rate/100))),2);
                return round($data['shippingTaxAmount']-$taxOnShipping,2);
            }
        }
        return 0;
    }

    private function _filterValuesforXml(&$value, $key)
    {
        //remove &amp; so we don't end up with &amp;amp;
        $value = str_replace('&amp;', '&', $value);

        //replace
        $search = array('&');
        $replace = array('&amp;');
        $value = str_replace($search, $replace, $value);
    }

    private function _keepTaxAdjustmentInRowTotal($currentRow,$adjustment, $taxAdjustment)
    {
        if($currentRow['lineTotal']-(round($currentRow['lineTotal']/$currentRow['qtyOrdered'],2)*$currentRow['qtyOrdered']) == 0){
            return true;
        }

        return false;
    }

    /**
     * try to retrieve a tax number from various sources for this order
     * try order first, then billing address and lastly the customer account
     *
     * @param $order
     *
     * @return string|bool
     */
    protected function _retrieveTaxNumber($order)
    {
        if ($order->getCustomerTaxvat()) {
            return $order->getCustomerTaxvat();
        }

        if ($order->getBillingAddress()) {
            $country = Mage::helper('foomanconnect')->getMageStoreConfig('xeroversion', $order->getStoreId());
            if ($country == 'uk') {
                if ($order->getBillingAddress()->getVatId()) {
                    return $order->getBillingAddress()->getVatId();
                }
            }

            if ($order->getBillingAddress()->getTaxId()) {
                return $order->getBillingAddress()->getTaxId();
            }
        }

        if ($order->getCustomerId()) {
            $customer = Mage::getModel('customer/customer')->load($order->getCustomerId());
            if ($customer->getId()) {
                if ($customer->getTaxVat()) {
                    return $customer->getTaxVat();
                }
            }
        }
        return false;
    }

}

