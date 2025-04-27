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

namespace MagoArab\PhoneMailer\Plugin;

use Magento\Customer\Model\AccountManagement;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\LocalizedException;
use MagoArab\PhoneMailer\Helper\EmailGenerator;
use MagoArab\PhoneMailer\Helper\Config;
use Psr\Log\LoggerInterface;

class AccountManagementPlugin
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
     * AccountManagementPlugin constructor.
     *
     * @param EmailGenerator $emailGenerator
     * @param Config $config
     * @param LoggerInterface $logger
     */
    public function __construct(
        EmailGenerator $emailGenerator,
        Config $config,
        LoggerInterface $logger
    ) {
        $this->emailGenerator = $emailGenerator;
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * Generate email from phone number before creating customer
     *
     * @param AccountManagement $subject
     * @param CustomerInterface $customer
     * @param string|null $password
     * @param string $redirectUrl
     * @return array
     * @throws LocalizedException
     */
    public function beforeCreateAccount(
        AccountManagement $subject,
        CustomerInterface $customer,
        $password = null,
        $redirectUrl = ''
    ) {
        if (!$this->config->isEnabled()) {
            return [$customer, $password, $redirectUrl];
        }

        try {
            // Check if customer already has a valid email
            if (!$customer->getEmail() || !filter_var($customer->getEmail(), FILTER_VALIDATE_EMAIL)) {
                // Get phone number from addresses
                $addresses = $customer->getAddresses();
                $telephone = null;
                
                foreach ($addresses as $address) {
                    if ($address->getTelephone()) {
                        $telephone = $address->getTelephone();
                        break;
                    }
                }
                
                // If phone number found, generate email
                if ($telephone) {
                    $email = $this->emailGenerator->generateEmailFromPhone($telephone, $customer->getStoreId());
                    $customer->setEmail($email);
                    
                    $this->logger->info('PhoneMailer: Generated email ' . $email . ' for phone ' . $telephone);
                } else {
                    throw new LocalizedException(__('Phone number is required to create an account.'));
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('PhoneMailer: Error generating email: ' . $e->getMessage());
            // Pass through the error to be handled by the core
        }

        return [$customer, $password, $redirectUrl];
    }

    /**
     * Before authenticate, handle cases where login is attempted with phone number
     *
     * @param AccountManagement $subject
     * @param string $username
     * @param string $password
     * @return array
     */
    public function beforeAuthenticate(
        AccountManagement $subject,
        $username,
        $password
    ) {
        if (!$this->config->isEnabled()) {
            return [$username, $password];
        }

        // Check if username might be a phone number
        if (preg_match('/^[0-9+\s\-()]+$/', $username)) {
            try {
                // Generate email from phone
                $email = $this->emailGenerator->generateEmailFromPhone($username);
                
                // Log the conversion
                $this->logger->info('PhoneMailer: Login attempt with phone, converting ' . $username . ' to ' . $email);
                
                // Use generated email as username
                return [$email, $password];
            } catch (\Exception $e) {
                $this->logger->error('PhoneMailer: Error in login conversion: ' . $e->getMessage());
            }
        }

        return [$username, $password];
    }

    /**
     * Before initiating password reset, handle phone number input
     *
     * @param AccountManagement $subject
     * @param string $email
     * @param string $template
     * @param int|null $websiteId
     * @return array
     */
    public function beforeInitiatePasswordReset(
        AccountManagement $subject,
        $email,
        $template,
        $websiteId = null
    ) {
        if (!$this->config->isEnabled()) {
            return [$email, $template, $websiteId];
        }

        // Check if email might be a phone number
        if (preg_match('/^[0-9+\s\-()]+$/', $email)) {
            try {
                // Generate email from phone
                $generatedEmail = $this->emailGenerator->generateEmailFromPhone($email, $websiteId);
                
                // Log the conversion
                $this->logger->info('PhoneMailer: Password reset with phone, converting ' . $email . ' to ' . $generatedEmail);
                
                // Use generated email
                return [$generatedEmail, $template, $websiteId];
            } catch (\Exception $e) {
                $this->logger->error('PhoneMailer: Error in password reset: ' . $e->getMessage());
            }
        }

        return [$email, $template, $websiteId];
    }
}