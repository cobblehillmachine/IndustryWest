<?php
//http://serv.industrywest.com/lightspeedcontent/hole/index
require_once($_SERVER['DOCUMENT_ROOT'] . "/home-fillers.php");


class Delorum_Lightspeedcontent_HoleController extends Mage_Core_Controller_Front_Action 
{
	public function IndexAction() 
	{
		$this->loadLayout();
		
		// 'jssession' => 'ppcache = "Cached"; var sessionid = "' . Mage::getModel("core/session")->getEncryptedSessionId() . '";'
		
		//Fills hole with real content by calling specific blocks by name
		 $content = array(
						'shopcart' => '<!-- PUNCHED -->' .$this->getLayout()->getBlock('cart_sidebar')->toHtml(),
		 				'recently' => '<!-- PUNCHED -->' .$this->getLayout()->getBlock('productviewed')->toHtml(),						
						'toplinks' => '<!-- PUNCHED -->' .$this->getLayout()->getBlock('top.links')->toHtml(),
						'h1' => '<!-- PUNCHED --><img src="/media/features/new' . gethomeimage(1) . '.jpg"  />',						
						'h2' => '<!-- PUNCHED --><img src="/media/features/shop' . gethomeimage(2) . '.jpg"  />',
						'h3' => '<!-- PUNCHED --><img src="/media/features/comm' . gethomeimage(3) . '.jpg"  />',
						'h4' => '<!-- PUNCHED --><img src="/media/features/sale' . gethomeimage(4) . '.jpg"  />',
						'h5' => '<!-- PUNCHED --><img src="/media/features/chairs' . gethomeimage(5) . '.jpg"  />',
						'h6' => '<!-- PUNCHED --><img src="/media/features/stools' . gethomeimage(6) . '.jpg"  />',																																				
						'jssession' => "ppcache = 'Cached';\nvar sessionid = \"".  Mage::getModel("core/session")->getEncryptedSessionId() . "\"". systemvars()
						
		 		 	);
		
		echo Zend_Json::encode($content);
	}

	
}
