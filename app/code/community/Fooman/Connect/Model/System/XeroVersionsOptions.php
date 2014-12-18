<?php

/*
 * @author     Kristof Ringleff
 * @package    Fooman_Connect
 * @copyright  Copyright (c) 2010 Fooman Limited (http://www.fooman.co.nz)
 */

class Fooman_Connect_Model_System_XeroVersionsOptions extends Fooman_Connect_Model_XeroOauth {

    public function toOptionArray() {
        $returnArray = array();
        foreach ($this->xeroVersionDefaults as $version) {
            $returnArray[]=array('value' =>$version['code'],'label'=>$version['name']);
        }
        return $returnArray;
    }

}
