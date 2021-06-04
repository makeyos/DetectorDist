<?php
/**
 * Order.php
 * 
 * NOT IN USE.
 * 
 * This method did not cover M2E pro, we optned for a plugin instead.
 * This also showed free items to the customer, which was unwanted.
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
use \Magento\Framework\Message\ManagerInterface;

class Order implements ObserverInterface
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
     * @var ManagerInterface
     */
    protected $_messageManager;
    /**
     * @var \Magento\Framework\App\RequestInterface $request
     */
    protected $_request;

    /**
     * @param   \Magento\Catalog\Model\ProductRepository 
     * @param   \Magento\Checkout\Model\Cart 
     * @param   \Magento\Framework\Data\Form\FormKey 
     * @param   Psr\Log\LoggerInterface
     */
    public function __construct(
            ProductRepository $productRepository, 
            Cart $cart, 
            FormKey $formKey, 
            LoggerInterface $logger,
            ManagerInterface $messageManager,
            RequestInterface $request
            //PriceHelper $helper,
    )
    {
        $this->_productRepository = $productRepository;
        $this->_cart = $cart;
        $this->_formKey = $formKey;
        $this->_logger = $logger;
        $this->_messageManager = $messageManager;
        $this->_request = $request;
    }
    
    /**
     * @todo    stop higher quantity of free procuts being added than the paid parent.
     * 
     * @param   \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $this->_logger->info('DetectorDist\Orders\Observer\Order:execute');
        
        $product = $observer->getEvent()->getData('product');
        /* @var $product \Magento\Catalog\Model\Product */
        $originalItem = $observer->getEvent()->getData('quote_item');
        //$item = ($item->getParentItem() ? $item->getParentItem() : $item);
        
        /**
         * Simple only
         */
        if( ! $product->getTypeId() == \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE) {
            $this->_logger->info("Skip product, not simple");
            return;
        }
        
        /**
         * Skip products without "extras"
         */
        if( ! $this->_hasExtraProducts($product)) {
            $this->_logger->info(sprintf("[%s] has NO extra products", $product->getSku()));
            return;
        }
        
        $extraProducts = $this->_getExtraProducts($product);
        $this->_logger->info(sprintf("[%s] has extra products", $product->getSku()));
        $this->_logger->info('Extra products', $extraProducts);
        
        /**
         * Add extras to cart!
         */
        try {
            $this->_addItemsToCart($extraProducts, $originalItem);
            //$this->_cart->getItems();
            
        } 
        catch (\Exception $ex) {
            $this->_logger->error("[ERROR] adding extra items");
            $this->_logger->error($ex->getMessage());
        }
        
        $this->_logger->info('[DONE] DetectorDist\Orders\Observer\Order:execute');
    }
    
    /**
     * Add extras to the cart.
     * 
     * @param   array
     * @param   \Magento\Quote\Model\Quote\Item
     * @return  void
     */
    protected function _addItemsToCart($skus, \Magento\Quote\Model\Quote\Item $originalItem)
    {
        $quote = $this->_cart->getQuote();
        //$qty = $originalItem->getQty(); // this adds too many as it adds total each time.
        $successfulltAdded = [];
        
        foreach($skus as $sku) {
            try {
                $_product = $this->_productRepository->get($sku);
                $_product->setPrice(0);
                $_product->setSpecialPrice(0);
                //$_product->setName(sprintf("%s (FREE WITH %s)", $_product->getName(), $sku));
                //$_product->setIsSuperMode(TRUE);
                $this->_cart->addProduct($_product, [
                    'qty'           => $this->_getQtyAdded(),
                    'price'         => 0,
                    'special_price' => 0,
                ]);
                $item = $quote->getItemByProduct($_product);
                /* @var $item \Magento\Quote\Model\Quote\Item */
                $item->getProduct()->setIsSuperMode(TRUE);
                //$productName = $item->getProduct()->getName();
                //$item->setName(sprintf("%s (FREE WITH %s)", $productName, $sku));
                $item->setBasePrice(0);
                $item->setBasePriceInclTax(0);
                $item->setPrice(0);
                $item->setBaseTaxAmount(0);
                $item->setCustomPrice(0);
                $item->setOriginalCustomPrice(0);
                $item->save();
                $quote->save();
                $successfulltAdded[] = $sku;
            }
            catch(NoSuchEntityException $ex) {
                $this->_logger->error("Extra Product Doesnt Exist", [$sku]);
            }
        }
        
        $this->_messageManager->addSuccessMessage(sprintf(
            "FREE item(s) added to cart: %s", implode(', ', $successfulltAdded)
        ));
    }
    
    /**
     * Find how many added to cart so we can add that many free items
     * 
     * @param   int
     */
    protected function _getQtyAdded($default = 1)
    {
        return $this->_request->getParam('qty', $default);
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
