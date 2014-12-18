<?php

class Fooman_Connect_Adminhtml_XeroController extends Mage_Adminhtml_Controller_Action {

    protected $_publicActions = array('callback');
    protected $_xeroOauth;
    protected $_session;
    protected $_consumer;

    protected function _construct() {
        $this->setUsedModuleName('Fooman_Connect');
    }

    private function _getXeroOauthModel() {
        return Mage::getModel('foomanconnect/xeroOauth');
    }

    private function _initAction() {
        $this->_xeroOauth = $this->_getXeroOauthModel();
        $this->_session = $this->_xeroOauth->getSession();
        $this->_consumer = $this->_xeroOauth->initConsumer();
    }

    public function createKeysAction ()
    {

        if (!Mage::helper('foomanconnect')->getMageStoreConfig('privatekey')) {
            try{
                $dn = array(
                    'private_key_bits' => 2048
                );
                $privKeyRessource = openssl_pkey_new($dn);
                if(!$privKeyRessource){
                    Mage::throwException(Mage::helper('foomanconnect')->__('Couldn\'t create private key - please check your server\'s and php\'s openssl configuration: %s', openssl_error_string()));
                }
                openssl_pkey_export($privKeyRessource, $privatekey);
                if(empty($privatekey)){
                    Mage::throwException(Mage::helper('foomanconnect')->__('Couldn\'t create private key - please check your server\'s and php\'s openssl configuration: %s', openssl_error_string()));
                }
                $privatekey = Mage::helper('core')->encrypt($privatekey);
                Mage::helper('foomanconnect')->setMageStoreConfig('privatekey', $privatekey);
            } catch (Exception $e) {
                $this->_getXeroOauthModel()->getSession()->addError('Error '.$e->getMessage());
            }
        }

        //go back to the Fooman Connect > Xero page
        $this->_redirect('adminhtml/xero/');
    }

    public function downloadPublicKeyAction ()
    {

        $privateKey = Mage::helper('foomanconnect')->getMageStoreConfig('privatekey');
        if (!$privateKey) {
            $this->_getXeroOauthModel()->getSession()->addError(Mage::helper('foomanconnect')->__('A Private Key is required before generating a Public Key.') . Mage::helper('foomanconnect')->__('You can create a Private Key by clicking <a href="%s">here</a>.',
                            Mage::helper('adminhtml')->getUrl('adminhtml/xero/createKeys')));
        } else {
            $privateKeyResource = openssl_pkey_get_private(Mage::helper('core')->decrypt($privateKey));

            $dn = array(
                "countryName" => Mage::getStoreConfig('general/country/default'),
                "stateOrProvinceName" => Mage::getStoreConfig('general/country/default'),
                "organizationName" => Mage::app()->getStore()->getName(),
                "organizationalUnitName" => "Magento Xero Integration by Fooman",
                "commonName" => str_replace(array('https://','http://'),'',Mage::getStoreConfig('web/unsecure/base_url')),
                "emailAddress" => Mage::getSingleton('admin/session')->getUser()->getEmail()
            );
            $csrResource = openssl_csr_new($dn, $privateKeyResource);
            $cert = openssl_csr_sign($csrResource, NULL, $privateKeyResource, 3650);
            openssl_x509_export ($cert, $publicKey);
            return $this->_prepareDownloadResponse('publickey.cer', $publicKey, 'application/x-x509-ca-cert');
        }

        //go back to the Fooman Connect > Xero page
        $this->_redirect('adminhtml/xero/');
    }

    public function indexAction() {

        if(!$this->_getXeroOauthModel()->isConfigured()) {
            $this->_getXeroOauthModel()->getSession()->addError(Mage::helper('foomanconnect')->__('Connection to Xero is not yet set up - please go to System > Configuration > Fooman Connect'));
            if(!Mage::helper('foomanconnect')->getMageStoreConfig('privatekey')) {
                $this->_getXeroOauthModel()->getSession()->addNotice(Mage::helper('foomanconnect')->__('You can create a Private Key by clicking <a href="%s">here</a>.',
                    Mage::helper('adminhtml')->getUrl('adminhtml/xero/createKeys')));
            } else {
                $this->_getXeroOauthModel()->getSession()->addNotice(Mage::helper('foomanconnect')->__('You can download the Public Key file for use in Xero by clicking <a href="%s">here</a>.',
                    Mage::helper('adminhtml')->getUrl('adminhtml/xero/downloadPublicKey')));
            }
        }

        $this->loadLayout();
        $this->_setActiveMenu('foomanconnect/xero');
        $this->_addContent(
                $this->getLayout()->createBlock('foomanconnect/adminhtml_xero', 'xero')
        );
        $this->renderLayout();
    }

    public function exportSelectedAction() {
        $errors='';
        $successes='';

        $statusToExport=Mage::helper('foomanconnect')->getMageStoreConfig('xeroexportwithstatus');
        $statusToExport=explode(',', $statusToExport);

        //Get order_ids from POST
        $orderIds = $this->getRequest()->getPost('order_ids');

        //loop through orders
        if (is_array($orderIds) && !empty($orderIds)) {
            sort($orderIds);
            foreach ($orderIds as $orderId) {
                $order      = Mage::getModel('sales/order')->load($orderId);
                $orderIncrementId = $order->getIncrementId();
                if(!in_array($order->getStatus(),$statusToExport)) {
                    $errors = empty($errors)?$orderIncrementId.": ".Mage::helper('foomanconnect')->__('Order status is not allowed for export - see config.'):$errors."<br/>".$orderIncrementId.": ".Mage::helper('foomanconnect')->__('Order status is not allowed for export - see config.');
                    continue;
                }
                if($order->getXeroExportStatus() == Fooman_Connect_Model_XeroOauth::CA_XERO_STATUS_ORDER_EXPORTED) {
                    //we have already exported it
                    $errors = empty($errors)?$orderIncrementId.": ".Mage::helper('foomanconnect')->__('Has already been exported.'):$errors."<br/>".$orderIncrementId.": ".Mage::helper('foomanconnect')->__('Has already been exported.');
                }else {
                    try {
                        //let's create the draft invoice
                        $result = $this->_getXeroOauthModel()->exportOrderToXero($order);
                    }
                    catch (Exception $e ) {
                        $result = false;
                        $errors = empty($errors)?$orderIncrementId.": ".$e->getMessage():$errors."<br/>".$orderIncrementId.": ".$e->getMessage();
                    }
                    if($result) {
                        $successes .=empty($successes)?$orderIncrementId:', '.$orderIncrementId;
                    }
                }

            }
        }
        //Add results to session
        if(!empty($errors)) {
            $this->_getSession()->addError($errors);
        }
        if(!empty($successes)) {
            $this->_getSession()->addSuccess(Mage::helper('foomanconnect')->__('Successfully exported').': '.$successes);
        }
        //go back to the order overview page
        $this->_redirect('adminhtml/xero/');
    }

    public function neverExportSelectedAction() {
        $errors='';
        $successes='';

        //Get order_ids from POST
        $orderIds = $this->getRequest()->getPost('order_ids');

        //loop through orders
        if (is_array($orderIds) && !empty($orderIds)) {
            sort($orderIds);
            foreach ($orderIds as $orderId) {
                $order      = Mage::getModel('sales/order')->load($orderId);
                $orderIncrementId = $order->getIncrementId();
                if($order->getXeroExportStatus() == Fooman_Connect_Model_XeroOauth::CA_XERO_STATUS_ORDER_EXPORTED) {
                    //we have already exported it
                    $errors = empty($errors)?$orderIncrementId.": ".Mage::helper('foomanconnect')->__('Has already been exported.'):$errors."<br/>".$orderIncrementId.": ".Mage::helper('foomanconnect')->__('Has already been exported.');
                }else {
                    try {
                        //let's set the status
                        $order->setXeroExportStatus(Fooman_Connect_Model_XeroOauth::CA_XERO_STATUS_ORDER_WONT_EXPORT)->save();
                    }
                    catch (Exception $e ) {
                        $errors = empty($errors)?$orderIncrementId.": ".$e->getMessage():$errors."<br/>".$orderIncrementId.": ".$e->getMessage();
                    }
                }

            }
        }
        //Add results to session
        if(!empty($errors)) {
            $this->_getSession()->addError($errors);
        }else {
            $this->_getSession()->addSuccess(Mage::helper('foomanconnect')->__('Successfully changed order export status.'));
        }
        //go back to the order overview page
        $this->_redirect('adminhtml/xero/');
    }

    public function creditmemoAction() {

        if(!$this->_getXeroOauthModel()->isConfigured()) {
            $this->_getXeroOauthModel()->getSession()->addError(Mage::helper('foomanconnect')->__('Connection to Xero is not yet set up - please go to System > Configuration > Fooman Connect'));
            if(!Mage::helper('foomanconnect')->getMageStoreConfig('privatekey')) {
                $this->_getXeroOauthModel()->getSession()->addNotice(Mage::helper('foomanconnect')->__('You can create a Private Key by clicking <a href="%s">here</a>.',
                    Mage::helper('adminhtml')->getUrl('adminhtml/xero/createKeys')));
            } else {
                $this->_getXeroOauthModel()->getSession()->addNotice(Mage::helper('foomanconnect')->__('You can download the Public Key file for use in Xero by clicking <a href="%s">here</a>.',
                    Mage::helper('adminhtml')->getUrl('adminhtml/xero/downloadPublicKey')));
            }
        }

        $this->loadLayout();
        $this->_setActiveMenu('foomanconnect/xero');
        $this->_addContent(
                $this->getLayout()->createBlock('foomanconnect/adminhtml_creditmemo', 'xero')
        );
        $this->renderLayout();
    }


    public function exportSelectedCreditmemosAction() {
        $errors='';
        $successes='';

        //Get ids from POST
        $creditmemoIds = $this->getRequest()->getPost('creditmemo_ids');

        //loop through credit memos
        if (is_array($creditmemoIds) && !empty($creditmemoIds)) {
            sort($creditmemoIds);
            foreach ($creditmemoIds as $creditmemoId) {
                $creditmemo      = Mage::getModel('sales/order_creditmemo')->load($creditmemoId);
                $creditmemoIncrementId = $creditmemo->getIncrementId();
                if($creditmemo->getXeroExportStatus() == Fooman_Connect_Model_XeroOauth::CA_XERO_STATUS_ORDER_EXPORTED) {
                    //we have already exported it
                    $errors = empty($errors)?$creditmemoIncrementId.": ".Mage::helper('foomanconnect')->__('Has already been exported.'):$errors."<br/>".$creditmemoIncrementId.": ".Mage::helper('foomanconnect')->__('Has already been exported.');
                }else {
                    try {
                        //let's create the draft credit note
                        $result = $this->_getXeroOauthModel()->exportCreditmemoToXero($creditmemo);
                    }
                    catch (Exception $e ) {
                        $result = false;
                        $errors = empty($errors)?$creditmemoIncrementId.": ".$e->getMessage():$errors."<br/>".$creditmemoIncrementId.": ".$e->getMessage();
                    }
                    if($result) {
                        $successes .=empty($successes)?$creditmemoIncrementId:', '.$creditmemoIncrementId;
                    }
                }

            }
        }
        //Add results to session
        if(!empty($errors)) {
            $this->_getSession()->addError($errors);
        }
        if(!empty($successes)) {
            $this->_getSession()->addSuccess(Mage::helper('foomanconnect')->__('Successfully exported').': '.$successes);
        }
        //go back to the order overview page
        $this->_redirect('adminhtml/xero/creditmemo');
    }

    public function neverExportSelectedCreditmemosAction() {
        $errors='';
        $successes='';

        //Get ids from POST
        $creditmemoIds = $this->getRequest()->getPost('creditmemo_ids');

        //loop through credit memos
        if (is_array($creditmemoIds) && !empty($creditmemoIds)) {
            sort($creditmemoIds);
            foreach ($creditmemoIds as $creditmemoId) {
                $creditmemo      = Mage::getModel('sales/order_creditmemo')->load($creditmemoId);
                $creditmemoIncrementId = $creditmemo->getIncrementId();
                if($creditmemo->getXeroExportStatus() == Fooman_Connect_Model_XeroOauth::CA_XERO_STATUS_ORDER_EXPORTED) {
                    //we have already exported it
                    $errors = empty($errors)?$creditmemoIncrementId.": ".Mage::helper('foomanconnect')->__('Has already been exported.'):$errors."<br/>".$creditmemoIncrementId.": ".Mage::helper('foomanconnect')->__('Has already been exported.');
                }else {
                    try {
                        //let's set the status
                        $creditmemo->setXeroExportStatus(Fooman_Connect_Model_XeroOauth::CA_XERO_STATUS_ORDER_WONT_EXPORT)->save();
                    }
                    catch (Exception $e ) {
                        $errors = empty($errors)?$creditmemoIncrementId.": ".$e->getMessage():$errors."<br/>".$creditmemoIncrementId.": ".$e->getMessage();
                    }
                }

            }
        }
        //Add results to session
        if(!empty($errors)) {
            $this->_getSession()->addError($errors);
        }else {
            $this->_getSession()->addSuccess(Mage::helper('foomanconnect')->__('Successfully changed creditmemo export status.'));
        }
        //go back to the order overview page
        $this->_redirect('adminhtml/xero/creditmemo');
    }


    public function processOneAction() {
        $errors='';
        $successes='';

        //Get order_id from url
        $orderId = $this->getRequest()->getParam('order_id');

        //loop through orders
        if (!empty($orderId) && is_numeric($orderId)) {

            $order      = Mage::getModel('sales/order')->load($orderId);
            $orderIncrementId = $order->getIncrementId();
            if($order->getXeroExportStatus() == Fooman_Connect_Model_XeroOauth::CA_XERO_STATUS_ORDER_EXPORTED) {
                //we have already exported it
                $errors = empty($errors)?$orderIncrementId.": ".Mage::helper('foomanconnect')->__('Has already been exported.'):$errors."<br/>".$orderIncrementId.": ".Mage::helper('foomanconnect')->__('Has already been exported.');
            }else {
                try {
                    //let's create the draft invoice
                    $result = $this->_getXeroOauthModel()->exportOrderToXero($order);
                }
                catch (Exception $e ) {
                    $errors = empty($errors)?$orderIncrementId.": ".$e->getMessage():$errors."<br/>".$orderIncrementId.": ".$e->getMessage();
                }
                if($result) {
                    $successes .=empty($successes)?$orderIncrementId:', '.$orderIncrementId;
                }
            }

        }
        //Add results to session
        if(!empty($errors)) {
            $this->_getSession()->addError($errors);
        }
        if(!empty($successes)) {
            $this->_getSession()->addSuccess(Mage::helper('foomanconnect')->__('Successfully exported').': '.$successes);
        }

        $this->loadLayout();
        $response = $this->getLayout()->createBlock('foomanconnect/adminhtml_sales_order_xero')->toHtml();

        if (is_array($response)) {
            $response = Zend_Json::encode($response);
        }
        $this->getResponse()->setBody($response);

    }

    public function resetAllAction() {
        try {
            if (version_compare(Mage::getVersion(), '1.4.0.0') < 0) {
                $tableSalesOrderVarchar = Mage::getSingleton('core/resource')->getTableName('sales_order_varchar');
                $tableSalesOrderInt = Mage::getSingleton('core/resource')->getTableName('sales_order_int');
                $write = Mage::getSingleton('core/resource')->getConnection('core_write');

                //order entity
                $orderEntityType = Mage::getSingleton('eav/config')->getEntityType('order');
                //xero_export_status - int
                $xeroExportStatusAttribute = Mage::getSingleton('eav/config')->getAttribute($orderEntityType->getEntityTypeId(), 'xero_export_status');
                $xeroExportStatusAttributeId = $xeroExportStatusAttribute->getAttributeId();
                $condition = $write->quoteInto('attribute_id = ?', $xeroExportStatusAttributeId);
                //$write->delete($tableSalesOrderInt, $condition);
                $write->update($tableSalesOrderInt, array('value' => Fooman_Connect_Model_XeroOauth::CA_XERO_STATUS_NOT_EXPORTED), $condition);
                //xero_invoice_id - varchar
                $xeroInvoiceIdAttribute = Mage::getSingleton('eav/config')->getAttribute($orderEntityType->getEntityTypeId(), 'xero_invoice_id');
                $xeroInvoiceIdAttributeId = $xeroInvoiceIdAttribute->getAttributeId();
                $condition = $write->quoteInto('attribute_id = ?', $xeroInvoiceIdAttributeId);
                $write->delete($tableSalesOrderVarchar, $condition);
                //xero_invoice_number - varchar
                $xeroInvoiceNumberAttribute = Mage::getSingleton('eav/config')->getAttribute($orderEntityType->getEntityTypeId(), 'xero_invoice_number');
                $xeroInvoiceNumberAttributeId = $xeroInvoiceNumberAttribute->getAttributeId();
                $condition = $write->quoteInto('attribute_id = ?', $xeroInvoiceNumberAttributeId);
                $write->delete($tableSalesOrderVarchar, $condition);
                //xero_last_validation_errors - varchar
                $xeroLastValidationErrorsAttribute = Mage::getSingleton('eav/config')->getAttribute($orderEntityType->getEntityTypeId(), 'xero_last_validation_errors');
                $xeroLastValidationErrorsAttributeId = $xeroLastValidationErrorsAttribute->getAttributeId();
                $condition = $write->quoteInto('attribute_id = ?', $xeroLastValidationErrorsAttributeId);
                $write->delete($tableSalesOrderVarchar, $condition);
            } else {
                $tableSalesOrder = Mage::getSingleton('core/resource')->getTableName('sales_flat_order');
                $write = Mage::getSingleton('core/resource')->getConnection('core_write');
                $write->update(
                        $tableSalesOrder,
                        array(
                            'xero_export_status' => Fooman_Connect_Model_XeroOauth::CA_XERO_STATUS_NOT_EXPORTED,
                            'xero_invoice_id' => new Zend_Db_Expr('null'),
                            'xero_invoice_number' => new Zend_Db_Expr('null'),
                            'xero_last_validation_errors' => new Zend_Db_Expr('null'),
                        )
                );
                //reset creditmemos
                $tableSalesCreditmemo = Mage::getSingleton('core/resource')->getTableName('sales_flat_creditmemo');
                $write->update(
                        $tableSalesCreditmemo,
                        array(
                            'xero_export_status' => Fooman_Connect_Model_XeroOauth::CA_XERO_STATUS_NOT_EXPORTED,
                            'xero_creditnote_id' => new Zend_Db_Expr('null'),
                            'xero_creditnote_number' => new Zend_Db_Expr('null'),
                            'xero_last_validation_errors' => new Zend_Db_Expr('null'),
                        )
                );

            }
        }
        catch (Exception $e ) {
            $this->_getSession()->addError($e->getMessage());
        }
        $this->_getSession()->addSuccess(Mage::helper('foomanconnect')->__('Successfully reset'));

        //go back to the order overview page
        $this->_redirect('adminhtml/xero/');
    }



    public function processAllAction() {
        $errors='';
        $successes='';

        //Get all unexported orders
        $orders = $this->_getXeroOauthModel()->getOrdersWithStatus();

        //loop through orders
        if ($orders->count()) {
            foreach ($orders as $order) {
                $orderIncrementId = $order->getIncrementId();
                $result = false;

                try {
                    //let's create the draft invoice
                    $result = $this->_getXeroOauthModel()->exportOrderToXero($order);
                }
                catch (Exception $e ) {
                    $errors = empty($errors)?$orderIncrementId.": ".$e->getMessage():$errors."<br/>".$orderIncrementId.": ".$e->getMessage();
                }
                if($result) {
                    $successes .=empty($successes)?$orderIncrementId:', '.$orderIncrementId;
                }

            }
        }else {
            $errors = Mage::helper('foomanconnect')->__('No Orders to export.');
        }

        //Add results to session
        if(!empty($errors)) {
            $this->_getSession()->addError($errors);
        }
        if(!empty($successes)) {
            $this->_getSession()->addSuccess(Mage::helper('foomanconnect')->__('Successfully exported').': '.$successes);
        }
        //go back to the order overview page
        $this->_redirect('adminhtml/xero/');
    }


    public function callbackAction() {
        try {
            $this->_initAction();

            $verifiedToken = array();
            $verifiedToken['oauth_token'] = $this->getRequest()->getParam('oauth_token');
            $verifiedToken['oauth_verifier'] = $this->getRequest()->getParam('oauth_verifier');

            $xeroRequestToken = $this->_session->getXeroRequestToken();
        } catch (Exception $e ) {
            $this->_session->addError($e->getMessage());
        }
        if (!empty($verifiedToken) && !empty($xeroRequestToken)) {
            try {
                $token = $this->_consumer->getAccessToken($verifiedToken, unserialize($xeroRequestToken));
                $this->_session->setXeroAccessToken(serialize($token));

                /**
                 * Now that we have an Access Token, we can discard the Request Token
                 */
                $this->_session->unsXeroRequestToken();
                $redirectUrl=$this->_session->getXeroBeforeAuthUrl();
                $this->_session->addSuccess(Mage::helper('foomanconnect')->__('Connection to Xero succeeded - you will have access for the next 30 minutes.'));

                /**
                 * With Access Token in hand, let's try accessing the client again
                 */
                $this->_redirectUrl($redirectUrl);
            } catch (Exception $e ) {
                $this->_session->addError($e->getMessage());
            }
        }else {
            $this->_session->addError(Mage::helper('foomanconnect')->__('Connection to Xero unsuccessful.'));
            $this->_redirect(Mage::getSingleton('admin/session')->getUser()->getStartupPageUrl());
        }
    }

    public function gridAction() {
        $this->loadLayout();
        $this->getResponse()->setBody(
                $this->getLayout()->createBlock('foomanconnect/adminhtml_xero_grid')->toHtml()
        );
    }

}