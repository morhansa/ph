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

use Magento\Checkout\Model\GuestPaymentInformationManagement as MagentoGuestPaymentInformationManagement;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\GuestCartManagementInterface;
use Magento\Quote\Api\GuestBillingAddressManagementInterface;
use Magento\Quote\Api\GuestPaymentMethodManagementInterface;
use Magento\Quote\Api\GuestCartTotalRepositoryInterface;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Checkout\Api\PaymentProcessingRateLimiterInterface;
use Magento\Checkout\Api\PaymentSavingRateLimiterInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Api\Data\AddressInterface;
use MagoArab\PhoneMailer\Helper\EmailGenerator;
use MagoArab\PhoneMailer\Helper\Config;
use Psr\Log\LoggerInterface;

class GuestPaymentInformationManagement extends MagentoGuestPaymentInformationManagement
{
    /**
     * @var EmailGenerator
     */
    private $emailGenerator;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * GuestPaymentInformationManagement constructor.
     *
     * @param CartRepositoryInterface $cartRepository
     * @param GuestCartManagementInterface $cartManagement
     * @param GuestBillingAddressManagementInterface $billingAddressManagement
     * @param GuestPaymentMethodManagementInterface $paymentMethodManagement
     * @param GuestCartTotalRepositoryInterface $cartTotalsRepository
     * @param QuoteIdMaskFactory $quoteIdMaskFactory
     * @param PaymentProcessingRateLimiterInterface $paymentRateLimiter
     * @param PaymentSavingRateLimiterInterface $savingRateLimiter
     * @param EmailGenerator $emailGenerator
     * @param Config $config
     * @param LoggerInterface $logger
     */
    public function __construct(
        CartRepositoryInterface $cartRepository,
        GuestCartManagementInterface $cartManagement,
        GuestBillingAddressManagementInterface $billingAddressManagement,
        GuestPaymentMethodManagementInterface $paymentMethodManagement,
        GuestCartTotalRepositoryInterface $cartTotalsRepository,
        QuoteIdMaskFactory $quoteIdMaskFactory,
        PaymentProcessingRateLimiterInterface $paymentRateLimiter,
        PaymentSavingRateLimiterInterface $savingRateLimiter,
        EmailGenerator $emailGenerator,
        Config $config,
        LoggerInterface $logger
    ) {
        parent::__construct(
            $cartRepository,
            $cartManagement,
            $billingAddressManagement,
            $paymentMethodManagement,
            $cartTotalsRepository,
            $quoteIdMaskFactory,
            $paymentRateLimiter,
            $savingRateLimiter
        );
        $this->emailGenerator = $emailGenerator;
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function savePaymentInformationAndPlaceOrder(
        $cartId,
        $email,
        PaymentInterface $paymentMethod,
        AddressInterface $billingAddress = null
    ) {
        try {
            if ($this->config->isEnabled()) {
                // Check email format - if it doesn't look like a valid email, generate one from phone
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    // Try to get phone number from billing address
                    $phone = null;
                    if ($billingAddress && $billingAddress->getTelephone()) {
                        $phone = $billingAddress->getTelephone();
                    }
                    
                    // If we have a phone number, generate email
                    if ($phone) {
                        $generatedEmail = $this->emailGenerator->generateEmailFromPhone($phone);
                        $email = $generatedEmail;
                        $this->logger->info('PhoneMailer: Generated email for guest checkout: ' . $email);
                    }
                }
            }
            
            // Call parent method with potentially modified email
            return parent::savePaymentInformationAndPlaceOrder($cartId, $email, $paymentMethod, $billingAddress);
        } catch (\Exception $e) {
            $this->logger->error('PhoneMailer: Error in guest payment processing: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * @inheritDoc
     */
    public function savePaymentInformation(
        $cartId,
        $email,
        PaymentInterface $paymentMethod,
        AddressInterface $billingAddress = null
    ) {
        try {
            if ($this->config->isEnabled()) {
                // Check email format - if it doesn't look like a valid email, generate one from phone
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    // Try to get phone number from billing address
                    $phone = null;
                    if ($billingAddress && $billingAddress->getTelephone()) {
                        $phone = $billingAddress->getTelephone();
                    }
                    
                    // If we have a phone number, generate email
                    if ($phone) {
                        $generatedEmail = $this->emailGenerator->generateEmailFromPhone($phone);
                        $email = $generatedEmail;
                        $this->logger->info('PhoneMailer: Generated email for guest checkout (save payment): ' . $email);
                    }
                }
            }
            
            // Call parent method with potentially modified email
            return parent::savePaymentInformation($cartId, $email, $paymentMethod, $billingAddress);
        } catch (\Exception $e) {
            $this->logger->error('PhoneMailer: Error in guest payment save: ' . $e->getMessage());
            throw $e;
        }
    }
}