<?php
/**
 * Set product image thumbnail
 * @category   Product Position
 * @package    Magedev_Productposition
 * @author     Mage Developer(mage.devloper@gmail.com)
 */
 
class Magedev_Productposition_Block_Widget_Grid_Column_Renderer_Thumbnail extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
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
		if ($row->getImage() != 'no_selection') {
			$imgUrl = Mage::helper('productposition')->getImageUrl($row->getImage());
		} else {
			$imgUrl = Mage::getDesign()->getSkinUrl('images/catalog/product/placeholder/image.jpg');
			return "<img src=\"$imgUrl\" width=\"75\" height=\"75\"/>";
		}
		
		$imgUrl = $this->helper('catalog/image')->init($row,'image')->resize(75); // RETURN RESIZED, SPEED THIS UP!!! UPDATED MTU
		return "<img src=\"$imgUrl\" width=\"75\" height=\"75\"/>";
    }
}
