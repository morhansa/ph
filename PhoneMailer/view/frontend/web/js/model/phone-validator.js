define([
    'jquery',
    'mage/translate'
], function ($, $t) {
    'use strict';

    return {
        /**
         * Validate phone number format
         *
         * @param {String} phoneNumber
         * @returns {Boolean}
         */
        validate: function (phoneNumber) {
            if (!phoneNumber) {
                return false;
            }
            
            // Remove spaces, dashes, parentheses
            var cleanPhone = phoneNumber.replace(/\s|\(|\)|\-/g, '');
            
            // Allow + at the beginning for international format
            if (cleanPhone.charAt(0) === '+') {
                cleanPhone = cleanPhone.substring(1);
            }
            
            // Check if the rest is digits only
            if (!/^\d+$/.test(cleanPhone)) {
                return false;
            }
            
            // Check minimum length (at least 7 digits)
            if (cleanPhone.length < 7) {
                return false;
            }
            
            return true;
        },
        
        /**
         * Get validation error message
         *
         * @returns {String}
         */
        getErrorMessage: function () {
            return $t('Please enter a valid phone number.');
        },
        
        /**
         * Format phone number for display
         *
         * @param {String} phoneNumber
         * @returns {String}
         */
        formatPhoneNumber: function (phoneNumber) {
            if (!phoneNumber) {
                return '';
            }
            
            // Remove spaces, dashes, parentheses first
            var cleaned = phoneNumber.replace(/\s|\(|\)|\-/g, '');
            
            // Format based on length and format
            if (cleaned.charAt(0) === '+') {
                // International format - keep as is
                return cleaned;
            } else if (cleaned.length === 10) {
                // US format: (XXX) XXX-XXXX
                return '(' + cleaned.substring(0, 3) + ') ' + 
                       cleaned.substring(3, 6) + '-' + 
                       cleaned.substring(6);
            } else {
                // Other formats - add dashes for readability
                return cleaned;
            }
        }
    };
});