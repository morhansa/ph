<?php

namespace MagoArab\PhoneMailer\Model\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\ScopeInterface;
use MagoArab\PhoneMailer\Helper\Config as ModuleConfig;
use Psr\Log\LoggerInterface;

class WhatsappApi
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var EncryptorInterface
     */
    protected $encryptor;

    /**
     * @var ModuleConfig
     */
    protected $moduleConfig;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * WhatsappApi constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param EncryptorInterface $encryptor
     * @param ModuleConfig $moduleConfig
     * @param LoggerInterface $logger
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        EncryptorInterface $encryptor,
        ModuleConfig $moduleConfig,
        LoggerInterface $logger
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->encryptor = $encryptor;
        $this->moduleConfig = $moduleConfig;
        $this->logger = $logger;
    }

    /**
     * Get WhatsApp API key
     *
     * @param int|null $storeId
     * @return string
     */
    public function getApiKey($storeId = null)
    {
        return $this->moduleConfig->getWhatsappApiKey($storeId);
    }

    /**
     * Get WhatsApp instance ID
     *
     * @param int|null $storeId
     * @return string
     */
    public function getInstanceId($storeId = null)
    {
        return $this->moduleConfig->getWhatsappInstanceId($storeId);
    }

    /**
     * Check if WhatsApp integration is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isEnabled($storeId = null)
    {
        return $this->moduleConfig->isWhatsappEnabled($storeId);
    }

    /**
     * Get API endpoint URL
     *
     * @param string $endpoint
     * @return string
     */
    public function getApiUrl($endpoint = '')
    {
        $baseUrl = 'https://api.whatsapp.com/v1/';
        return $baseUrl . ltrim($endpoint, '/');
    }

    /**
     * Get template for notification type
     *
     * @param string $type
     * @param int|null $storeId
     * @return string
     */
    public function getTemplate($type, $storeId = null)
    {
        $templates = $this->moduleConfig->getWhatsappTemplates($storeId);
        
        if (isset($templates[$type])) {
            return $templates[$type];
        }
        
        // Default templates
        $defaults = [
            'order_confirmation' => 'Thank you for your order #{{order_id}}. Your total is {{total}}.',
            'shipping_confirmation' => 'Your order #{{order_id}} has been shipped. Track your package with number {{tracking_number}}.',
            'order_delivered' => 'Your order #{{order_id}} has been delivered. Thank you for shopping with {{store_name}}!',
            'welcome' => 'Welcome to {{store_name}}, {{customer_name}}! Thank you for registering.'
        ];
        
        return $defaults[$type] ?? '';
    }

    /**
     * Validate configuration
     *
     * @param int|null $storeId
     * @return bool
     */
    public function validateConfig($storeId = null)
    {
        if (!$this->isEnabled($storeId)) {
            return false;
        }
        
        $apiKey = $this->getApiKey($storeId);
        $instanceId = $this->getInstanceId($storeId);
        
        if (empty($apiKey) || empty($instanceId)) {
            $this->logger->warning('PhoneMailer: WhatsApp configuration is incomplete');
            return false;
        }
        
        return true;
    }
}