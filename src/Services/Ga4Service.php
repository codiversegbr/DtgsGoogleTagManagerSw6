<?php declare(strict_types=1);

namespace Dtgs\GoogleTagManager\Services;

use Dtgs\GoogleTagManager\Components\Helper\CategoryHelper;
use Dtgs\GoogleTagManager\Components\Helper\CustomerHelper;
use Dtgs\GoogleTagManager\Components\Helper\LoggingHelper;
use Dtgs\GoogleTagManager\Components\Helper\ManufacturerHelper;
use Dtgs\GoogleTagManager\Components\Helper\PriceHelper;
use Dtgs\GoogleTagManager\Components\Helper\ProductHelper;
use Dtgs\GoogleTagManager\Services\Interfaces\Ga4ServiceInterface;
use Dtgs\GoogleTagManager\Services\Interfaces\GeneralTagsServiceInterface;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\LineItem\LineItemCollection;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerWishlist\CustomerWishlistEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Promotion\Cart\PromotionProcessor;
use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Content\Product\SalesChannel\Listing\ProductListingResult;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Page\Checkout\Cart\CheckoutCartPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Register\CheckoutRegisterPageLoadedEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Ga4Service implements Ga4ServiceInterface
{
    private $systemConfigService;
    private $generalTagsService;
    /**
     * @var ContainerInterface $container
     */
    private $container;
    private $priceHelper;
    private $loggingHelper;
    /**
     * @var ProductHelper
     */
    private $productHelper;
    /**
    /**
     * @var CategoryHelper
     */
    private $categoryHelper;
    /**
     * @var ManufacturerHelper
     */
    private $manufacturerHelper;
    /**
     * @var CustomerHelper
     */
    private $customerHelper;

    public function __construct(SystemConfigService $systemConfigService,
                                GeneralTagsServiceInterface  $generalTagsService,
                                ContainerInterface  $container,
                                ProductHelper       $productHelper,
                                CategoryHelper      $categoryHelper,
                                ManufacturerHelper  $manufacturerHelper,
                                CustomerHelper      $customerHelper,
                                PriceHelper         $priceHelper,
                                LoggingHelper       $loggingHelper)
    {
        $this->container = $container;
        $this->systemConfigService = $systemConfigService;
        $this->generalTagsService = $generalTagsService;
        $this->productHelper = $productHelper;
        $this->categoryHelper = $categoryHelper;
        $this->manufacturerHelper = $manufacturerHelper;
        $this->customerHelper = $customerHelper;
        $this->priceHelper = $priceHelper;
        $this->loggingHelper = $loggingHelper;
    }

    /**
     * Maybe move to general helper
     *
     * Helper to get plugin specific config
     *
     * @return array|mixed|null
     */
    public function getGtmConfig($salesChannelId) {
        return $tagManagerConfig = $this->systemConfigService->get('DtgsGoogleTagManagerSw6.config', $salesChannelId);
    }

    /**
     * SW6 ready
     *
     */
    public function getAdwordsId($salesChannelId) {

        $tagManagerConfig = $this->getGtmConfig($salesChannelId);

        if(isset($tagManagerConfig['googleAdwordsId'])) {
            return $tagManagerConfig['googleAdwordsId'];
        }

        return false;

    }

    public function remarketingEnabled($salesChannelId) {
        $tagManagerConfig = $this->getGtmConfig($salesChannelId);
        return (isset($tagManagerConfig['remarketingIntegration']) && $tagManagerConfig['remarketingIntegration'] == 'enable');
    }

    /**
     * GH-Ticket #7
     *
     * @param $salesChannelId
     * @return bool
     */
    public function addDatabaseProductId($salesChannelId) {

        $tagManagerConfig = $this->getGtmConfig($salesChannelId);

        if(isset($tagManagerConfig['addProductDatabaseIds'])) {
            return $tagManagerConfig['addProductDatabaseIds'];
        }

        return false;

    }

    /**
     * @param $salesChannelId
     * @return bool
     */
    public function showOriginalPrice($salesChannelId): bool {

        $tagManagerConfig = $this->getGtmConfig($salesChannelId);

        if(isset($tagManagerConfig['eeShowOriginalPrice'])) {
            return (bool) $tagManagerConfig['eeShowOriginalPrice'];
        }

        return false;

    }

    /**
     * Source of the item_id value (productNumber, parentProductNumber, ean, manufacturerNumber).
     *
     * @param $salesChannelId
     * @return string
     */
    public function getItemIdSource($salesChannelId): string {

        $tagManagerConfig = $this->getGtmConfig($salesChannelId);

        if(isset($tagManagerConfig['itemIdSource']) && $tagManagerConfig['itemIdSource']) {
            return $tagManagerConfig['itemIdSource'];
        }

        return 'productNumber';

    }

    /**
     * @param $salesChannelId
     * @return bool
     */
    public function showParentId($salesChannelId): bool {

        $tagManagerConfig = $this->getGtmConfig($salesChannelId);

        if(isset($tagManagerConfig['eeShowParentId'])) {
            return (bool) $tagManagerConfig['eeShowParentId'];
        }

        return false;

    }

    /**
     * @param $salesChannelId
     * @return bool
     */
    public function showVariantId($salesChannelId): bool {

        $tagManagerConfig = $this->getGtmConfig($salesChannelId);

        if(isset($tagManagerConfig['eeShowVariantId'])) {
            return (bool) $tagManagerConfig['eeShowVariantId'];
        }

        return false;

    }

    /**
     * Resolves the item_id for a product entity based on the configured source.
     *
     * @param SalesChannelProductEntity $product
     * @param SalesChannelContext $context
     * @return string
     */
    private function resolveItemId(ProductEntity $product, SalesChannelContext $context): string {

        $source = $this->getItemIdSource($context->getSalesChannel()->getId());

        switch ($source) {
            case 'parentProductNumber':
                if($product->getParentId() !== null) {
                    $parentNumber = $this->productHelper->getProductNumberById($product->getParentId(), $context);
                    if(!empty($parentNumber)) {
                        return $parentNumber;
                    }
                }
                return $product->getProductNumber();
            case 'ean':
                return !empty($product->getEan()) ? $product->getEan() : $product->getProductNumber();
            case 'manufacturerNumber':
                return !empty($product->getManufacturerNumber()) ? $product->getManufacturerNumber() : $product->getProductNumber();
            default:
                return $product->getProductNumber();
        }

    }

    /**
     * Resolves the configured item_id for a storefront line item (cart or order line item).
     * Used e.g. for the hidden line items (data-dtgs-sku) which are read by the storefront JS.
     * Falls back to the line item product number if the real product entity cannot be loaded.
     *
     * @param mixed $lineItem
     * @param SalesChannelContext $context
     * @return string|null
     */
    public function resolveItemIdForLineItem($lineItem, SalesChannelContext $context): ?string {

        $payload = (is_object($lineItem) && method_exists($lineItem, 'getPayload')) ? $lineItem->getPayload() : [];
        $fallback = (is_array($payload) && isset($payload['productNumber'])) ? $payload['productNumber'] : null;

        // Default source does not require loading the product entity.
        if($this->getItemIdSource($context->getSalesChannel()->getId()) === 'productNumber') {
            return $fallback;
        }

        $referencedId = (is_object($lineItem) && method_exists($lineItem, 'getReferencedId')) ? $lineItem->getReferencedId() : null;
        if(empty($referencedId) || !Uuid::isValid($referencedId)) {
            return $fallback;
        }

        $product = $this->productHelper->getProductyById($referencedId, $context);
        if(!$product instanceof ProductEntity) {
            return $fallback;
        }

        $resolved = $this->resolveItemId($product, $context);

        return !empty($resolved) ? $resolved : $fallback;

    }

    /**
     * Resolves the configured item_id for a product entity (e.g. product listing cards).
     * Falls back to the product number if resolution is not possible.
     *
     * @param ProductEntity $product
     * @param SalesChannelContext $context
     * @return string|null
     */
    public function resolveItemIdForProduct(ProductEntity $product, SalesChannelContext $context): ?string {

        try {
            $resolved = $this->resolveItemId($product, $context);
            return !empty($resolved) ? $resolved : $product->getProductNumber();
        } catch (\Throwable $e) {
            return $product->getProductNumber();
        }

    }

    /**
     * Returns the configured parent_id / variant_id for a product entity (if enabled and
     * the product is a variant). Used by the storefront templates (product cards) to expose
     * the values as hidden inputs which are read by the storefront JS.
     *
     * @param ProductEntity $product
     * @param SalesChannelContext $context
     * @return array
     */
    public function getParentAndVariantId(ProductEntity $product, SalesChannelContext $context): array {

        try {
            return $this->addParentAndVariantId([], $product, $context);
        } catch (\Throwable $e) {
            return [];
        }

    }

    /**
     * Returns the configured parent_id / variant_id for a storefront line item (cart or order
     * line item), if enabled and the referenced product is a variant. Loads the real product
     * entity via the referenced id. Returns an empty array if resolution is not possible.
     *
     * @param mixed $lineItem
     * @param SalesChannelContext $context
     * @return array
     */
    public function getParentAndVariantIdForLineItem($lineItem, SalesChannelContext $context): array {

        try {
            $salesChannelId = $context->getSalesChannel()->getId();

            // Nothing to do if neither parent_id nor variant_id is enabled.
            if(!$this->showParentId($salesChannelId) && !$this->showVariantId($salesChannelId)) {
                return [];
            }

            $referencedId = (is_object($lineItem) && method_exists($lineItem, 'getReferencedId')) ? $lineItem->getReferencedId() : null;
            if(empty($referencedId) || !Uuid::isValid($referencedId)) {
                return [];
            }

            $product = $this->productHelper->getProductyById($referencedId, $context);
            if(!$product instanceof ProductEntity) {
                return [];
            }

            return $this->addParentAndVariantId([], $product, $context);
        } catch (\Throwable $e) {
            return [];
        }

    }

    /**
     * Adds parent_id / variant_id to the item array if enabled and the product is a variant.
     *
     * @param array $item
     * @param SalesChannelProductEntity $product
     * @param SalesChannelContext $context
     * @return array
     */
    private function addParentAndVariantId(array $item, ProductEntity $product, SalesChannelContext $context): array {

        if($product->getParentId() === null) {
            return $item;
        }

        $salesChannelId = $context->getSalesChannel()->getId();

        if($this->showParentId($salesChannelId)) {
            $parentNumber = $this->productHelper->getProductNumberById($product->getParentId(), $context);
            if(!empty($parentNumber)) {
                $item['parent_id'] = $parentNumber;
            }
        }

        if($this->showVariantId($salesChannelId)) {
            $item['variant_id'] = $product->getProductNumber();
        }

        return $item;

    }

    /**
     * @param $ga4Tags
     * @return false|string
     */
    public function prepareTagsForView($ga4Tags)
    {
        return json_encode($ga4Tags);
    }

    /**
     * since 6.0.5
     *
     * @param array $tags
     * @param string $event_name
     * @param string $source
     * @return array
     */
    private function addEeEvent($tags, string $event_name = '', string $source = ''): array
    {
        $event_array = [];
        if($event_name != '') $event_array = ['event' => $event_name];

        $source_array = [];
        if($source != '') $source_array = ['event_source' => $source];

        return array_merge(
            $event_array,
            $source_array,
            ['ecommerce' => $tags]
        );
    }

    /**
     * @param SalesChannelProductEntity $product
     * @param SalesChannelContext $context
     * @return mixed
     * @throws \Exception
     */
    public function getDetailTags(SalesChannelProductEntity $product, SalesChannelContext $context) {

        $ga4_tags = [];
        //Currency Code
        $ga4_tags['currency'] = $context->getCurrency()->getIsoCode();

        //New in 1.3.5 - select if brutto/netto
        $price = ($product->getCalculatedPrices()->count()) ? $product->getCalculatedPrices()->first()->getUnitPrice() : $product->getCalculatedPrice()->getUnitPrice();
        $brutto_price = (is_float($price)) ? $price : str_replace(',', '.', $price);

        $taxRate = $product->getCalculatedPrice()?->getTaxRules()->first();
        if($taxRate) {
            $tax = $taxRate->getTaxRate();
        }
        else {
            //Bugfix for tax free countries, V6.1.4
            $tax = 0;
        }

        $item_price = (float) $this->priceHelper->getPrice($brutto_price, $tax, $context);

        $product_data = [
            'item_name'      =>  $product->getTranslation('name'),
            'item_id'        =>  $this->resolveItemId($product, $context),
            'price'          =>  $item_price,
            'index' => 0, //Index auf Detailseiten immer 0
            'item_list_name' => 'Category',
            'quantity' => 1,
        ];

        //added: optional parent_id / variant_id for variant products
        $product_data = $this->addParentAndVariantId($product_data, $product, $context);

        //added in 6.3.7 - CDVRS-0000022
        if($this->getVariantName($product->getVariation())) {
            $product_data['item_variant'] = $this->getVariantName($product->getVariation());
        }

        if($this->addDatabaseProductId($context->getSalesChannel()->getId())) {
            $product_data['item_db_id'] = $product->getId();
        }

        //Product Category - Changed to SEO Category in V6.1.22
        $seoCategory = $product->getSeoCategory();
        if($seoCategory) {
            $breadcrumb = $seoCategory->getTranslation('breadcrumb');

            if (!empty($breadcrumb) && is_array($breadcrumb)) {

                $product_data = $this->mapBreadcrumbCategories($breadcrumb, $product_data);

            } else {
                $product_data['item_category'] = $seoCategory->getTranslation('name');
            }

            // Optional: the last visible category can be used for list ID
            $product_data['item_list_id'] = $seoCategory->getId();

        } else {
            $product_data['item_category'] = '';
        }

        //add Remarketing Data
        if($this->remarketingEnabled($context->getSalesChannel()->getId())) {
            $product_data['id'] = $product->getProductNumber();
            $product_data['google_business_vertical'] = 'retail';
        }

        if($product->getManufacturer())
            $product_data['item_brand'] = $product->getManufacturer()->getTranslation('name');

        //Original price for discounted items
        if($this->showOriginalPrice($context->getSalesChannel()->getId())) {
            $listPrice = $product->getCalculatedPrice()->getListPrice();
            if($listPrice !== null) {
                $originalPrice = (float) $this->priceHelper->getPrice($listPrice->getPrice(), $tax, $context);
                $product_data['item_original_price'] = $originalPrice;
                $product_data['discount'] = $this->priceHelper->formatPrice($originalPrice - $item_price);
            }
        }

        $ga4_tags['value'] = $item_price;
        $ga4_tags['items'] = [$product_data];

        /**
         * Related und Similar Articles: tbd
         */

        return $this->addEeEvent($ga4_tags, 'view_item');

    }

    /**
     * SW6 ready
     *
     * @param $navigationId
     * @param EntitySearchResult $result
     * @param SalesChannelContext $context
     * @return array
     * @throws \Exception
     */
    public function getNavigationTags($navigationId, $listing, SalesChannelContext $context, $listName = 'Category') {

        $pluginConfig = $this->getGtmConfig($context->getSalesChannel()->getId());
        $eeMaxAmountCategoriesForImpressions = (isset($pluginConfig['eeMaxAmountCategoriesForImpressions'])) ? $pluginConfig['eeMaxAmountCategoriesForImpressions'] : 0;

        $ga4_tags = [];

        $category = $this->categoryHelper->getCategoryById($navigationId, $context);

        //Currency Code
        $ga4_tags['currency'] = $context->getCurrency()->getIsoCode();
        //Added in 6.3.9
        switch ($listName) {
            case 'Category':
                $listName .= ': ' . $category->getTranslation('name');
                break;
            default:
                $listName = 'Additional: ' . ucwords($listName, '-');
                break;
        }
        $ga4_tags['item_list_name'] = $listName;
        if($category) $ga4_tags['item_list_id'] = $category->getId();

        //Impressions
        $ga4_tags['items'] = $this->getImpressions($listing, $eeMaxAmountCategoriesForImpressions, $context, $listName, $category);

        return $this->addEeEvent($ga4_tags, 'view_item_list');

    }

    /**
     * SW6 ready
     *
     * @param $cartOrOrder
     * @param $event
     * @return array
     * @throws \Exception
     */
    public function getCheckoutTags($cartOrOrder, $event) {

        $pluginConfig = $this->getGtmConfig($event->getSalesChannelContext()->getSalesChannel()->getId());
        $addCategoryNames = (isset($pluginConfig['eeAddCategorynameInCheckout'])) ? $pluginConfig['eeAddCategorynameInCheckout'] : false;
        $useNetPrices = isset($pluginConfig['showPriceType']) && $pluginConfig['showPriceType'] == 'netto';

        $ga4_tags = [];
        //Currency Code
        $ga4_tags['currency'] = $event->getSalesChannelContext()->getCurrency()->getIsoCode();
        $ga4_tags['value'] = ($useNetPrices) ? $cartOrOrder->getPrice()->getNetPrice() : $cartOrOrder->getPrice()->getTotalPrice();
        $ga4_tags['items'] = $this->getBasketItems($cartOrOrder->getLineItems(), $event->getSalesChannelContext(), $addCategoryNames, 'checkout');

        return $this->addEeEvent($ga4_tags, $this->getCheckoutEventName($event));

    }

    /**
     * SW6 ready
     *
     * @param OrderEntity $order
     * @param SalesChannelContext $context
     * @return array
     * @throws \Exception
     */
    public function getPurchaseConfirmationTags(OrderEntity $order, SalesChannelContext $context): array
    {

        $pluginConfig = $this->getGtmConfig($context->getSalesChannel()->getId());
        $addCategoryNames = (isset($pluginConfig['eeAddCategorynameInCheckout'])) ? $pluginConfig['eeAddCategorynameInCheckout'] : false;
        $useNetPrices = isset($pluginConfig['showPriceType']) && $pluginConfig['showPriceType'] == 'netto';

        //added in 6.1.24
        $shipping = $order->getShippingCosts();
        if($shipping instanceof CalculatedPrice) $shipping_tax_rate = $shipping->getTaxRules()->first();
        else $shipping_tax_rate = null;

        if($shipping_tax_rate) {
            $shippingTax = $shipping_tax_rate->getTaxRate();
        }
        else {
            //Bugfix for tax free countries, V6.1.4
            $shippingTax = 0;
        }

        //Finish fields
        $ga4_tags = [];

        $lineItems = $order->getLineItems()->filterByType(PromotionProcessor::LINE_ITEM_TYPE);
        if ($lineItems->count() >= 1) {
            $promoCode = $this->getPromotionCode($order->getLineItems());
            if($promoCode != '') {
                $ga4_tags['coupon'] = $promoCode;
            }
        }

        //added in 6.1.24
        $revenue = ($useNetPrices) ? $order->getAmountNet() : $order->getAmountTotal();

        $ga4_tags['currency'] = $context->getCurrency()->getIsoCode();
        $ga4_tags['transaction_id'] = $order->getOrderNumber();
        $ga4_tags['value'] = $revenue;
        $ga4_tags['tax'] = $this->priceHelper->formatPrice($order->getAmountTotal() - $order->getAmountNet());
        $ga4_tags['shipping'] = (float) $this->priceHelper->getPrice($order->getShippingTotal(), $shippingTax, $context);
        $ga4_tags['items'] = $this->getBasketItems($order->getLineItems(), $context, $addCategoryNames, 'purchase');

        //new customer & lifetime value
        $customer = $order->getOrderCustomer();
        if($customer) {
            $customerData = $customer->getCustomer();
            $customerOrderStatistics = $this->customerHelper->getCustomerOrderStatisticsByCustomerId($customer->getCustomerId(), $context);
            $isGuest = !($customerData != null) || $customerData->getGuest();
            if(!$isGuest) {
                /** new_customer should only be specified if there is certainty about the customer, not if it is a guest account */
                $ga4_tags['new_customer'] = !(($customerOrderStatistics['orderCount'] > 1));
            }
            $ga4_tags['customer_lifetime_value'] = $customerOrderStatistics['orderSum'];
            //$ga4_tags['customer_order_count'] = $customerOrderStatistics['orderCount'];
        }

        //added in 6.2.3
        $adwords_tracking_enabled = isset($pluginConfig['googleAdwordsId']) && $pluginConfig['googleAdwordsId'] != '';
        if($adwords_tracking_enabled) {
            $ga4_tags['aw_merchant_id'] = $this->getAdwordsId($context->getSalesChannel()->getId());
            $localeCode = $this->generalTagsService->getLocaleCode($context->getContext());
            $ga4_tags['aw_feed_country'] = $this->generalTagsService->getCldrCountryCode($localeCode);
            $ga4_tags['aw_feed_language'] = $this->generalTagsService->getCldrLanguageCode($localeCode);
        }

        return $this->addEeEvent($ga4_tags, 'purchase', 'client');

    }

    private function getGenericPaymentOrShippingInfoData($cart, SalesChannelContext $context): array
    {
        $pluginConfig = $this->getGtmConfig($context->getSalesChannel()->getId());
        $addCategoryNames = (isset($pluginConfig['eeAddCategorynameInCheckout'])) ? $pluginConfig['eeAddCategorynameInCheckout'] : false;
        $useNetPrices = isset($pluginConfig['showPriceType']) && $pluginConfig['showPriceType'] == 'netto';

        $ga4_tags = [];
        $ga4_tags['currency'] = $context->getCurrency()->getIsoCode();
        $ga4_tags['value'] = ($useNetPrices) ? $cart->getPrice()->getNetPrice() : $cart->getPrice()->getTotalPrice();
        $ga4_tags['items'] = $this->getBasketItems($cart->getLineItems(), $context, $addCategoryNames, 'confirm');

        return $ga4_tags;
    }

    /**
     * CDVRS-GH-14
     */
    public function getAddPaymentInfoTags($cart, SalesChannelContext $context): array
    {
        $ga4_tags = $this->getGenericPaymentOrShippingInfoData($cart, $context);
        $ga4_tags['payment_type'] = $context->getPaymentMethod()?->getTechnicalName();
        $ga4_tags['payment_type_translated'] = $context->getPaymentMethod()?->getName();

        return $this->addEeEvent($ga4_tags, 'add_payment_info');
    }

    /**
     * CDVRS-GH-14
     */
    public function getAddShippingInfoTags($cart, SalesChannelContext $context): array
    {
        $ga4_tags = $this->getGenericPaymentOrShippingInfoData($cart, $context);
        $ga4_tags['shipping_tier'] = $context->getShippingMethod()?->getTechnicalName();
        $ga4_tags['shipping_tier_translated'] = $context->getShippingMethod()?->getName();

        return $this->addEeEvent($ga4_tags, 'add_shipping_info');
    }

    public function getSearchTags($searchTerm, ProductListingResult $listing, SalesChannelContext $context): array
    {
        $ga4_tags = [];

        //$ga4_tags['currency'] = $context->getCurrency()->getIsoCode();
        $ga4_tags['search_term'] = $searchTerm;

        return $this->addEeEvent($ga4_tags, 'view_search_results');
    }

    /**
     * @param $listing
     * @param int $maxCategories
     * @param SalesChannelContext $context
     * @param string $listName
     * @param string $category
     * @return array
     * @throws \Exception
     */
    private function getImpressions($listing, int $maxCategories, SalesChannelContext $context, string $listName = 'Search', CategoryEntity $category = null): array
    {

        $tags = array();
        if(empty($listing)) return $tags;

        $i = 0;

        foreach($listing as $product) {
            if(!is_a($product, SalesChannelProductEntity::class)) continue;
            /** @var SalesChannelProductEntity $product */
            $price = ($product->getCalculatedPrices()->count()) ? $product->getCalculatedPrices()->first()->getUnitPrice() : $product->getCalculatedPrice()->getUnitPrice();
            $brutto_price = (is_float($price)) ? $price : str_replace(',', '.', $price);

            $taxRate = $product->getCalculatedPrice()?->getTaxRules()->first();
            if($taxRate) {
                $tax = $taxRate->getTaxRate();
            }
            else {
                //Bugfix for tax free countries, V6.1.4
                $tax = 0;
            }

            $item = array(
                'item_name'      =>  $product->getTranslation('name'),
                'item_id'        =>  $this->resolveItemId($product, $context),
                'price'          =>  (float) $this->priceHelper->getPrice($brutto_price, $tax, $context),
                'item_brand'     =>  ($product->getManufacturer() !== null) ? $product->getManufacturer()->getTranslation('name') : '',
                'index'          =>  $i++,
                'quantity'       =>  1
            );

            //added: optional parent_id / variant_id for variant products
            $item = $this->addParentAndVariantId($item, $product, $context);
            if($listName) $item['item_list_name'] = $listName;
            if($category) $item['item_list_id'] = $category->getId();
            if($category) $item['item_category'] = $category->getTranslation('name');

            if($this->addDatabaseProductId($context->getSalesChannel()->getId())) {
                $item['item_db_id'] = $product->getId();
            }

            //added in 6.3.9 - CDVRS-33 + CDVRS-36
            if($this->getVariantName($product->getVariation())) {
                $item['item_variant'] = $this->getVariantName($product->getVariation());
            }

            //Original price for discounted items
            if($this->showOriginalPrice($context->getSalesChannel()->getId())) {
                $listPrice = $product->getCalculatedPrice()->getListPrice();
                if($listPrice !== null) {
                    $originalPrice = (float) $this->priceHelper->getPrice($listPrice->getPrice(), $tax, $context);
                    $item['item_original_price'] = $originalPrice;
                    $item['discount'] = $this->priceHelper->formatPrice($originalPrice - $item['price']);
                }
            }

            //add Remarketing Data
            if($this->remarketingEnabled($context->getSalesChannel()->getId())) {
                $item['id'] = $product->getProductNumber();
                $item['google_business_vertical'] = 'retail';
            }

            $tags[] = $item;

            //since 6.1.35
            if($maxCategories > 0 && $i >= $maxCategories) break;
        }

        return $tags;

    }

    /**
     * @param $listing
     * @param SalesChannelContext $context
     * @param bool $addCategoryNames
     * @param string $location
     * @return array
     * @throws \Exception
     */
    public function getBasketItems($listing, SalesChannelContext $context, $addCategoryNames = false, $location = 'checkout'): array
    {

        $i = 0;
        $tags = array();
        if(empty($listing)) return $tags;

        $salesChannelId = $context->getSalesChannel()->getId();
        // We need to load the real product entities whenever category names are requested
        // or the item_id / parent_id / variant_id configuration requires product data
        // (EAN, manufacturer number or parent product number) that is not part of the line item payload.
        $needsProductEntities = $addCategoryNames
            || $this->getItemIdSource($salesChannelId) !== 'productNumber'
            || $this->showParentId($salesChannelId)
            || $this->showVariantId($salesChannelId);

        $realProductIds = [];
        if ($needsProductEntities) {
            foreach ($listing as $lineItem) {
                /** @var LineItem $lineItem */
                if ($lineItem->getReferencedId() && Uuid::isValid($lineItem->getReferencedId())) {
                    $realProductIds[] = $lineItem->getReferencedId();
                }
            }
        }

        $realProducts = null;
        if (count($realProductIds)) {
            $realProducts = $this->productHelper->getProductsById($realProductIds, $context);
        }

        foreach($listing as $product) {
            /** @var LineItem $product */
            $taxRate = $product->getPrice()?->getTaxRules()->first();
            if($taxRate) {
                $tax = $taxRate->getTaxRate();
            }
            else {
                //Bugfix for tax free countries, V6.1.4
                $tax = 0;
            }

            $payload = $product->getPayload();

            $productNumber = (isset($payload['productNumber'])) ? $payload['productNumber'] : 'voucher';
            $productName = $product->getLabel();

            //Anpassung für Swag Custom Products - V6.1.29
            if($product->getType() == 'customized-products' && $product->getChildren()) {
                foreach($product->getChildren() as $child) {
                    if($child->getType() == 'product') {
                        $productName = $child->getLabel();
                        $childPayload = $child->getPayload();
                        $productNumber = (isset($childPayload['productNumber'])) ? $childPayload['productNumber'] : 'customized-product';
                    }
                }
            }
            if($product->getType() == 'customized-products-option') {
                $productNumber = 'customized-product-option-' . strtolower($product->getLabel());
            }
            if(($product->getType() == 'option-values' || $product->getType() == 'customized-products') && $location === 'purchase') {
                continue;
            }

            // Resolve the real product entity (if loaded) to apply the configured item_id source
            // and the optional parent_id / variant_id for variants.
            $realProduct = null;
            if($realProducts && $product->getReferencedId() && $realProducts->has($product->getReferencedId())) {
                $realProduct = $realProducts->get($product->getReferencedId());
            }

            $itemId = $productNumber;
            if($realProduct !== null && $product->getType() !== 'promotion') {
                $itemId = $this->resolveItemId($realProduct, $context);
            }

            $item = array(
                'item_name'      =>  $productName,
                'item_id'        =>  $itemId,
                'index'          =>  $i++,
                'price'     =>  (float) $this->priceHelper->getPrice($product->getPrice()->getUnitPrice(), $tax, $context),
                'quantity'  =>  $product->getQuantity(),
            );

            //added: optional parent_id / variant_id for variant products
            if($realProduct !== null && $product->getType() !== 'promotion') {
                $item = $this->addParentAndVariantId($item, $realProduct, $context);
            }

            //added in 6.3.7 - CDVRS-0000022
            if(isset($payload['options']) && $this->getVariantName($payload['options'])) {
                $item['item_variant'] = $this->getVariantName($payload['options']);
            }

            if($this->addDatabaseProductId($context->getSalesChannel()->getId())) {
                $item['item_db_id'] = $product->getId();
            }

            if(isset($payload['manufacturerId'])) {
                //V6.1.20: add manufacturer-name
                $manufacturer = $this->manufacturerHelper->getManufacturerById($payload['manufacturerId'], $context);
                if($manufacturer !== null) {
                    $item['item_brand'] = $manufacturer->getTranslation('name');
                }
            }

            //Product Category - Changed to SEO Category in V6.1.22
            if($addCategoryNames) {
                if($product->getType() != 'promotion' && $product->getReferencedId() && $realProducts && $realProducts->has($product->getReferencedId())) {
                    $seoCategory = $this->productHelper->getSalesChannelSeoCategoryByProduct(
                        $realProducts->get($product->getReferencedId()),
                        $context,
                    );
                    if($seoCategory !== null) {
                        $breadcrumb = $seoCategory->getTranslation('breadcrumb');
                        if (!empty($breadcrumb) && is_array($breadcrumb)) {
                            $item = $this->mapBreadcrumbCategories($breadcrumb, $item);

                            // Optional: provide list id for consistency
                            $item['item_list_id'] = $seoCategory->getId();
                        } else {
                            $item['item_category'] = $seoCategory->getTranslation('name');
                        }
                    }
                }
            }

            //Original price for discounted items
            if($this->showOriginalPrice($context->getSalesChannel()->getId())) {
                $listPrice = $product->getPrice()->getListPrice();
                if($listPrice !== null) {
                    $originalPrice = (float) $this->priceHelper->getPrice($listPrice->getPrice(), $tax, $context);
                    $item['item_original_price'] = $originalPrice;
                    $item['discount'] = $this->priceHelper->formatPrice($originalPrice - $item['price']);
                }
            }

            //add Remarketing Data
            if($this->remarketingEnabled($context->getSalesChannel()->getId())) {
                $item['id'] = $productNumber;
                $item['google_business_vertical'] = 'retail';
            }

            $tags[] = $item;
        }

        return $tags;

    }

    /**
     * @param $event
     * @return int
     */
    private function getCheckoutEventName($event)
    {

        $event_name = 0;

        switch (get_class($event)) {
            case CheckoutConfirmPageLoadedEvent::class:
                $event_name = 'confirm_order';
                break;
            case CheckoutRegisterPageLoadedEvent::class:
                // begin_checkout is no longer fired on the registration page.
                // It is now triggered earlier, on click of the checkout button
                // ("Zur Kasse") in the off canvas cart (handled in JS).
                // custom event name 'view_register_page' added in 6.4.12
                $event_name = 'view_register_page';
                break;
            default:
                $event_name = 'view_cart';
                break;
        }

        return $event_name;

    }

    /**
     * @param $lineItems LineItemCollection
     */
    private function getPromotionCode($lineItems)
    {
        foreach ($lineItems as $lineItem) {

            $payload = $lineItem->getPayload();

            if (isset($payload['discountType'])) {
                return $payload['code'] ?? '';
            }

        }
    }

    /**
     * @return string|null
     */
    private function getVariantName(array $options)
    {
        if (empty($options)) return null;

        $variantName = '';
        foreach ($options as $option) {

            //$variantName .= $option['group'].': '.$option['option'];
            $variantName .= $option['option'].' ';

        }

        return trim($variantName);
    }

    /**
     * @param array $breadcrumb
     * @param array $item_data
     * @return array
     */
    private function mapBreadcrumbCategories(array $breadcrumb, array $item_data): array
    {
        // Remove the first element (entry point category)
        $visibleCategories = array_values(array_slice($breadcrumb, 1));

        // Reverse the order: make the leaf category first
        $visibleCategories = array_reverse($visibleCategories);

        // Assign category levels dynamically
        foreach ($visibleCategories as $index => $categoryName) {
            $key = 'item_category' . ($index === 0 ? '' : (string)($index + 1));
            $item_data[$key] = $categoryName;
        }

        return $item_data;
    }

}
