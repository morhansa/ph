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

namespace MagoArab\PhoneMailer\Model\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use MagoArab\PhoneMailer\Api\WhatsappServiceInterface;
use MagoArab\PhoneMailer\Helper\Config;
use Psr\Log\LoggerInterface;

class CustomerRegisterSuccess implements ObserverInterface
{
    /**
     * @var WhatsappServiceInterface
     */
    protected $whatsappService;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * CustomerRegisterSuccess constructor.
     *
     * @param WhatsappServiceInterface $whatsappService
     * @param Config $config
     * @param LoggerInterface $logger
     */
    public function __construct(
        WhatsappServiceInterface $whatsappService,
        Config $config,
        LoggerInterface $logger
    ) {
        $this->whatsappService = $whatsappService;
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * Observer for customer_register_success event
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        try {
            // Check if module and WhatsApp integration are enabled
            if (!$this->config->isEnabled() || !$this->config->isWhatsappEnabled()) {
                return;
            }

            /** @var \Magento\Customer\Model\Customer $customer */
            $customer = $observer->getEvent()->getCustomer();
            
            if (!$customer || !$customer->getId()) {
                return;
            }
            
            // Get phone number
            $telephone = $this->getCustomerPhone($customer);
            
            if (!$telephone) {
                $this->logger->warning('PhoneMailer: Cannot send WhatsApp welcome message - no phone number for customer ID ' . $customer->getId());
                return;
            }
            
            // Prepare message parameters
            $params = [
                'customer_name' => $customer->getFirstname() . ' ' . $customer->getLastname(),
                'store_name' => $customer->getStore()->getFrontendName(),
                'customer_id' => $customer->getId(),
                'customer_email' => $customer->getEmail()
            ];
            
            // Get welcome message template
            $templates = $this->config->getWhatsappTemplates($customer->getStoreId());
            $message = $templates['welcome'] ?? 'Welcome to {{store_name}}, {{customer_name}}! Thank you for registering.';
            
            // Send WhatsApp notification
            $result = $this->whatsappService->sendMessage($telephone, $message, $params, $customer->getStoreId());
            
            if ($result) {
                $this->logger->info('PhoneMailer: WhatsApp welcome message sent to customer ID ' . $customer->getId());
            } else {
                $this->logger->warning('PhoneMailer: Failed to send WhatsApp welcome message to customer ID ' . $customer->getId());
            }
        } catch (\Exception $e) {
            $this->logger->error('PhoneMailer: Error sending WhatsApp welcome message: ' . $e->getMessage());
        }
    }

    /**
     * Get customer phone number
     *
     * @param \Magento\Customer\Model\Customer $customer
     * @return string|null
     */
    private function getCustomerPhone($customer)
    {
        // Try to get from addresses
        $address = $customer->getPrimaryBillingAddress();
        if ($address && $address->getTelephone()) {
            return $address->getTelephone();
        }
        
        $address = $customer->getPrimaryShippingAddress();
        if ($address && $address->getTelephone()) {
            return $address->getTelephone();
        }
        
        // Try to get from custom attributes if available
        if ($customer->getCustomAttributes() && $customer->getCustomAttribute('telephone')) {
            return $customer->getCustomAttribute('telephone')->getValue();
        }
        
        // Try to extract from email if it's a generated one
        $email = $customer->getEmail();
        if ($email && strpos($email, '@') !== false) {
            $parts = explode('@', $email);
            if (preg_match('/^[0-9+]+$/', $parts[0])) {
                return $parts[0];
            }
        }
        
        return null;
    }
}