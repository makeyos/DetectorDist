<?php
/**
 * QuoteSubmit.php
 * 
 * This will also be triggered by M2e Pro, as well as fontend and backend Order creation.
 * 
 * This works, apart from the fact the Quote items have already been added to the quote!
 * see sales_model_service_quote_submit_before.
 * we would need to manually add, or use the QuoteConverter to do it.
 * 
 * could try injecting that, not sure if there's one instance tho..
 */
namespace DetectorDist\Orders\Observer;

use Magento\Framework\Event\ObserverInterface;
use \Magento\Catalog\Model\ProductRepository;
use \Magento\Catalog\Api\Data\ProductInterface;
use \Magento\Catalog\Model\Product;
use \Magento\Quote\Model\Quote;
use \Magento\Quote\Model\Quote\Item;
use \Magento\Sales\Model\Order;
//use \Magento\Framework\Logger\Monolog;
use \Psr\Log\LoggerInterface;
use \Magento\Framework\Exception\NoSuchEntityException;

class QuoteSubmit implements ObserverInterface
{
    /**
     * @var ProductRepository
     */
    protected $_productRepository;
    /** 
     * @var \Magento\Framework\Logger\Monolog 
     */
    protected $_logger;

    /**
     * @param   \Magento\Catalog\Model\ProductRepository 
     * @param   Psr\Log\LoggerInterface
     */
    public function __construct(
            ProductRepository $productRepository, 
            LoggerInterface $logger
            //\Magento\Quote\Model\QuoteManagement $management
    )
    {
        $this->_productRepository = $productRepository;
        $this->_logger = $logger;
    }
    
    /**
     * 
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $this->_logger->info('DetectorDist\Orders\Observer\QuoteSubmit:execute');
        
        $order = $observer->getOrder();
        $quote = $observer->getQuote();
        /* @var $quote \Magento\Quote\Model\Quote */
        
        $this->_addExtraItemsToQuote($quote);
        
        
        $this->_logger->info('[DONE] DetectorDist\Orders\Observer\QuoteSubmit:execute');
    }
    
    /**
     * Add all extra items to quote
     * 
     * @param   \Magento\Quote\Model\Quote\
     */
    protected function _addExtraItemsToQuote(Quote $quote)
    {
        $quoteItems = []; // sku => qty
        
        foreach ($quote->getAllVisibleItems() as $quoteItem) {
            /* @var $$quoteItem \Magento\Quote\Model\Quote\Item */
            
            //$this->_logger->info('original item', [$quoteItem->getData()]); ////
            //$this->_logger->info('original prod', [$quoteItem->getProduct()->getData()]); ///
            
            //$product = $quoteItem->getProduct();
            // need full product?
            $product = $this->_productRepository->get($quoteItem->getSku());
            /**
             * Simples Only.
             */
            if($product->getTypeId() != \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE) {
                $this->_logger->info("Skip product, not simple");
                continue;
            }
            
            /**
            * Skip products without "extras"
            */
            if( ! $this->_hasExtraProducts($product)) {
                $this->_logger->info(sprintf(
                   "[%s] NO extra products", 
                   $product->getSku()
                ));
                continue;
            }
            
            $extraProducts = $this->_getExtraProducts($product);
            $this->_logger->info('Extra products', $extraProducts);
            
            /**
             * Add each Extra Item to the quote, or update it's Qty.
             */
            foreach($extraProducts as $extraProductSku) {
                $this->_addExtraItemToQuote($quote, $quoteItem, $extraProductSku);
            }
            
            $quoteItems[$quoteItem->getId()] = $quoteItem;
        }
    }
    
    /**
     * @param   \Magento\Quote\Model\Quote\
     * @param   \Magento\Quote\Model\Quote\Item
     * @param   string
     * @return  void
     */
    protected function _addExtraItemToQuote(Quote $quote, Item $parentItem, $sku)
    {
        $this->_logger->info(sprintf('[Add] Extra Item [%s]', $sku));
        
         try {
            $_product = $this->_productRepository->get($sku);
            $_product->setPrice(0);
            $_product->setSpecialPrice(0);
            
            /**
             *  Not already added by a parent product
             */
            $item = $quote->getItemByProduct($_product);
            /* @var $item \Magento\Quote\Model\Quote\Item */
            
            /**
             * Add the item, it's not already been added.
             */
            if($item === FALSE) {
                $this->_logger->info(
                    sprintf('[Add] NEW Quote Item [%s] [%s]', $sku, $parentItem->getQty())
                );
                $quote->addProduct($_product, $parentItem->getQty());
                $item = $quote->getItemByProduct($_product);
                
                $this->_logger->info('new item', [$item->getData()]);
                
                $item->getProduct()->setIsSuperMode(TRUE);
                //$productName = $item->getProduct()->getName();
                //$item->setName(sprintf("%s (FREE WITH %s)", $productName, $sku));
                $item->setBasePrice(0);
                $item->setBasePriceInclTax(0);
                $item->setPrice(0);
                $item->setBaseTaxAmount(0);
                $item->setCustomPrice(0);
                $item->setOriginalCustomPrice(0);
            }
            /**
             * Already added from another product
             */
            else {
                $newQty = $item->getQty() + $parentItem->getQty();
                $this->_logger->info(
                    sprintf('[UPDATE] Quote Item [%s] [%s]', $sku, $newQty)
                );
                //$item->addQty($item->getQty());
                $item->setQty($newQty);
            }
            
            $item->save();
            $quote->save();
        }
        catch(NoSuchEntityException $ex) {
            $this->_logger->error("Extra Product Doesnt Exist", [$sku]);
        }
        catch(\Exception $ex) {
            $this->_logger->error("[Error] adding item", [$ex->getMessage()]);
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
