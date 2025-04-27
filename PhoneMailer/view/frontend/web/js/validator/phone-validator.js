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
    'mage/translate'
], function ($, $t) {
    'use strict';

    return {
        /**
         * Validate phone number
         * 
         * @param {String} value
         * @returns {Boolean}
         */
        validate: function (value) {
            // Skip empty values - use required validation for that
            if (value === '' || value === null || value === undefined) {
                return true;
            }
            
            // Remove any whitespace, parentheses, dashes, etc.
            var cleanValue = value.replace(/\s|\(|\)|\-|\+/g, '');
            
            // Check if the cleaned value consists only of digits
            if (!/^\d+$/.test(cleanValue)) {
                return false;
            }
            
            // Check the length (minimum 7 digits, maximum 15 digits)
            if (cleanValue.length < 7 || cleanValue.length > 15) {
                return false;
            }
            
            return true;
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
            
            // Remove any non-digit except for leading +
            var formatted = phoneNumber.replace(/[^\d+]/g, '');
            
            // Format international numbers differently if they start with +
            if (formatted.startsWith('+')) {
                // Keep the + and first country code digits clean
                return formatted;
            } else {
                // Format as a local number
                if (formatted.length > 10) {
                    return formatted.substring(0, formatted.length - 10) + '-' + 
                           formatted.substring(formatted.length - 10, formatted.length - 7) + '-' +
                           formatted.substring(formatted.length - 7, formatted.length - 4) + '-' +
                           formatted.substring(formatted.length - 4);
                } else if (formatted.length > 7) {
                    return formatted.substring(0, formatted.length - 7) + '-' +
                           formatted.substring(formatted.length - 7, formatted.length - 4) + '-' +
                           formatted.substring(formatted.length - 4);
                } else {
                    return formatted;
                }
            }
        },
        
        /**
         * Clean phone number (remove non-digits except for leading +)
         * 
         * @param {String} phoneNumber
         * @returns {String}
         */
        cleanPhoneNumber: function (phoneNumber) {
            if (!phoneNumber) {
                return '';
            }
            
            // Remove any non-digit except for leading +
            var clean = phoneNumber.replace(/\s|\(|\)|\-/g, '');
            
            // If it starts with +, keep it, otherwise remove all non-digits
            if (clean.startsWith('+')) {
                return clean;
            } else {
                return clean.replace(/\D/g, '');
            }
        },
        
        /**
         * Generate email from phone
         * 
         * @param {String} phoneNumber
         * @returns {String}
         */
        generateEmailFromPhone: function (phoneNumber) {
            // Clean the phone number
            var cleanPhone = this.cleanPhoneNumber(phoneNumber);
            
            if (!cleanPhone) {
                return '';
            }
            
            // Get domain from current page
            var domain = window.location.hostname;
            domain = domain.replace('www.', '');
            
            // Generate email
            return cleanPhone + '@' + domain;
        }
    };
});