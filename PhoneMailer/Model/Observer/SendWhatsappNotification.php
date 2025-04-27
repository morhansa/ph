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

class SendWhatsappNotification implements ObserverInterface
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
     * SendWhatsappNotification constructor.
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
     * Execute observer for custom events that need WhatsApp notifications
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

            $event = $observer->getEvent()->getName();
            
            switch ($event) {
                case 'sales_order_shipment_save_after':
                    $this->processShipmentNotification($observer);
                    break;
                
                case 'sales_order_invoice_save_after':
                    $this->processInvoiceNotification($observer);
                    break;
                
                case 'sales_order_status_history_save_after':
                    $this->processStatusHistoryNotification($observer);
                    break;
            }
        } catch (\Exception $e) {
            $this->logger->error('PhoneMailer: Error in SendWhatsappNotification observer: ' . $e->getMessage());
        }
    }

    /**
     * Process shipment notification
     *
     * @param Observer $observer
     * @return void
     */
    private function processShipmentNotification(Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order\Shipment $shipment */
        $shipment = $observer->getEvent()->getShipment();
        if (!$shipment) {
            return;
        }

        $order = $shipment->getOrder();
        if (!$order) {
            return;
        }
        
        // Get tracking numbers
        $trackingNumbers = [];
        foreach ($shipment->getAllTracks() as $track) {
            $trackingNumbers[] = $track->getTrackNumber();
        }
        
        $phone = $this->getPhoneFromOrder($order);
        if (!$phone) {
            $this->logger->warning('PhoneMailer: Cannot send shipment WhatsApp notification - no phone number in order #' . $order->getIncrementId());
            return;
        }
        
        // Prepare message parameters
        $params = [
            'order_id' => $order->getIncrementId(),
            'customer_name' => $order->getCustomerName() ?: 'Customer',
            'tracking_number' => !empty($trackingNumbers) ? implode(', ', $trackingNumbers) : 'N/A',
            'store_name' => $order->getStore()->getFrontendName(),
            'shipment_id' => $shipment->getIncrementId()
        ];
        
        // Get template
        $templates = $this->config->getWhatsappTemplates($order->getStoreId());
        $message = $templates['shipping_confirmation'] ?? 'Good news! Your order #{{order_id}} has been shipped. Track your package with number {{tracking_number}}.';
        
        // Send message
        $result = $this->whatsappService->sendMessage($phone, $message, $params, $order->getStoreId());
        
        if ($result) {
            $this->logger->info('PhoneMailer: WhatsApp shipment notification sent for order #' . $order->getIncrementId());
        } else {
            $this->logger->warning('PhoneMailer: Failed to send WhatsApp shipment notification for order #' . $order->getIncrementId());
        }
    }

    /**
     * Process invoice notification
     *
     * @param Observer $observer
     * @return void
     */
    private function processInvoiceNotification(Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order\Invoice $invoice */
        $invoice = $observer->getEvent()->getInvoice();
        if (!$invoice) {
            return;
        }

        $order = $invoice->getOrder();
        if (!$order) {
            return;
        }
        
        $phone = $this->getPhoneFromOrder($order);
        if (!$phone) {
            return;
        }
        
        // Prepare message parameters
        $params = [
            'order_id' => $order->getIncrementId(),
            'customer_name' => $order->getCustomerName() ?: 'Customer',
            'invoice_id' => $invoice->getIncrementId(),
            'total' => $order->formatPrice($invoice->getGrandTotal()),
            'store_name' => $order->getStore()->getFrontendName()
        ];
        
        // Get template
        $templates = $this->config->getWhatsappTemplates($order->getStoreId());
        $message = $templates['invoice_confirmation'] ?? 'Your invoice #{{invoice_id}} for order #{{order_id}} has been created. Total amount: {{total}}.';
        
        // Send message
        $this->whatsappService->sendMessage($phone, $message, $params, $order->getStoreId());
    }

    /**
     * Process status history notification
     *
     * @param Observer $observer
     * @return void
     */
    private function processStatusHistoryNotification(Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order\Status\History $statusHistory */
        $statusHistory = $observer->getEvent()->getStatusHistory();
        if (!$statusHistory || !$statusHistory->getComment()) {
            return;
        }

        $order = $statusHistory->getOrder();
        if (!$order) {
            return;
        }
        
        // Only send notification if status is "complete"
        if ($statusHistory->getStatus() === 'complete') {
            $phone = $this->getPhoneFromOrder($order);
            if (!$phone) {
                return;
            }
            
            // Prepare message parameters
            $params = [
                'order_id' => $order->getIncrementId(),
                'customer_name' => $order->getCustomerName() ?: 'Customer',
                'store_name' => $order->getStore()->getFrontendName(),
                'order_status' => __($statusHistory->getStatus()),
                'comment' => $statusHistory->getComment()
            ];
            
            // Get template
            $templates = $this->config->getWhatsappTemplates($order->getStoreId());
            $message = $templates['order_delivered'] ?? 'Your order #{{order_id}} has been delivered. Thank you for shopping with {{store_name}}!';
            
            // Send message
            $this->whatsappService->sendMessage($phone, $message, $params, $order->getStoreId());
        }
    }

    /**
     * Get phone number from order
     *
     * @param \Magento\Sales\Model\Order $order
     * @return string|null
     */
    private function getPhoneFromOrder($order)
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
}
