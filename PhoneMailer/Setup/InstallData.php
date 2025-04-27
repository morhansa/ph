<?php
/**
 * MagoArab_PhoneMailer
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category   MagoArab
 * @package    MagoArab_PhoneMailer
 * @copyright  Copyright © 2025 MagoArab (https://www.magoarab.com)
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

namespace MagoArab\PhoneMailer\Setup;

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Customer\Model\Customer;
use Magento\Eav\Model\Entity\Attribute\SetFactory as AttributeSetFactory;
use Magento\Framework\DB\Ddl\Table;

class InstallData implements InstallDataInterface
{
    /**
     * @var CustomerSetupFactory
     */
    private $customerSetupFactory;

    /**
     * @var AttributeSetFactory
     */
    private $attributeSetFactory;

    /**
     * InstallData constructor.
     *
     * @param CustomerSetupFactory $customerSetupFactory
     * @param AttributeSetFactory $attributeSetFactory
     */
    public function __construct(
        CustomerSetupFactory $customerSetupFactory,
        AttributeSetFactory $attributeSetFactory
    ) {
        $this->customerSetupFactory = $customerSetupFactory;
        $this->attributeSetFactory = $attributeSetFactory;
    }

    /**
     * Install data
     *
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        // Setup default config
        $this->setupDefaultConfig($setup);

        $setup->endSetup();
    }

    /**
     * Setup default config
     *
     * @param ModuleDataSetupInterface $setup
     * @return void
     */
    private function setupDefaultConfig(ModuleDataSetupInterface $setup)
    {
        // Default configuration values
        $configData = [
            'phonemail/general/enabled' => 1,
            'phonemail/general/domain_mode' => 'auto',
            'phonemail/whatsapp/enabled' => 0,
            'phonemail/whatsapp/templates' => json_encode([
                'order_confirmation' => 'Thank you for your order #{{order_id}}. Your total is {{total}}. We will process your order shortly.',
                'shipping_confirmation' => 'Good news! Your order #{{order_id}} has been shipped. Track your package with number {{tracking_number}}.',
                'order_delivered' => 'Your order #{{order_id}} has been delivered. Thank you for shopping with {{store_name}}!',
                'welcome' => 'Welcome to {{store_name}}, {{customer_name}}! Thank you for registering.'
            ])
        ];
        
        // Insert default config values
        $configTable = $setup->getTable('core_config_data');
        
        foreach ($configData as $path => $value) {
            // Check if config already exists
            $select = $setup->getConnection()->select()
                ->from($configTable)
                ->where('path = ?', $path)
                ->where('scope = ?', 'default')
                ->where('scope_id = ?', 0);
            
            $row = $setup->getConnection()->fetchRow($select);
            
            if (!$row) {
                // Insert new config value
                $setup->getConnection()->insert(
                    $configTable,
                    [
                        'path' => $path,
                        'value' => $value,
                        'scope' => 'default',
                        'scope_id' => 0
                    ]
                );
            }
        }
    }
}