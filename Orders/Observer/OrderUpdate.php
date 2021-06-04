<?php
/**
 * OrderUpdate.php
 * 
 * When the order is updated it's getting rid of the free prices we've set
 * on extra items
 */
namespace DetectorDist\Orders\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\RequestInterface;
use \Magento\Catalog\Model\ProductRepository;
use \Magento\Checkout\Model\Cart;
use \Magento\Framework\Data\Form\FormKey;
use \Magento\Catalog\Api\Data\ProductInterface;
use \Magento\Catalog\Model\Product;
//use \Magento\Framework\Logger\Monolog;
use \Psr\Log\LoggerInterface;
use \Magento\Framework\Exception\NoSuchEntityException;

class OrderUpdate implements ObserverInterface
{
    /**
     * @var ProductRepository
     */
    protected $_productRepository;
    /**
     * @var Cart
     */
    protected $_cart;
    /**
     * @var FormKey
     */
    protected $_formKey;
    /** 
     * @var \Magento\Framework\Logger\Monolog 
     */
    protected $_logger;

    /**
     * @param   \Magento\Catalog\Model\ProductRepository 
     * @param   \Magento\Checkout\Model\Cart 
     * @param   \Magento\Framework\Data\Form\FormKey 
     * @param   Psr\Log\LoggerInterface
     */
    public function __construct(ProductRepository $productRepository, Cart $cart, FormKey $formKey, LoggerInterface $logger)
    {
        $this->_productRepository = $productRepository;
        $this->_cart = $cart;
        $this->_formKey = $formKey;
        $this->_logger = $logger;
    }
    
    /**
     * 
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $this->_logger->info('DetectorDist\Orders\Observer\OrderUpdate:execute');
        
        $info = $observer->getEvent()->getData('info');
        $quote = $this->_cart->getQuote();
        // ['cart' => $this, 'info' => $infoDataObject]
        
        /**
         * Go through each item, and find if it's an "exta item".
         * if it is then set the price to zero again.
         */
        
        $this->_logger->info('[DONE] DetectorDist\Orders\Observer\OrderUpdate:execute');
    }
    
    /**
     * @return  bool
     */
    protected function _isExtraItem()
    {
        
    }
    
    /**
     * @return  array
     */
    protected function _findExtraItems()
    {
        
    }
    
    /**
     * Add extras to the cart.
     * 
     * @param   array
     * @return  void
     * 
     */
    protected function _addItemsToCart($skus)
    {
        $quote = $this->_cart->getQuote();
        
        foreach($skus as $sku) {
            try {
                $_product = $this->_productRepository->get($sku);
                $_product->setPrice(0);
                $_product->setSpecialPrice(0);
                $this->_cart->addProduct($_product, [
                    'qty'           => 1,
                    'price'         => 0,
                    'special_price' => 0,
                ]);
                $item = $quote->getItemByProduct($_product);
                /* @var $item \Magento\Quote\Model\Quote\Item */
                $item->setBasePrice(0);
                $item->setBasePriceInclTax(0);
                $item->setPrice(0);
                $item->setBaseTaxAmount(0);
                $item->save();
                $quote->save();
            }
            catch(NoSuchEntityException $ex) {
                $this->_logger->error("Extra Product Doesnt Exist", [$sku]);
            }
        }
    }
    
    /**
     * 
     * @param   ProductInterface $product
     * @return  bool
     */
    protected function _hasExtraProducts(ProductInterface $product)
    {
        /* @var $product \Magento\Catalog\Model\Product */
        
        return $product->getHasExtraItems() == 1;
    }
    
    /**
     * @param   ProductInterface $product
     * @return  array
     */
    protected function _getExtraProducts(ProductInterface $product)
    {
        /* @var $product \Magento\Catalog\Model\Product */
        
        if(empty($product->getExtraItemsList())) {
            return [];
        }
        
        return explode(',', $product->getExtraItemsList());
    }
}
