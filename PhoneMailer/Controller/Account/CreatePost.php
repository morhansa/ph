<?php


namespace MagoArab\PhoneMailer\Controller\Account;

use Magento\Customer\Controller\Account\CreatePost as MagentoCreatePost;
use Magento\Framework\App\Action\Context;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Helper\Address;
use Magento\Framework\UrlFactory;
use Magento\Customer\Model\Metadata\FormFactory;
use Magento\Newsletter\Model\SubscriberFactory;
use Magento\Customer\Api\Data\RegionInterfaceFactory;
use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Customer\Model\Url as CustomerUrl;
use Magento\Customer\Model\Registration;
use Magento\Framework\Escaper;
use Magento\Customer\Model\CustomerExtractor;
use Magento\Framework\Exception\StateException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use MagoArab\PhoneMailer\Helper\EmailGenerator;
use MagoArab\PhoneMailer\Helper\Config;
use Psr\Log\LoggerInterface;

class CreatePost extends MagentoCreatePost
{
    /**
     * @var EmailGenerator
     */
    protected $emailGenerator;

    /**
     * @var Config
     */
    protected $moduleConfig;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param Context $context
     * @param Session $customerSession
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param AccountManagementInterface $accountManagement
     * @param Address $addressHelper
     * @param UrlFactory $urlFactory
     * @param FormFactory $formFactory
     * @param SubscriberFactory $subscriberFactory
     * @param RegionInterfaceFactory $regionDataFactory
     * @param AddressInterfaceFactory $addressDataFactory
     * @param CustomerInterfaceFactory $customerDataFactory
     * @param CustomerUrl $customerUrl
     * @param Registration $registration
     * @param Escaper $escaper
     * @param CustomerExtractor $customerExtractor
     * @param EmailGenerator $emailGenerator
     * @param Config $moduleConfig
     * @param LoggerInterface $logger
     * @param array $data
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        AccountManagementInterface $accountManagement,
        Address $addressHelper,
        UrlFactory $urlFactory,
        FormFactory $formFactory,
        SubscriberFactory $subscriberFactory,
        RegionInterfaceFactory $regionDataFactory,
        AddressInterfaceFactory $addressDataFactory,
        CustomerInterfaceFactory $customerDataFactory,
        CustomerUrl $customerUrl,
        Registration $registration,
        Escaper $escaper,
        CustomerExtractor $customerExtractor,
        EmailGenerator $emailGenerator,
        Config $moduleConfig,
        LoggerInterface $logger,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $customerSession,
            $scopeConfig,
            $storeManager,
            $accountManagement,
            $addressHelper,
            $urlFactory,
            $formFactory,
            $subscriberFactory,
            $regionDataFactory,
            $addressDataFactory,
            $customerDataFactory,
            $customerUrl,
            $registration,
            $escaper,
            $customerExtractor,
            $data
        );
        $this->emailGenerator = $emailGenerator;
        $this->moduleConfig = $moduleConfig;
        $this->logger = $logger;
    }

    /**
     * Create customer account action
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        if ($this->session->isLoggedIn() || !$this->registration->isAllowed()) {
            $resultRedirect->setPath('*/*/');
            return $resultRedirect;
        }

        if (!$this->getRequest()->isPost()) {
            $resultRedirect->setPath('*/*/create');
            return $resultRedirect;
        }

        $this->session->regenerateId();

        try {
            // Check if PhoneMailer is enabled
            if ($this->moduleConfig->isEnabled()) {
                // Get post data
                $data = $this->getRequest()->getPostValue();
                
                // Check if we have a telephone but no email
                if (isset($data['telephone']) && (!isset($data['email']) || empty($data['email']))) {
                    // Generate email from telephone
                    $email = $this->emailGenerator->generateEmailFromPhone($data['telephone']);
                    
                    // Add the generated email to POST data
                    $data['email'] = $email;
                    $this->getRequest()->setPostValue($data);
                    
                    $this->logger->info('PhoneMailer: Generated email ' . $email . ' for registration form');
                }
            }
            
            // Continue with normal execution
            return parent::execute();
        } catch (StateException $e) {
            $this->messageManager->addComplexErrorMessage(
                'customerAlreadyExistsErrorMessage',
                [
                    'url' => $this->urlModel->getUrl('customer/account/forgotpassword'),
                ]
            );
        } catch (InputException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            foreach ($e->getErrors() as $error) {
                $this->messageManager->addErrorMessage($error->getMessage());
            }
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('We can\'t save the customer.'));
        }

        $this->session->setCustomerFormData($this->getRequest()->getPostValue());
        $resultRedirect->setPath('*/*/create');
        return $resultRedirect;
    }
}