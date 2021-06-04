<?php
/**
 * Install Data.
 */
namespace DetectorDist\Orders\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
 
class UpgradeSchema implements UpgradeSchemaInterface
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
     * 
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        $eavSetup = $this->eavSetupFactory->create();
        //$eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);
        $entityType = $eavSetup->getEntityTypeId('catalog_product');
        
 
        if(!$context->getVersion()) {
            //no previous version found, installation, InstallSchema was just executed
            //be careful, since everything below is true for installation !
        }
 
        //code to upgrade to 1.0.1
        if (version_compare($context->getVersion(), '0.0.1') < 0) {
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
 
        //code to upgrade to 1.0.2
        if (version_compare($context->getVersion(), '0.0.2') < 0) {
            $eavSetup->updateAttribute(
                $entityType,
                'extra_items_list',
                'backend_model',
                'DetectorDist\Orders\Model\Backend\ExtraItems'
            );
        }
 
        $setup->endSetup();
    }
}