<?php


namespace MagoArab\PhoneMailer\Test\Unit\Model;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use MagoArab\PhoneMailer\Model\WhatsappService;
use MagoArab\PhoneMailer\Helper\Config;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\Serializer\Json;
use Psr\Log\LoggerInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Model\Store;
use Magento\Sales\Model\Order\Address;

class WhatsappServiceTest extends TestCase
{
    /**
     * @var Config|MockObject
     */
    private $configMock;

    /**
     * @var Curl|MockObject
     */
    private $curlMock;

    /**
     * @var Json|MockObject
     */
    private $jsonMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var WhatsappService
     */
    private $whatsappService;

    /**
     * Setup test environment
     */
    protected function setUp(): void
    {
        $this->configMock = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->curlMock = $this->getMockBuilder(Curl::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->jsonMock = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();

        $this->whatsappService = new WhatsappService(
            $this->configMock,
            $this->curlMock,
            $this->jsonMock,
            $this->loggerMock
        );
    }

    /**
     * Test sendMessage method when WhatsApp is disabled
     */
    public function testSendMessageWhenWhatsappDisabled()
    {
        $phone = '1234567890';
        $message = 'Test message';
        $params = [];
        $storeId = 1;
        
        // Configure mocks
        $this->configMock->expects($this->once())
            ->method('isWhatsappEnabled')
            ->with($storeId)
            ->willReturn(false);
        
        // Test the method
        $result = $this->whatsappService->sendMessage($phone, $message, $params, $storeId);
        
        // Assert result
        $this->assertFalse($result);
    }

    /**
     * Test sendMessage method when WhatsApp is enabled
     */
    public function testSendMessageWhenWhatsappEnabled()
    {
        $phone = '1234567890';
        $message = 'Test message with {{param}}';
        $params = ['param' => 'value'];
        $storeId = 1;
        $apiKey = 'test-api-key';
        $instanceId = 'test-instance-id';
        $endpoint = 'https://api.whatsapp.com/v1/messages';
        $requestData = [
            'to' => '1234567890',
            'type' => 'text',
            'text' => [
                'body' => 'Test message with value'
            ]
        ];
        $responseData = [
            'messages' => [
                ['id' => 'msg123']
            ]
        ];
        
        // Configure mocks
        $this->configMock->expects($this->once())
            ->method('isWhatsappEnabled')
            ->with($storeId)
            ->willReturn(true);
        
        $this->configMock->expects($this->once())
            ->method('getWhatsappApiKey')
            ->with($storeId)
            ->willReturn($apiKey);
        
        $this->configMock->expects($this->once())
            ->method('getWhatsappInstanceId')
            ->with($storeId)
            ->willReturn($instanceId);
        
        $this->jsonMock->expects($this->once())
            ->method('serialize')
            ->with($requestData)
            ->willReturn('{"serialized":"data"}');

        $this->curlMock->expects($this->exactly(2))
            ->method('addHeader')
            ->withConsecutive(
                ['Content-Type', 'application/json'],
                ['Authorization', 'Bearer ' . $apiKey]
            );
            
        $this->curlMock->expects($this->once())
            ->method('post')
            ->with($endpoint, '{"serialized":"data"}');
            
        $this->curlMock->expects($this->once())
            ->method('getBody')
            ->willReturn('{"response":"data"}');

        $this->jsonMock->expects($this->once())
            ->method('unserialize')
            ->with('{"response":"data"}')
            ->willReturn($responseData);
        
        // Test the method
        $result = $this->whatsappService->sendMessage($phone, $message, $params, $storeId);
        
        // Assert result
        $this->assertTrue($result);
    }

    /**
     * Test sendOrderNotification method
     */
    public function testSendOrderNotification()
    {
        $storeId = 1;
        $phone = '1234567890';
        $orderIncrement = '10000001';
        $customerName = 'John Doe';
        $total = '$100.00';
        $storeName = 'Test Store';
        $message = 'Thank you for your order #{{order_id}}';
        $templates = ['order_confirmation' => $message];
        
        // Create order mock
        $orderMock = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->getMock();
            
        $orderMock->expects($this->once())
            ->method('getStoreId')
            ->willReturn($storeId);
            
        $orderMock->expects($this->once())
            ->method('getIncrementId')
            ->willReturn($orderIncrement);
            
        $orderMock->expects($this->atLeastOnce())
            ->method('getCustomerName')
            ->willReturn($customerName);
            
        $orderMock->expects($this->once())
            ->method('formatPriceTxt')
            ->willReturn($total);
            
        // Create store mock
        $storeMock = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->getMock();
            
        $storeMock->expects($this->once())
            ->method('getFrontendName')
            ->willReturn($storeName);
            
        $orderMock->expects($this->once())
            ->method('getStore')
            ->willReturn($storeMock);
            
        // Create address mock
        $addressMock = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->getMock();
            
        $addressMock->expects($this->once())
            ->method('getTelephone')
            ->willReturn($phone);
            
        $orderMock->expects($this->once())
            ->method('getBillingAddress')
            ->willReturn($addressMock);
            
        // Configure config mock
        $this->configMock->expects($this->once())
            ->method('isWhatsappEnabled')
            ->with($storeId)
            ->willReturn(true);
            
        $this->configMock->expects($this->once())
            ->method('getWhatsappTemplates')
            ->with($storeId)
            ->willReturn($templates);
            
        // Configure service mock for sending message
        $whatsappServiceMock = $this->getMockBuilder(WhatsappService::class)
            ->setMethods(['sendMessage'])
            ->setConstructorArgs([
                $this->configMock,
                $this->curlMock,
                $this->jsonMock,
                $this->loggerMock
            ])
            ->getMock();
            
        $expectedParams = [
            'order_id' => $orderIncrement,
            'customer_name' => $customerName,
            'total' => $total,
            'store_name' => $storeName
        ];
        
        $whatsappServiceMock->expects($this->once())
            ->method('sendMessage')
            ->with($phone, $message, $expectedParams, $storeId)
            ->willReturn(true);
            
        // Test the method
        $result = $whatsappServiceMock->sendOrderNotification($orderMock, $storeId);
        
        // Assert result
        $this->assertTrue($result);
    }
}