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

class OrderPlaceAfter implements ObserverInterface
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
     * OrderPlaceAfter constructor.
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
     * Observer for sales_order_place_after event
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

            /** @var \Magento\Sales\Model\Order $order */
            $order = $observer->getEvent()->getOrder();
            
            if (!$order || !$order->getId()) {
                return;
            }
            
            // Get the customer's phone number
            $phone = $this->getPhoneFromOrder($order);
            
            if (!$phone) {
                $this->logger->warning('PhoneMailer: Cannot send WhatsApp notification - no phone number in order #' . $order->getIncrementId());
                return;
            }
            
            // Prepare message parameters
            $params = $this->prepareMessageParams($order);
            
            // Get message template
            $templates = $this->config->getWhatsappTemplates($order->getStoreId());
            $message = $templates['order_confirmation'] ?? 'Thank you for your order #{{order_id}}. Your total is {{total}}. We will process your order shortly.';
            
            // Send WhatsApp notification
            $result = $this->whatsappService->sendMessage($phone, $message, $params, $order->getStoreId());
            
            if ($result) {
                $this->logger->info('PhoneMailer: WhatsApp notification sent for order #' . $order->getIncrementId());
            } else {
                $this->logger->warning('PhoneMailer: Failed to send WhatsApp notification for order #' . $order->getIncrementId());
            }
        } catch (\Exception $e) {
            $this->logger->error('PhoneMailer: Error sending WhatsApp notification: ' . $e->getMessage());
        }
    }

    /**
     * Get phone number from order
     *
     * @param \Magento\Sales\Model\Order $order
     * @return string|null
     */
    protected function getPhoneFromOrder($order)
    {
        // Try billing address first
        $phone = null;
        
        if ($order->getBillingAddress() && $order->getBillingAddress()->getTelephone()) {
            $phone = $order->getBillingAddress()->getTelephone();
        }
        
        // If no phone in billing, try shipping
        if (!$phone && $order->getShippingAddress() && $order->getShippingAddress()->getTelephone()) {
            $phone = $order->getShippingAddress()->getTelephone();
        }
        
        // Clean phone number (remove spaces, dashes, etc.)
        if ($phone) {
            return preg_replace('/[^0-9+]/', '', $phone);
        }
        
        return null;
    }

    /**
     * Prepare message parameters
     *
     * @param \Magento\Sales\Model\Order $order
     * @return array
     */
    protected function prepareMessageParams($order)
    {
        // Format currency
        $formattedTotal = $order->formatPrice($order->getGrandTotal());
        
        // Get customer name
        $customerName = $order->getCustomerFirstname() . ' ' . $order->getCustomerLastname();
        if (trim($customerName) == ' ') {
            $customerName = $order->getBillingAddress()->getFirstname() . ' ' . $order->getBillingAddress()->getLastname();
        }
        
        // Prepare parameters for message template
        return [
            'order_id' => $order->getIncrementId(),
            'customer_name' => $customerName,
            'total' => $formattedTotal,
            'store_name' => $order->getStore()->getFrontendName(),
            'currency' => $order->getOrderCurrencyCode(),
            'payment_method' => $order->getPayment() ? $order->getPayment()->getMethodInstance()->getTitle() : 'Unknown',
            'items_count' => $order->getTotalItemCount() ?: count($order->getAllItems()),
            'shipping_method' => $order->getShippingDescription() ?: 'Standard Shipping',
            'shipping_address' => $this->formatAddress($order->getShippingAddress())
        ];
    }

    /**
     * Format address for message
     *
     * @param \Magento\Sales\Model\Order\Address|null $address
     * @return string
     */
    protected function formatAddress($address)
    {
        if (!$address) {
            return 'N/A';
        }
        
        $street = is_array($address->getStreet()) ? implode(', ', $address->getStreet()) : $address->getStreet();
        
        return implode(', ', array_filter([
            $street,
            $address->getCity(),
            $address->getRegion(),
            $address->getPostcode(),
            $address->getCountryId()
        ]));
    }
}