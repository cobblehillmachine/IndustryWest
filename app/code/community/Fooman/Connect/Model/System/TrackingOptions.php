<?php

/*
 * @author     Kristof Ringleff
 * @package    Fooman_Connect
 * @copyright  Copyright (c) 2010 Fooman Limited (http://www.fooman.co.nz)
 */

class Fooman_Connect_Model_System_TrackingOptions extends Fooman_Connect_Model_XeroOauth {


    public function toOptionArray() {
        $returnArray=array();
        if ($this->isConfigured()) {
            $_session = $this->getSession();
            if($this->initConsumer() && Mage::helper('foomanconnect')->getMageStoreConfig('xeroenabled')) {
                $xeroAccounts=array();

                //offer none as option
                $returnArray[]=array('value' =>'','label'=> 'none');

                $token =$this->getXeroAccessToken();
                //Check for Access Token / but we really should already have one after initConsumer
                if ($token) {
                    try {
                        $client = $token->getHttpClient($this->getConfiguration());
                        $client->setUri(parent::URL_ROOT.parent::CA_TRACKING_PATH);
                        $client->setMethod(Zend_Http_Client::GET);
                        $response = $client->request();
                    } catch (Exception $e) {
                        $_session->addError($e->getMessage());
                    }
                    //connect to Xero Server and get tracking options
                    try {
                        //Check if the response contains an error message, for example access token has expired
                        $resultXml = $this->handleResponse($response);

                        if (!($resultXml->Status=="OK")) {
                            throw new Exception("Please use a valid ApiKey and Save Config");
                        }else {
                            //we have been successful
                            if($resultXml->TrackingCategories){
                                foreach ($resultXml->TrackingCategories->TrackingCategory as $category) {
                                    $categoryName = (string)$category->Name;
                                    foreach ($category->Options as $options) {
                                        foreach ($options->Option as $option) {
                                            $returnArray[]=array('value' =>(string)$category->TrackingCategoryID.'|'.$categoryName.'|'.(string)$option->Name,'label'=> '['.$categoryName.'] '.(string)$option->Name);
                                        }
                                    }
                                }
                            }
                        }
                    } catch (Exception $e) {
                        //display the error message in the dropdown
                        $returnArray[]=array('value' =>'0','label'=> $e->getMessage());
                    }
                }

            }
        }else {
            $returnArray[]=array('value' =>'0','label'=>Mage::helper('foomanconnect')->__('Please configure and enable the integration above and save config.'));
        }

        return $returnArray;
    }

}
