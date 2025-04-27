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

define([
    'jquery',
    'ko',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/checkout-data',
    'phoneValidator',
    'mage/utils/wrapper'
], function ($, ko, quote, checkoutData, phoneValidator, wrapper) {
    'use strict';

    return function (emailComponent) {
        return wrapper.wrap(emailComponent, function (originalComponent) {
            var component = originalComponent;
            
            /**
             * Override visibility to hide email field
             */
            component.isVisible = ko.computed(function () {
                // Check if PhoneMailer is enabled via config
                var phoneMailerEnabled = false;
                
                // Try to get from component's config or from DOM
                if (component.config && component.config.phoneMailerEnabled) {
                    phoneMailerEnabled = component.config.phoneMailerEnabled;
                } else {
                    var configElement = $('[data-role="phonemail-config"]');
                    if (configElement.length) {
                        phoneMailerEnabled = configElement.data('phonemail-enabled') === '1';
                    }
                }
                
                if (phoneMailerEnabled) {
                    return false; // Hide email field if PhoneMailer is enabled
                }
                
                // Otherwise use original logic
                return !component.isCustomerLoggedIn();
            });
            
            /**
             * Override initialize to listen for phone changes
             */
            var originalInitialize = component.initialize;
            component.initialize = function () {
                var result = originalInitialize.apply(this, arguments);
                
                // Listen for phone field changes
                this.bindPhoneChangeListener();
                
                return result;
            };
            
            /**
             * Add method to bind phone field change listener
             */
            component.bindPhoneChangeListener = function () {
                var self = this;
                
                // Listen for phone field changes in shipping or billing address forms
                $(document).on('change', 'input[name="telephone"]', function () {
                    var phoneNumber = $(this).val();
                    
                    if (phoneNumber && !self.isCustomerLoggedIn()) {
                        // Auto-generate email
                        self.updateEmailFromPhone(phoneNumber);
                    }
                });
            };
            
            /**
             * Add method to update email from phone
             */
            component.updateEmailFromPhone = function (phoneNumber) {
                // Generate email from phone
                var email = phoneValidator.generateEmailFromPhone(phoneNumber);
                
                if (email) {
                    // Update email field
                    this.email(email);
                    
                    // Update quote email
                    quote.guestEmail = email;
                    
                    // Update checkout data
                    checkoutData.setInputFieldEmailValue(email);
                    
                    // Validate email
                    this.emailHasChanged();
                }
            };
            
            return component;
        });
    };
});