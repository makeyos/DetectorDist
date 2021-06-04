<?php
/**
 * ExtraItems.php
 */
namespace DetectorDist\Orders\Model\Backend;

use \Magento\Eav\Model\Entity\Attribute\Backend\AbstractBackend;
use Magento\Framework\Exception\LocalizedException;

class ExtraItems extends AbstractBackend
{
    /**
     * Validate object
     *
     * @param \Magento\Framework\DataObject $object
     * @return bool
     * @throws LocalizedException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function validate($object)
    {
        $attribute = $this->getAttribute();
        $attrCode = $attribute->getAttributeCode();
        $value = $object->getData($attrCode);
        
        if(empty($value)) {
            return TRUE;
        }
        
        $valueArr = explode(',', $value);
        
        foreach($valueArr as $val) {
            if($this->_skuExists($val) === FALSE) {
                throw new LocalizedException(__('SKU "%1" must exist', $val));
            }
        }
        
        return parent::validate($object);
    }
    
    /**
     * @param   string
     * @return  bool
     */
    protected function _skuExists($sku)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $product = $objectManager->get('Magento\Catalog\Model\Product');

        if($product->getIdBySku($sku)) {
            return TRUE;  
        }
        
        return FALSE;
    }
}