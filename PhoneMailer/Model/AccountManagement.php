<?php

namespace MagoArab\PhoneMailer\Model;

use Magento\Customer\Model\AccountManagement as MagentoAccountManagement;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\State\InputMismatchException;
use Magento\Framework\Exception\EmailNotConfirmedException;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Exception\LocalizedException;
use MagoArab\PhoneMailer\Helper\EmailGenerator;
use MagoArab\PhoneMailer\Helper\Config;
use Psr\Log\LoggerInterface;

class AccountManagement extends MagentoAccountManagement
{
    /**
     * @var EmailGenerator
     */
    private $emailGenerator;

    /**
     * @var Config
     */
    private $moduleConfig;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Authenticate a customer by username and password
     *
     * @param string $username
     * @param string $password
     * @return \Magento\Customer\Api\Data\CustomerInterface
     * @throws AuthenticationException
     * @throws LocalizedException
     */
    public function authenticate($username, $password)
    {
        try {
            // Get dependencies via object manager since we can't override constructor
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            
            if (!$this->emailGenerator) {
                $this->emailGenerator = $objectManager->get(EmailGenerator::class);
            }
            
            if (!$this->moduleConfig) {
                $this->moduleConfig = $objectManager->get(Config::class);
            }
            
            if (!$this->logger) {
                $this->logger = $objectManager->get(LoggerInterface::class);
            }
            
            // Check if module is enabled and username might be a phone number
            if ($this->moduleConfig->isEnabled() && preg_match('/^[0-9+\s\-()]+$/', $username)) {
                try {
                    // Clean phone number
                    $cleanPhone = preg_replace('/[^0-9+]/', '', $username);
                    
                    // Generate email from phone
                    $email = $this->emailGenerator->generateEmailFromPhone($cleanPhone);
                    
                    $this->logger->info('PhoneMailer: Authentication with phone number. Converted ' . $username . ' to ' . $email);
                    
                    // Use generated email as username for authentication
                    $username = $email;
                } catch (\Exception $e) {
                    $this->logger->error('PhoneMailer: Error converting phone to email for authentication: ' . $e->getMessage());
                    // Continue with original username if conversion fails
                }
            }
        } catch (\Exception $e) {
            // If anything fails, log it but allow the original authentication to proceed
            if ($this->logger) {
                $this->logger->error('PhoneMailer: Error in authentication preprocessing: ' . $e->getMessage());
            }
        }
        
        // Call parent method with potentially modified username
        return parent::authenticate($username, $password);
    }

    /**
     * Send a reset password email
     *
     * @param string $email
     * @param string $template
     * @param int $websiteId
     * @return bool
     * @throws InputException
     * @throws InputMismatchException
     * @throws LocalizedException
     */
    public function initiatePasswordReset($email, $template, $websiteId = 0)
    {
        try {
            // Get dependencies via object manager if needed
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            
            if (!$this->emailGenerator) {
                $this->emailGenerator = $objectManager->get(EmailGenerator::class);
            }
            
            if (!$this->moduleConfig) {
                $this->moduleConfig = $objectManager->get(Config::class);
            }
            
            if (!$this->logger) {
                $this->logger = $objectManager->get(LoggerInterface::class);
            }
            
            // Check if module is enabled and email might be a phone number
            if ($this->moduleConfig->isEnabled() && preg_match('/^[0-9+\s\-()]+$/', $email)) {
                try {
                    // Clean phone number
                    $cleanPhone = preg_replace('/[^0-9+]/', '', $email);
                    
                    // Generate email from phone
                    $generatedEmail = $this->emailGenerator->generateEmailFromPhone($cleanPhone, $websiteId);
                    
                    $this->logger->info('PhoneMailer: Password reset with phone number. Converted ' . $email . ' to ' . $generatedEmail);
                    
                    // Use generated email
                    $email = $generatedEmail;
                } catch (\Exception $e) {
                    $this->logger->error('PhoneMailer: Error converting phone to email for password reset: ' . $e->getMessage());
                    // Continue with original email if conversion fails
                }
            }
        } catch (\Exception $e) {
            // If anything fails, log it but allow the original password reset to proceed
            if ($this->logger) {
                $this->logger->error('PhoneMailer: Error in password reset preprocessing: ' . $e->getMessage());
            }
        }
        
        // Call parent method with potentially modified email
        return parent::initiatePasswordReset($email, $template, $websiteId);
    }

    /**
     * Validate the provided password reset token
     *
     * @param string $customerId
     * @param string $resetPasswordLinkToken
     * @return bool
     * @throws InputException
     * @throws InputMismatchException
     * @throws LocalizedException
     */
    public function validateResetPasswordLinkToken($customerId, $resetPasswordLinkToken)
    {
        // No need to modify this method, just call parent
        return parent::validateResetPasswordLinkToken($customerId, $resetPasswordLinkToken);
    }
}
