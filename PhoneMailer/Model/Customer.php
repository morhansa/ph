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

namespace MagoArab\PhoneMailer\Model;

use Magento\Customer\Model\Customer as MagentoCustomer;
use MagoArab\PhoneMailer\Helper\EmailGenerator;
use MagoArab\PhoneMailer\Helper\Config;
use Psr\Log\LoggerInterface;

class Customer extends MagentoCustomer
{
    /**
     * @var EmailGenerator
     */
    protected $emailGenerator;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Auto-generate email from phone before save
     *
     * @return $this
     */
    public function beforeSave()
    {
        // Get dependencies via object manager since we can't use constructor in models
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        
        if (!$this->emailGenerator) {
            $this->emailGenerator = $objectManager->get(EmailGenerator::class);
        }
        
        if (!$this->config) {
            $this->config = $objectManager->get(Config::class);
        }
        
        if (!$this->logger) {
            $this->logger = $objectManager->get(LoggerInterface::class);
        }
        
        try {
            // Check if module is enabled
            if ($this->config->isEnabled()) {
                // Check if customer has a valid email
                if (!$this->getEmail() || !filter_var($this->getEmail(), FILTER_VALIDATE_EMAIL)) {
                    // Get the telephone number from address
                    $telephone = $this->getTelephone();
                    
                    // If telephone is available, generate email
                    if ($telephone) {
                        $email = $this->emailGenerator->generateEmailFromPhone($telephone, $this->getStoreId());
                        $this->setEmail($email);
                        
                        $this->logger->info('PhoneMailer: Generated email ' . $email . ' for customer ' . $this->getId());
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('PhoneMailer: Error generating email in Customer model: ' . $e->getMessage());
        }
        
        return parent::beforeSave();
    }

    /**
     * Get telephone from primary address
     *
     * @return string|null
     */
    public function getTelephone()
    {
        $telephone = null;
        
        // Try to get phone from addresses
        $address = $this->getPrimaryBillingAddress();
        if ($address && $address->getTelephone()) {
            $telephone = $address->getTelephone();
        }
        
        if (!$telephone) {
            $address = $this->getPrimaryShippingAddress();
            if ($address && $address->getTelephone()) {
                $telephone = $address->getTelephone();
            }
        }
        
        // Try to get from custom attributes if available
        if (!$telephone && $this->getCustomAttributes() && $this->getCustomAttribute('telephone')) {
            $telephone = $this->getCustomAttribute('telephone')->getValue();
        }
        
        return $telephone;
    }
}