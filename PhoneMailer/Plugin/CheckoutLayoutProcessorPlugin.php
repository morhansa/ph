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

use Magento\Checkout\Block\Checkout\LayoutProcessor;
use MagoArab\PhoneMailer\Helper\Config;
use Psr\Log\LoggerInterface;

class CheckoutLayoutProcessorPlugin
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * CheckoutLayoutProcessorPlugin constructor.
     *
     * @param Config $config
     * @param LoggerInterface $logger
     */
    public function __construct(
        Config $config,
        LoggerInterface $logger
    ) {
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * Process js Layout of checkout page
     *
     * @param LayoutProcessor $subject
     * @param array $jsLayout
     * @return array
     */
    public function afterProcess(
        LayoutProcessor $subject,
        array $jsLayout
    ) {
        try {
            if (!$this->config->isEnabled()) {
                return $jsLayout;
            }

            // Hide email field if module is enabled
            if (isset($jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']
                ['children']['shippingAddress']['children']['customer-email'])) {
                
                // Add custom component for email field
                $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']
                    ['children']['shippingAddress']['children']['customer-email']['component'] = 
                    'MagoArab_PhoneMailer/js/view/form/element/email';
                
                // Add data about PhoneMailer being enabled
                $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']
                    ['children']['shippingAddress']['children']['customer-email']['config'] = [
                        'phoneMailerEnabled' => true,
                        'template' => 'MagoArab_PhoneMailer/form/element/email'
                    ];
                
                // Make sure telephone is required and visible
                if (isset($jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']
                    ['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['telephone'])) {
                    
                    $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']
                        ['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['telephone']['validation']['required-entry'] = true;

                    $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']
                        ['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['telephone']['visible'] = true;
                    
                    // Add custom validation for telephone
                    $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']
                        ['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['telephone']['validation']['phone-number'] = true;
                    
                    // Add custom component for telephone field
                    $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']
                        ['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['telephone']['component'] = 
                        'MagoArab_PhoneMailer/js/view/form/element/telephone';
                }
            }

            // Same for billing address if exists
            if (isset($jsLayout['components']['checkout']['children']['steps']['children']['billing-step']
                ['children']['payment']['children']['customer-email'])) {
                
                $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']
                    ['children']['payment']['children']['customer-email']['component'] = 
                    'MagoArab_PhoneMailer/js/view/form/element/email';
                
                $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']
                    ['children']['payment']['children']['customer-email']['config'] = [
                        'phoneMailerEnabled' => true,
                        'template' => 'MagoArab_PhoneMailer/form/element/email'
                    ];
            }
            
            // Process payments section if exists
            if (isset($jsLayout['components']['checkout']['children']['steps']['children']['billing-step']
                ['children']['payment']['children']['payments-list'])) {
                
                $paymentForms = $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']
                    ['children']['payment']['children']['payments-list']['children'];
                
                foreach ($paymentForms as $paymentCode => $paymentForm) {
                    if (isset($paymentForm['children']['form-fields']['children']['telephone'])) {
                        $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']
                            ['children']['payment']['children']['payments-list']['children'][$paymentCode]
                            ['children']['form-fields']['children']['telephone']['validation']['required-entry'] = true;
                        
                        $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']
                            ['children']['payment']['children']['payments-list']['children'][$paymentCode]
                            ['children']['form-fields']['children']['telephone']['validation']['phone-number'] = true;
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('PhoneMailer: Error in checkout layout processor: ' . $e->getMessage());
        }
        
        return $jsLayout;
    }
}