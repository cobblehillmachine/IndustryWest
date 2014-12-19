<?php

class Fooman_Connect_Model_XeroApi
{

    const URL_ROOT = 'https://api.xero.com';
    const API_VERSION = 'version/2';

    //Entry points for Fooman Connect: Xero
    const CA_BASE_URL='/api.xro/2.0/';
    const CA_INVOICE_PATH ="Invoice";
    const CA_INVOICES_PATH ="Invoices";
    const CA_CREDITNOTE_PATH = "CreditNotes";
    const CA_CONTACTS_PATH = "Contacts";
    const CA_TRACKING_PATH ="TrackingCategories";
    const CA_ACCOUNTS_PATH ="Accounts";
    const CA_TAXRATES_PATH ="TaxRates";
    const CA_ITEMS ="Items";
    const CA_XERO_INVOICE_LINK = "https://go.xero.com/AccountsReceivable/View.aspx?InvoiceID=";

    protected $_xeroApiClient;

    /**
     * return the current session of the admin user
     *
     * @param  void
     * @return Mage_Adminhtml_Model_Session
     */
    public function getSession()
    {
        return Mage::getSingleton('adminhtml/session');
    }


    /**
     * Get configuration settings to work with Xero's Oauth
     *
     * @param   void
     * @return array
     * @throws Exception
     */
    public function getConfiguration()
    {
        try {
            $rsaPrivateKey = new Zend_Crypt_Rsa_Key_Private(Mage::helper('core')->decrypt(
                Mage::helper('foomanconnect')->getMageStoreConfig('privatekey')
            ), Mage::helper('core')->decrypt(Mage::helper('foomanconnect')->getMageStoreConfig('privatekeypassword')));
        } catch (Exception $e) {
            $this->getSession()->addError(
                Mage::helper('foomanconnect')->__('Private Key error: %s' . $e->getMessage() . openssl_error_string())
            );
            throw new Exception(Mage::helper('foomanconnect')->__(
                'Private Key error: %s' . $e->getMessage() . openssl_error_string()
            ));
        }
        return array(
            'useragent' => 'Fooman Magento',
            'siteUrl' => self::URL_ROOT,
            'signatureMethod' => 'RSA-SHA1',
            'consumerKey' => Mage::helper('core')->decrypt(
                Mage::helper('foomanconnect')->getMageStoreConfig('consumerkey')
            ),
            'consumerSecret' => Mage::helper('core')->decrypt(
                Mage::helper('foomanconnect')->getMageStoreConfig('consumersecret')
            ),
            'requestTokenUrl' => self::URL_ROOT . '/oauth/RequestToken',
            'accessTokenUrl' => self::URL_ROOT . '/oauth/AccessToken',
            'authorizeUrl' => self::URL_ROOT . '/oauth/Authorize',
            'rsaPrivateKey' => $rsaPrivateKey
        );
    }

    public function getXeroApiClient() {
        if(!$this->_xeroApiClient){
            try{
                $configuration =  $this->getConfiguration();
                $xeroAccessToken= new Zend_Oauth_Token_Access();
                $xeroAccessToken->setToken($configuration['consumerKey']);
                $xeroAccessToken->setTokenSecret($configuration['consumerSecret']);
                $this->_xeroApiClient = $xeroAccessToken->getHttpClient($configuration);
                $this->_xeroApiClient->setHeaders('Accept','application/json');
            }catch (Exception $e){
                Mage::throwException(Mage::helper('foomanconnect')->__('Oauth error: %s'),$e->getMessage());
            }
        }
        return $this->_xeroApiClient;
    }

    /**
     *
     * construct complete URL from given entrypoint
     *
     * @param string $endpoint
     *
     * @return string
     */
    public function getApiUrl($endpoint = '')
    {
        return self::URL_ROOT . self::CA_BASE_URL. $endpoint;
    }

    public function sendData($entryPoint, $method = Zend_Http_Client::GET, $data = false)
    {
        $client = $this->getXeroApiClient()->resetParameters();
        $client->setMethod($method);
        $client->setUri($this->getApiUrl($entryPoint));
        $response = $client->request();
        return $this->handleResponse($response);
    }

    public function getAccounts()
    {
        $result = $this->sendData(self::CA_ACCOUNTS_PATH);
        if (isset ($result['Accounts'])) {
            return $result['Accounts'];
        }
    }

    public function getTaxRates()
    {
        $result = $this->sendData(self::CA_TAXRATES_PATH);
        if (isset ($result['TaxRates'])) {
            return $result['TaxRates'];
        }
    }

    /**
     * error checking of response returned by server
     *
     * @param Zend_Http_Response $response
     * @return array
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
        }
        elseif(strpos($responseBody,'oauth_problem') === 0 ) {
            $this->getSession()->unsXeroAccessToken();
            $this->getSession()->addError(Mage::helper('foomanconnect')->__('Oauth error: %s',$responseBody));
        }
        try {
            $result = json_decode($responseBody,true);
        }catch (Exception $e) {
            Mage::log($responseBody,null,self::CA_LOG_FILENAME);
            Mage::log($e->getMessage(),null,self::CA_LOG_FILENAME);
            throw new Exception("Result is not a valid response.");
            return false;
        }
        return $result;
    }

}