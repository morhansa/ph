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

use MagoArab\PhoneMailer\Api\WhatsappServiceInterface;
use MagoArab\PhoneMailer\Helper\Config;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\Serializer\Json;
use Psr\Log\LoggerInterface;

class WhatsappService implements WhatsappServiceInterface
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Curl
     */
    protected $curl;

    /**
     * @var Json
     */
    protected $json;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * WhatsApp API base URL
     */
    const API_BASE_URL = 'https://api.whatsapp.com/v1/';

    /**
     * WhatsappService constructor.
     *
     * @param Config $config
     * @param Curl $curl
     * @param Json $json
     * @param LoggerInterface $logger
     */
    public function __construct(
        Config $config,
        Curl $curl,
        Json $json,
        LoggerInterface $logger
    ) {
        $this->config = $config;
        $this->curl = $curl;
        $this->json = $json;
        $this->logger = $logger;
    }

    /**
     * Send WhatsApp message
     *
     * @param string $phone
     * @param string $message
     * @param array $params
     * @param int|null $storeId
     * @return bool
     */
    public function sendMessage($phone, $message, $params = [], $storeId = null)
    {
        if (!$this->config->isWhatsappEnabled($storeId)) {
            $this->logger->debug('PhoneMailer: WhatsApp integration is disabled');
            return false;
        }

        try {
            $apiKey = $this->config->getWhatsappApiKey($storeId);
            $instanceId = $this->config->getWhatsappInstanceId($storeId);
            
            if (!$apiKey || !$instanceId) {
                $this->logger->error('PhoneMailer: WhatsApp API credentials are not configured');
                return false;
            }
            
            // Format phone number (remove any non-numeric characters except +)
            $phone = preg_replace('/[^0-9+]/', '', $phone);
            
            // Process message template
            $messageBody = $this->processTemplate($message, $params);
            
            // Build API endpoint
            $endpoint = self::API_BASE_URL . "messages";
            
            // Prepare request data
            $data = [
                'to' => $phone,
                'type' => 'text',
                'text' => [
                    'body' => $messageBody
                ]
            ];
            
            // Reset cURL
            $this->curl->reset();
            
            // Set headers
            $this->curl->addHeader('Content-Type', 'application/json');
            $this->curl->addHeader('Authorization', 'Bearer ' . $apiKey);
            
            // Send request
            $this->logger->debug('PhoneMailer: Sending WhatsApp message to ' . $phone);
            $this->curl->post($endpoint, $this->json->serialize($data));
            
            // Get response
            $response = $this->curl->getBody();
            $responseData = $this->json->unserialize($response);
            
            // Check if successful
            if (isset($responseData['messages']) && isset($responseData['messages'][0]['id'])) {
                $this->logger->debug('PhoneMailer: WhatsApp message sent successfully. Message ID: ' . $responseData['messages'][0]['id']);
                return true;
            }
            
            $errorMessage = isset($responseData['error']) ? $responseData['error']['message'] : 'Unknown error';
            $this->logger->error('PhoneMailer: WhatsApp API error: ' . $errorMessage);
            return false;
        } catch (\Exception $e) {
            $this->logger->error('PhoneMailer: Failed to send WhatsApp message: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Process template by replacing placeholders
     *
     * @param string $template
     * @param array $params
     * @return string
     */
    protected function processTemplate($template, $params)
    {
        foreach ($params as $key => $value) {
            $template = str_replace('{{' . $key . '}}', $value, $template);
        }
        
        return $template;
    }

    /**
     * Send notification for order placement
     *
     * @param \Magento\Sales\Model\Order $order
     * @param int|null $storeId
     * @return bool
     */
    public function sendOrderNotification($order, $storeId = null)
    {
        try {
            if (!$this->config->isWhatsappEnabled($storeId)) {
                return false;
            }

            // Get phone number from order
            $phone = null;
            if ($order->getBillingAddress()) {
                $phone = $order->getBillingAddress()->getTelephone();
            }
            
            if (!$phone && $order->getShippingAddress()) {
                $phone = $order->getShippingAddress()->getTelephone();
            }
            
            if (!$phone) {
                $this->logger->warning('PhoneMailer: No phone number found for order #' . $order->getIncrementId());
                return false;
            }
            
            // Prepare message parameters
            $params = [
                'order_id' => $order->getIncrementId(),
                'customer_name' => $order->getCustomerName() ?: ($order->getBillingAddress() ? $order->getBillingAddress()->getName() : 'Customer'),
                'total' => $order->formatPriceTxt($order->getGrandTotal()),
                'store_name' => $order->getStore()->getFrontendName()
            ];
            
            // Get order confirmation template
            $templates = $this->config->getWhatsappTemplates($storeId);
            $message = isset($templates['order_confirmation']) ? $templates['order_confirmation'] : 'Thank you for your order #{{order_id}}. Your total is {{total}}.';
            
            // Send message
            return $this->sendMessage($phone, $message, $params, $storeId);
        } catch (\Exception $e) {
            $this->logger->error('PhoneMailer: Error sending order notification: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Test WhatsApp connection
     *
     * @param string|null $apiKey
     * @param string|null $instanceId
     * @param int|null $storeId
     * @return array
     */
    public function testConnection($apiKey = null, $instanceId = null, $storeId = null)
    {
        try {
            // Use provided credentials or get from config
            $apiKey = $apiKey ?: $this->config->getWhatsappApiKey($storeId);
            $instanceId = $instanceId ?: $this->config->getWhatsappInstanceId($storeId);
            
            if (!$apiKey || !$instanceId) {
                return [
                    'success' => false,
                    'message' => __('API Key or Instance ID is missing')
                ];
            }
            
            // Build API endpoint for account validation
            $endpoint = self::API_BASE_URL . "accounts";
            
            // Reset cURL
            $this->curl->reset();
            
            // Set headers
            $this->curl->addHeader('Content-Type', 'application/json');
            $this->curl->addHeader('Authorization', 'Bearer ' . $apiKey);
            
            // Send request
            $this->curl->get($endpoint);
            
            // Get response
            $responseStatus = $this->curl->getStatus();
            $response = $this->curl->getBody();
            
            // Check HTTP status
            if ($responseStatus >= 200 && $responseStatus < 300) {
                $responseData = $this->json->unserialize($response);
                
                if (isset($responseData['account'])) {
                    return [
                        'success' => true,
                        'message' => __('Connection successful. Account is active.')
                    ];
                }
            }
            
            // Try to parse error
            $errorMessage = __('Connection failed');
            try {
                $errorData = $this->json->unserialize($response);
                if (isset($errorData['error']['message'])) {
                    $errorMessage = $errorData['error']['message'];
                }
            } catch (\Exception $parseException) {
                // If can't parse JSON, use HTTP status
                $errorMessage = __('Connection failed with HTTP status: %1', $responseStatus);
            }
            
            return [
                'success' => false,
                'message' => $errorMessage
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => __('Connection failed: %1', $e->getMessage())
            ];
        }
    }