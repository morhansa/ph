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

use Magento\Customer\Model\Address as MagentoAddress;
use MagoArab\PhoneMailer\Helper\Config;
use Psr\Log\LoggerInterface;

class Address extends MagentoAddress
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Execute additional logic after address save
     *
     * @return $this
     */
    public function afterSave()
    {
        try {
            // Get dependencies via object manager since we can't use constructor in models
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            
            if (!$this->config) {
                $this->config = $objectManager->get(Config::class);
            }
            
            if (!$this->logger) {
                $this->logger = $objectManager->get(LoggerInterface::class);
            }
            
            // Check if module is enabled
            if ($this->config->isEnabled()) {
                // Get the telephone number from address
                $telephone = $this->getTelephone();
                
                // Get the customer
                $customer = $this->getCustomer();
                
                // If telephone is available and we have a customer, update email if needed
                if ($telephone && $customer && $customer->getId()) {
                    // Check if this is a primary address (billing or shipping)
                    $isPrimary = $this->getIsPrimaryBilling() || $this->getIsPrimaryShipping();
                    
                    if ($isPrimary) {
                        // Load the most recent customer data
                        $customerModel = $objectManager->create(\Magento\Customer\Model\Customer::class);
                        $customerModel->load($customer->getId());
                        
                        // Check if customer has a valid email or if it's a generated one
                        $email = $customerModel->getEmail();
                        $isGeneratedEmail = false;
                        
                        if ($email && strpos($email, '@') !== false) {
                            $parts = explode('@', $email);
                            // If username part is a phone number, it's a generated email
                            if (preg_match('/^[0-9+]+$/', $parts[0])) {
                                $isGeneratedEmail = true;
                            }
                        }
                        
                        // If the email is generated, update it with the new phone number
                        if ($isGeneratedEmail) {
                            // Get EmailGenerator helper
                            $emailGenerator = $objectManager->get(\MagoArab\PhoneMailer\Helper\EmailGenerator::class);
                            
                            // Generate new email
                            $newEmail = $emailGenerator->generateEmailFromPhone($telephone, $customerModel->getStoreId());
                            
                            // Update customer email if it changed
                            if ($newEmail !== $email) {
                                $customerModel->setEmail($newEmail);
                                $customerModel->save();
                                $this->logger->info('PhoneMailer: Updated customer email to ' . $newEmail . ' based on address phone change');
                            }
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('PhoneMailer: Error in Address afterSave: ' . $e->getMessage());
            }
        }
        
        return parent::afterSave();
    }
}