<?php
$installer = $this;
$installer->startSetup();


$installer->addAttribute("customer", "title",  array(
    "type"     => "varchar",
    "backend"  => "",
    "label"    => "Company Title/Position",
    "input"    => "text",
    "source"   => "",
    "visible"  => true,
    "required" => false,
    "default" => "",
    "frontend" => "",
    "unique"     => false,
    "note"       => ""

	));

        $attribute   = Mage::getSingleton("eav/config")->getAttribute("customer", "title");

        
$used_in_forms=array();

$used_in_forms[]="adminhtml_customer";
$used_in_forms[]="customer_account_create";
$used_in_forms[]="customer_account_edit";
        $attribute->setData("used_in_forms", $used_in_forms)
		->setData("is_used_for_customer_segment", true)
		->setData("is_system", 0)
		->setData("is_user_defined", 1)
		->setData("is_visible", 1)
		->setData("sort_order", 150)
		;
        $attribute->save();
	
	
	

$installer->addAttribute("customer", "license",  array(
    "type"     => "varchar",
    "backend"  => "",
    "label"    => "Business License",
    "input"    => "text",
    "source"   => "",
    "visible"  => true,
    "required" => false,
    "default" => "",
    "frontend" => "",
    "unique"     => false,
    "note"       => ""

	));

        $attribute   = Mage::getSingleton("eav/config")->getAttribute("customer", "license");

        
$used_in_forms=array();

$used_in_forms[]="adminhtml_customer";
$used_in_forms[]="customer_account_create";
$used_in_forms[]="customer_account_edit";
        $attribute->setData("used_in_forms", $used_in_forms)
		->setData("is_used_for_customer_segment", true)
		->setData("is_system", 0)
		->setData("is_user_defined", 1)
		->setData("is_visible", 1)
		->setData("sort_order", 160)
		;
        $attribute->save();
	
	
	

$installer->addAttribute("customer", "profession",  array(
    "type"     => "varchar",
    "backend"  => "",
    "label"    => "Profession",
    "input"    => "text",
    "source"   => "",
    "visible"  => true,
    "required" => false,
    "default" => "",
    "frontend" => "",
    "unique"     => false,
    "note"       => ""

	));

        $attribute   = Mage::getSingleton("eav/config")->getAttribute("customer", "profession");

        
$used_in_forms=array();

$used_in_forms[]="adminhtml_customer";
$used_in_forms[]="customer_account_create";
$used_in_forms[]="customer_account_edit";
        $attribute->setData("used_in_forms", $used_in_forms)
		->setData("is_used_for_customer_segment", true)
		->setData("is_system", 0)
		->setData("is_user_defined", 1)
		->setData("is_visible", 1)
		->setData("sort_order", 170)
		;
        $attribute->save();
	
	
	

$installer->addAttribute("customer", "findout",  array(
    "type"     => "text",
    "backend"  => "",
    "label"    => "How did you find out about us?",
    "input"    => "textarea",
    "source"   => "",
    "visible"  => true,
    "required" => false,
    "default" => "",
    "frontend" => "",
    "unique"     => false,
    "note"       => ""

	));

        $attribute   = Mage::getSingleton("eav/config")->getAttribute("customer", "findout");

        
$used_in_forms=array();

$used_in_forms[]="adminhtml_customer";
$used_in_forms[]="customer_account_create";
        $attribute->setData("used_in_forms", $used_in_forms)
		->setData("is_used_for_customer_segment", true)
		->setData("is_system", 0)
		->setData("is_user_defined", 1)
		->setData("is_visible", 1)
		->setData("sort_order", 180)
		;
        $attribute->save();
        
        
$installer->addAttribute("customer", "faxnumber",  array(
    "type"     => "text",
    "backend"  => "",
    "label"    => "Fax Number",
    "input"    => "text",
    "source"   => "",
    "visible"  => true,
    "required" => false,
    "default" => "",
    "frontend" => "",
    "unique"     => false,
    "note"       => ""

	));

        $attribute   = Mage::getSingleton("eav/config")->getAttribute("customer", "faxnumber");

        
$used_in_forms=array();

$used_in_forms[]="adminhtml_customer";
$used_in_forms[]="customer_account_create";
        $attribute->setData("used_in_forms", $used_in_forms)
		->setData("is_used_for_customer_segment", true)
		->setData("is_system", 0)
		->setData("is_user_defined", 1)
		->setData("is_visible", 1)
		->setData("sort_order", 190)
		;
        $attribute->save();
	
	
	
$installer->endSetup();
	 