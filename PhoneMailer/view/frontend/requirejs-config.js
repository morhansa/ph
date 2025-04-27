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

var config = {
    config: {
        mixins: {
            'Magento_Ui/js/form/element/abstract': {
                'MagoArab_PhoneMailer/js/form/element/abstract-mixin': true
            },
            'Magento_Checkout/js/view/form/element/email': {
                'MagoArab_PhoneMailer/js/view/form/element/email-mixin': true
            },
            'Magento_Checkout/js/action/set-shipping-information': {
                'MagoArab_PhoneMailer/js/action/set-shipping-information-mixin': true
            }
        }
    },
    map: {
        '*': {
            'Magento_Checkout/template/form/element/email.html': 'MagoArab_PhoneMailer/template/form/element/email.html'
        }
    },
    paths: {
        'phoneValidator': 'MagoArab_PhoneMailer/js/validator/phone-validator'
    }
};