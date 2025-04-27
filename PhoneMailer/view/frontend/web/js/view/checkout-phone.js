define([
    'jquery',
    'ko',
    'uiComponent',
    'Magento_Checkout/js/model/quote',
    'Magento_Customer/js/model/customer',
    'phoneValidator'
], function ($, ko, Component, quote, customer, phoneValidator) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'MagoArab_PhoneMailer/form/phone-field',
            phoneNumber: '',
            isVisible: true
        },

        /**
         * Initialize component
         */
        initialize: function () {
            this._super();
            
            this.phoneNumber = ko.observable('');
            
            // Listen to changes in phone number
            this.phoneNumber.subscribe(function (value) {
                this.onPhoneChange(value);
            }, this);
            
            return this;
        },

        /**
         * Handle phone number change
         * 
         * @param {String} value
         */
        onPhoneChange: function (value) {
            // Validate phone
            if (phoneValidator.validate(value)) {
                // Generate email from phone
                var email = phoneValidator.generateEmailFromPhone(value);
                
                // Update quote email
                if (email && quote) {
                    quote.guestEmail = email;
                }
            }
        },

        /**
         * Format phone number for display
         * 
         * @param {String} value
         * @returns {String}
         */
        formatPhone: function (value) {
            return phoneValidator.formatPhoneNumber(value);
        }
    });
});