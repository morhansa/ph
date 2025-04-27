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

namespace MagoArab\PhoneMailer\Api;

/**
 * Interface for WhatsApp service
 * @api
 */
interface WhatsappServiceInterface
{
    /**
     * Send WhatsApp message
     *
     * @param string $phone
     * @param string $message
     * @param array $params
     * @param int|null $storeId
     * @return bool
     */
    public function sendMessage($phone, $message, $params = [], $storeId = null);

    /**
     * Send notification for order placement
     *
     * @param \Magento\Sales\Model\Order $order
     * @param int|null $storeId
     * @return bool
     */
    public function sendOrderNotification($order, $storeId = null);

    /**
     * Test WhatsApp connection
     *
     * @param string|null $apiKey
     * @param string|null $instanceId
     * @param int|null $storeId
     * @return array
     */
    public function testConnection($apiKey = null, $instanceId = null, $storeId = null);
}