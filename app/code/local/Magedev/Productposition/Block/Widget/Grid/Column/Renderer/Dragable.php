<?php
/**
 * Set product drag icon
 * @category   Product Position
 * @package    Magedev_Productposition
 * @author     Mage Developer(mage.devloper@gmail.com)
 */
class Magedev_Productposition_Block_Widget_Grid_Column_Renderer_Dragable extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    protected $_values;

    /**
     * Renders grid column
     *
     * @param   Varien_Object $row
     * @return  string
     */
    public function render(Varien_Object $row)
    {   
		return '<img src="'.$this->getSkinUrl('images/DragIcon.png').'" style="cursor:move"/>';
    }
}
