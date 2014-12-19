<?php


class Nick_Ordercorrection_Block_Restore extends Mage_Adminhtml_Block_Widget
{
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('ordercorrection/restore.phtml');
    }

}