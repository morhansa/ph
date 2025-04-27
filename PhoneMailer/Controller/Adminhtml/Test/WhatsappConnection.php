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

namespace MagoArab\PhoneMailer\Controller\Adminhtml\Test;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use MagoArab\PhoneMailer\Api\WhatsappServiceInterface;

class WhatsappConnection extends Action
{
    /**
     * Authorization level
     */
    const ADMIN_RESOURCE = 'MagoArab_PhoneMailer::config';

    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var WhatsappServiceInterface
     */
    protected $whatsappService;

    /**
     * WhatsappConnection constructor.
     *
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param WhatsappServiceInterface $whatsappService
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        WhatsappServiceInterface $whatsappService
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->whatsappService = $whatsappService;
    }

    /**
     * Test WhatsApp connection
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        
        try {
            $params = $this->getRequest()->getParams();
            $apiKey = isset($params['api_key']) ? $params['api_key'] : null;
            $instanceId = isset($params['instance_id']) ? $params['instance_id'] : null;
            
            // Test connection
            $testResult = $this->whatsappService->testConnection($apiKey, $instanceId);
            
            return $result->setData($testResult);
        } catch (\Exception $e) {
            return $result->setData([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}