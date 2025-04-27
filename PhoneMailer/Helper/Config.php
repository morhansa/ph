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

namespace MagoArab\PhoneMailer\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Encryption\EncryptorInterface;

class Config extends AbstractHelper
{
    /**
     * Config paths
     */
    const XML_PATH_ENABLED = 'phonemail/general/enabled';
    const XML_PATH_DOMAIN_MODE = 'phonemail/general/domain_mode';
    const XML_PATH_CUSTOM_DOMAIN = 'phonemail/general/custom_domain';
    const XML_PATH_WHATSAPP_ENABLED = 'phonemail/whatsapp/enabled';
    const XML_PATH_WHATSAPP_API_KEY = 'phonemail/whatsapp/api_key';
    const XML_PATH_WHATSAPP_INSTANCE_ID = 'phonemail/whatsapp/instance_id';
    const XML_PATH_WHATSAPP_TEMPLATES = 'phonemail/whatsapp/templates';

    /**
     * @var Json
     */
    protected $json;

    /**
     * @var EncryptorInterface
     */
    protected $encryptor;

    /**
     * Config constructor.
     *
     * @param Context $context
     * @param Json $json
     * @param EncryptorInterface $encryptor
     */
    public function __construct(
        Context $context,
        Json $json,
        EncryptorInterface $encryptor
    ) {
        $this->json = $json;
        $this->encryptor = $encryptor;
        parent::__construct($context);
    }

    /**
     * Check if the module is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isEnabled($storeId = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get domain mode (auto or custom)
     *
     * @param int|null $storeId
     * @return string
     */
    public function getDomainMode($storeId = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_DOMAIN_MODE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: 'auto';
    }

    /**
     * Get custom domain
     *
     * @param int|null $storeId
     * @return string
     */
    public function getCustomDomain($storeId = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_CUSTOM_DOMAIN,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: '';
    }

    /**
     * Check if WhatsApp integration is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isWhatsappEnabled($storeId = null)
    {
        return $this->isEnabled($storeId) && $this->scopeConfig->isSetFlag(
            self::XML_PATH_WHATSAPP_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get WhatsApp API key
     *
     * @param int|null $storeId
     * @return string
     */
    public function getWhatsappApiKey($storeId = null)
    {
        $encryptedKey = $this->scopeConfig->getValue(
            self::XML_PATH_WHATSAPP_API_KEY,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        
        if (!$encryptedKey) {
            return '';
        }
        
        try {
            return $this->encryptor->decrypt($encryptedKey);
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Get WhatsApp instance ID
     *
     * @param int|null $storeId
     * @return string
     */
    public function getWhatsappInstanceId($storeId = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_WHATSAPP_INSTANCE_ID,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: '';
    }

    /**
     * Get WhatsApp notification templates
     *
     * @param int|null $storeId
     * @return array
     */
    public function getWhatsappTemplates($storeId = null)
    {
        $templates = $this->scopeConfig->getValue(
            self::XML_PATH_WHATSAPP_TEMPLATES,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        
        if (!$templates) {
            return $this->getDefaultTemplates();
        }
        
        try {
            $decodedTemplates = $this->json->unserialize($templates);
            return is_array($decodedTemplates) ? $decodedTemplates : $this->getDefaultTemplates();
        } catch (\Exception $e) {
            return $this->getDefaultTemplates();
        }
    }

    /**
     * Get default WhatsApp templates
     *
     * @return array
     */
    protected function getDefaultTemplates()
    {
        return [
            'order_confirmation' => 'Thank you for your order #{{order_id}}. Your total is {{total}}. We will process your order shortly.',
            'shipping_confirmation' => 'Good news! Your order #{{order_id}} has been shipped. Track your package with number {{tracking_number}}.',
            'order_delivered' => 'Your order #{{order_id}} has been delivered. Thank you for shopping with {{store_name}}!',
            'welcome' => 'Welcome to {{store_name}}, {{customer_name}}! Thank you for registering.'
        ];
    }
}