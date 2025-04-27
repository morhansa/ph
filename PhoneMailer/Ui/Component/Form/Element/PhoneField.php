<?php


namespace MagoArab\PhoneMailer\Ui\Component\Form\Element;

use Magento\Ui\Component\Form\Element\Input;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use MagoArab\PhoneMailer\Helper\Config;

class PhoneField extends Input
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * PhoneField constructor.
     *
     * @param ContextInterface $context
     * @param Config $config
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        Config $config,
        array $components = [],
        array $data = []
    ) {
        $this->config = $config;
        parent::__construct($context, $components, $data);
    }

    /**
     * Prepare component configuration
     *
     * @return void
     */
    public function prepare()
    {
        $config = $this->getData('config');
        
        // Add custom validation
        if (!isset($config['validation'])) {
            $config['validation'] = [];
        }
        
        // Add phone validation
        $config['validation']['phone-number'] = true;
        
        // Make required if PhoneMailer is enabled
        if ($this->config->isEnabled()) {
            $config['validation']['required-entry'] = true;
        }
        
        // Add custom class
        if (!isset($config['additionalClasses'])) {
            $config['additionalClasses'] = '';
        }
        $config['additionalClasses'] .= ' phonemail-phone-field';
        
        // Add custom placeholder
        if (!isset($config['placeholder'])) {
            $config['placeholder'] = __('Phone Number');
        }
        
        // Set configuration
        $this->setData('config', $config);
        
        parent::prepare();
    }
}