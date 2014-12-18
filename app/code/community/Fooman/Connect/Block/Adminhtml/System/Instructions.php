<?php

/*
 * @author     Kristof Ringleff
 * @package    Fooman_Connect
 * @copyright  Copyright (c) 2010 Fooman Limited (http://www.fooman.co.nz)
 */

class Fooman_Connect_Block_Adminhtml_System_Instructions extends Mage_Adminhtml_Block_System_Config_Form_Fieldset {

    public function render(Varien_Data_Form_Element_Abstract $element) {

        $html='';
        $html.='<tr><td colspan="2"><div class="box">';
        $html.=Mage::helper('foomanconnect')->__('');
        $html.=Mage::helper('foomanconnect')->__('Callback Url')."<br/>";
        $html.=Mage::helper('adminhtml')->getUrl('adminhtml/xero/callback', array('_secure' => true, '_nosecret' => true));
        $html.='<p class="nm"><small>';
        $html.= Mage::helper('foomanconnect')->__('Please copy and paste the above Url into Xero when setting up.');
        $html.='</small></p>';
        $html.='</div></td></tr>';
        return $html;
    }

}