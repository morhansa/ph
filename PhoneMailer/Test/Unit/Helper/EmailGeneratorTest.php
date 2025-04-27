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


namespace MagoArab\PhoneMailer\Test\Unit\Helper;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Api\Data\StoreInterface;
use MagoArab\PhoneMailer\Helper\EmailGenerator;
use MagoArab\PhoneMailer\Helper\Config;
use Psr\Log\LoggerInterface;

class EmailGeneratorTest extends TestCase
{
    /**
     * @var Context|MockObject
     */
    private $contextMock;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManagerMock;

    /**
     * @var Config|MockObject
     */
    private $configMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var EmailGenerator
     */
    private $emailGenerator;

    /**
     * Setup test environment
     */
    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->storeManagerMock = $this->getMockBuilder(StoreManagerInterface::class)
            ->getMockForAbstractClass();

        $this->configMock = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();

        $this->emailGenerator = new EmailGenerator(
            $this->contextMock,
            $this->storeManagerMock,
            $this->configMock,
            $this->loggerMock
        );
    }

    /**
     * Test generateEmailFromPhone method with auto domain mode
     */
    public function testGenerateEmailFromPhoneWithAutoDomain()
    {
        $phoneNumber = '1234567890';
        $storeId = 1;
        $domain = 'example.com';
        
        // Configure mocks
        $this->configMock->expects($this->once())
            ->method('getDomainMode')
            ->with($storeId)
            ->willReturn('auto');
        
        $storeMock = $this->getMockBuilder(StoreInterface::class)
            ->getMockForAbstractClass();
        
        $storeMock->expects($this->once())
            ->method('getBaseUrl')
            ->willReturn('https://www.example.com/');
        
        $this->storeManagerMock->expects($this->once())
            ->method('getStore')
            ->with($storeId)
            ->willReturn($storeMock);
        
        // Test the method
        $result = $this->emailGenerator->generateEmailFromPhone($phoneNumber, $storeId);
        
        // Assert result
        $this->assertEquals('1234567890@example.com', $result);
    }

    /**
     * Test generateEmailFromPhone method with custom domain mode
     */
    public function testGenerateEmailFromPhoneWithCustomDomain()
    {
        $phoneNumber = '1234567890';
        $storeId = 1;
        $customDomain = 'custom-domain.com';
        
        // Configure mocks
        $this->configMock->expects($this->once())
            ->method('getDomainMode')
            ->with($storeId)
            ->willReturn('custom');
        
        $this->configMock->expects($this->once())
            ->method('getCustomDomain')
            ->with($storeId)
            ->willReturn($customDomain);
        
        // Test the method
        $result = $this->emailGenerator->generateEmailFromPhone($phoneNumber, $storeId);
        
        // Assert result
        $this->assertEquals('1234567890@custom-domain.com', $result);
    }

    /**
     * Test generateEmailFromPhone method with phone formatting
     */
    public function testGenerateEmailFromPhoneWithFormatting()
    {
        $phoneNumber = '+1 (234) 567-890';
        $storeId = 1;
        $domain = 'example.com';
        
        // Configure mocks
        $this->configMock->expects($this->once())
            ->method('getDomainMode')
            ->with($storeId)
            ->willReturn('auto');
        
        $storeMock = $this->getMockBuilder(StoreInterface::class)
            ->getMockForAbstractClass();
        
        $storeMock->expects($this->once())
            ->method('getBaseUrl')
            ->willReturn('https://www.example.com/');
        
        $this->storeManagerMock->expects($this->once())
            ->method('getStore')
            ->with($storeId)
            ->willReturn($storeMock);
        
        // Test the method
        $result = $this->emailGenerator->generateEmailFromPhone($phoneNumber, $storeId);
        
        // Assert result
        $this->assertEquals('1234567890@example.com', $result);
    }

    /**
     * Test generateEmailFromPhone method with exception
     */
    public function testGenerateEmailFromPhoneWithException()
    {
        $phoneNumber = '';
        $storeId = 1;
        
        // Set expectation for exception
        $this->expectException(\Exception::class);
        
        // Test the method
        $this->emailGenerator->generateEmailFromPhone($phoneNumber, $storeId);
    }
}