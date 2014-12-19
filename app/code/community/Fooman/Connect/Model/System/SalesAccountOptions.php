<?php

/*
 * @author     Kristof Ringleff
 * @package    Fooman_Connect
 * @copyright  Copyright (c) 2010 Fooman Limited (http://www.fooman.co.nz)
 */

class Fooman_Connect_Model_System_SalesAccountOptions extends Fooman_Connect_Model_System_AbstractAccounts {

    public function toOptionArray($includeGlobal = false)
    {
        $returnArray = array();
        try {
            $accounts = $this->getXeroAccounts();
            $wantedAccountTypes = array('REVENUE','SALES');
            if ($includeGlobal) {
                $returnArray[] = array(
                    'value' => '0',
                    'label' => Mage::helper('foomanconnect')->__('Use Global Setting')
                );
            }
            foreach ($accounts as $account) {
                if(in_array($account['Type'], $wantedAccountTypes)){
                    $returnArray[] = array(
                        'value' => $account['Code'],
                        'label' => '[' . $account['Code'] . "] " . substr($account['Name'],0,30)
                    );
                }
            }
        } catch (Exception $e){
            $returnArray[]=array('value' =>'0','label'=> $e->getMessage());
        }
        return $returnArray;
    }
}

