<?php
/**
 * AfterOrderCreated.php
 */
namespace DetectorDist\Orders\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
//use \Magento\Framework\Logger\Monolog;
use \Psr\Log\LoggerInterface;
use \Magento\Sales\Model\Order;

class AfterOrderCreated implements ObserverInterface
{
    const TEMANDO_METHOD = 'temando_daff9ef0-7dd5-464e-b5d1-c2eaa744a5b8';
    const XML_KEY_ENABLED = 'dd_section/general/dd_enable';
    const XML_KEY_SHIPPING_METHOD = 'dd_section/general/dd_shipping_method';
    
    /** 
     * @var \Magento\Framework\Logger\Monolog 
     */
    protected $_logger;
    /**
     * @var ScopeConfigInterface
     */
    protected $_scope;

    /**
     * @param   ScopeConfigInterface
     * @param   Psr\Log\LoggerInterface
     */
    public function __construct(
        ScopeConfigInterface $scope,
        LoggerInterface $logger
    )
    {
        $this->_logger = $logger;
        $this->_scope = $scope;
    }
    
    /**
     * @todo    stop higher quantity of free procuts being added than the paid parent.
     * 
     * @param   \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $this->_logger->info('DetectorDist\Orders\Observer\AfterOrderCreated:execute');
        
        $order = $observer->getEvent()->getOrder();
        /* @var $order \Magento\Sales\Model\Order */
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $isEnabled = $this->_scope->getValue(self::XML_KEY_ENABLED, $storeScope);
        $method = $this->_scope->getValue(self::XML_KEY_SHIPPING_METHOD, $storeScope);
        
        if( ! $isEnabled) {
            $this->_logger->info("[DISABLED] module" . $order->getId());
            return;
        }
          
        if(empty($method)) {
            $method = self::TEMANDO_METHOD;
        }
        
        try {
            $this->_logger->info("[ORDER] " . $order->getId());
            $this->_logger->info("Changing Shipping Method From: " . $order->getShippingMethod());
            $order->setShippingMethod($method);
            $this->_logger->info("Setting Shipping Method: " . $method);
            $order->save();
            //$shippingAddress = $order->getShippingAddress();
        } 
        catch (\Exception $ex) {
            
            $this->_logger->error($ex->getMessage());
        }
        
        
        $this->_logger->info('[DONE] DetectorDist\Orders\Observer\AfterOrderCreated:execute');
    }
}
