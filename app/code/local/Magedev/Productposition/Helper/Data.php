<?php
/**
 * Set product image
 * @category   Product image
 * @package    Magedev_Productposition
 * @author     Mage Developer(mage.devloper@gmail.com)
 */
 
class Magedev_Productposition_Helper_Data extends Mage_Core_Helper_Abstract 
{
   
    public function getImageUrl($image)
    {
        $productImageUrl = false;
        $productImageUrl = Mage::getBaseUrl('media').'catalog/product'. $image;
        return $productImageUrl;
    }
  
    
    public function getFileExists($image)
    {
        $isFileExists = false;
        $isFileExists = file_exists('media/catalog/product'. $image);
        return $isFileExists;
    }
}
?>