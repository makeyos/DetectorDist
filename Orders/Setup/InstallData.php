<?php
/**
 * Install Data.
 */
namespace DetectorDist\Orders\Setup;

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
* @codeCoverageIgnore
*/
class InstallData implements InstallDataInterface
{
    /**
     * Eav setup factory
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * Init
     * @param CategorySetupFactory $categorySetupFactory
     */
    public function __construct(\Magento\Eav\Setup\EavSetupFactory $eavSetupFactory)
    {
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $eavSetup = $this->eavSetupFactory->create();
        $entityType = $eavSetup->getEntityTypeId('catalog_product');
        
        /**
         * Attribute already exists, we need to update the backend model.
         */
        if (version_compare($context->getVersion(), '1.0.1', '<')) {
             /*$eavSetup->updateAttribute(
                $entityType,
                'extra_items_list',
                'backend_model',
                'DetectorDist\Orders\Model\Backend\ExtraItems'
            );*/
        }
        
        /*$eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'extra_items_list',
            [
                'type' => 'text',
                'backend' => '',
                'frontend' => '',
                'label' => 'Extra Items',
                'input' => 'text',
                'class' => '',
                'source' => '',
                'backend' => 'DetectorDist\Orders\Model\Backend\ExtraItems',
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible' => true,
                'required' => false,
                'user_defined' => false,
                'default' => '',
                'searchable' => false,
                'filterable' => false,
                'comparable' => false,
                'visible_on_front' => false,
                'used_in_product_listing' => false,
                'unique' => false,
                'apply_to' => ''
            ]
        );*/
    }
}