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
    'Magento_Checkout/js/view/form/element/email',
    'Magento_Customer/js/model/customer',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/checkout-data',
    'mage/validation'
], function ($, ko, Component, customer, quote, checkoutData) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'MagoArab_PhoneMailer/form/element/email',
            email: '',
            emailFocused: false,
            isLoading: false,
            isPasswordVisible: false,
            listens: {
                email: 'emailHasChanged',
                emailFocused: 'validateEmail'
            }
        },

        /**
         * Initialize component
         */
        initialize: function () {
            this._super();
            
            // Check if customer is logged in
            this.isCustomerLoggedIn = customer.isLoggedIn;
            
            // Make email field visibility dynamic
            this.isVisible = ko.computed(function () {
                return !this.isCustomerLoggedIn() && this.isPhoneMailerEnabled();
            }, this);

            // Auto-generate email when phone is provided
            this.bindPhoneChangeListener();

            return this;
        },

        /**
         * Check if PhoneMailer is enabled
         * 
         * @returns {boolean}
         */
        isPhoneMailerEnabled: function () {
            // Get config from data attribute
            var config = $('[data-role="phonemail-config"]').data('phonemail-enabled');
            return config !== '1'; // Show email field if module is disabled
        },

        /**
         * Bind listener to phone field changes
         */
        bindPhoneChangeListener: function () {
            var self = this;
            
            // Listen for phone field changes in shipping address form
            $(document).on('change', 'input[name="telephone"]', function () {
                var phoneNumber = $(this).val();
                
                if (phoneNumber && !self.isCustomerLoggedIn() && !self.isPhoneMailerEnabled()) {
                    // Auto-generate email
                    self.generateEmailFromPhone(phoneNumber);
                }
            });
        },

        /**
         * Generate email from phone number
         * 
         * @param {string} phoneNumber
         */
        generateEmailFromPhone: function (phoneNumber) {
            var cleanPhone = phoneNumber.replace(/[^0-9]/g, '');
            
            // Get domain from config or use store domain
            var domain = window.location.hostname;
            domain = domain.replace('www.', '');
            
            // Set email
            var email = cleanPhone + '@' + domain;
            this.email(email);
            
            // Update quote and checkout data
            if (quote) {
                quote.guestEmail = email;
            }
            
            if (checkoutData) {
                checkoutData.setInputFieldEmailValue(email);
            }
        }
    });
});