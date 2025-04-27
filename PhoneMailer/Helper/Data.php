<?php
namespace MagoArab\PhoneMailer\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Psr\Log\LoggerInterface;

class Data extends AbstractHelper
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var EmailGenerator
     */
    protected $emailGenerator;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Data constructor.
     *
     * @param Context $context
     * @param Config $config
     * @param EmailGenerator $emailGenerator
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        Config $config,
        EmailGenerator $emailGenerator,
        LoggerInterface $logger
    ) {
        $this->config = $config;
        $this->emailGenerator = $emailGenerator;
        $this->logger = $logger;
        parent::__construct($context);
    }

    /**
     * Check if module is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isModuleEnabled($storeId = null)
    {
        return $this->config->isEnabled($storeId);
    }

    /**
     * Generate email from phone number
     *
     * @param string $phoneNumber
     * @param int|null $storeId
     * @return string
     */
    public function generateEmail($phoneNumber, $storeId = null)
    {
        try {
            return $this->emailGenerator->generateEmailFromPhone($phoneNumber, $storeId);
        } catch (\Exception $e) {
            $this->logger->error('PhoneMailer Data Helper: Error generating email: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Check if WhatsApp is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isWhatsAppEnabled($storeId = null)
    {
        return $this->config->isWhatsappEnabled($storeId);
    }

    /**
     * Format phone number for display
     *
     * @param string $phoneNumber
     * @return string
     */
    public function formatPhoneNumber($phoneNumber)
    {
        if (empty($phoneNumber)) {
            return '';
        }
        
        // Keep only digits and + sign
        $clean = preg_replace('/[^\d+]/', '', $phoneNumber);
        
        // Format depending on the length and format
        if (strpos($clean, '+') === 0) {
            // International format
            return $clean;
        } elseif (strlen($clean) > 10) {
            // Long number - likely international without +
            return '+' . $clean;
        } elseif (strlen($clean) === 10) {
            // Likely US format
            return preg_replace('/(\d{3})(\d{3})(\d{4})/', '($1) $2-$3', $clean);
        } else {
            // Other formats
            return $clean;
        }
    }

    /**
     * Get domain for email generation
     *
     * @param int|null $storeId
     * @return string
     */
    public function getEmailDomain($storeId = null)
    {
        try {
            return $this->emailGenerator->getDomain($storeId);
        } catch (\Exception $e) {
            $this->logger->error('PhoneMailer Data Helper: Error getting domain: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Log debug message
     *
     * @param string $message
     * @return void
     */
    public function logDebug($message)
    {
        $this->logger->debug('PhoneMailer: ' . $message);
    }
}