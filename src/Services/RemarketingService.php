<?php declare(strict_types=1);

namespace Dtgs\GoogleTagManager\Services;

use Dtgs\GoogleTagManager\Components\Helper\CategoryHelper;
use Dtgs\GoogleTagManager\Components\Helper\LoggingHelper;
use Dtgs\GoogleTagManager\Components\Helper\PriceHelper;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\HttpFoundation\Request;

/**
 * @deprecated
 */
class RemarketingService
{
    private $systemConfigService;
    private $priceHelper;
    private $loggingHelper;
    /**
     * @var CategoryHelper
     */
    private $categoryHelper;

    public function __construct(SystemConfigService $systemConfigService,
                                CategoryHelper $categoryHelper,
                                PriceHelper $priceHelper,
                                LoggingHelper $loggingHelper)
    {
        $this->systemConfigService = $systemConfigService;
        $this->categoryHelper = $categoryHelper;
        $this->priceHelper = $priceHelper;
        $this->loggingHelper = $loggingHelper;
    }

    /**
     * @param $remarketingTags
     * @return false|string
     */
    public function prepareTagsForView($remarketingTags)
    {
        return json_encode($remarketingTags);
    }

    /**
     * SW6 ready
     *
     * @param Request $request
     * @return array
     */
    public function getBasicTags(Request $request): array
    {
        //no explicit navigation Tags so far
        $remarketing_tags = [];
        $remarketing_tags['ecomm_pagetype'] = 'other';
        if($this->getActionNameFromRequest($request) == 'home') $remarketing_tags['ecomm_pagetype'] = 'home';

        if($this->loggingHelper->loggingType('debug')) $this->loggingHelper->logMsg('Navigation-Tags: ' . json_encode($remarketing_tags));

        return $remarketing_tags;
    }

    /**
     * SW6 ready
     *
     * @param SalesChannelProductEntity $product
     * @param SalesChannelContext $context
     * @return array
     * @throws \Exception
     */
    public function getDetailTags(SalesChannelProductEntity $product, SalesChannelContext $context) {

        $remarketing_tags = array();

        //New in 1.3.5 - select if brutto/netto
        $price = ($product->getCalculatedPrices()->count()) ? $product->getCalculatedPrices()->first()->getUnitPrice() : $product->getCalculatedPrice()->getUnitPrice();
        $brutto_price = (is_float($price)) ? $price : str_replace(',', '.', $price);

        $taxRate = $product->getCalculatedPrice()->getTaxRules()->first();
        if($taxRate) {
            $tax = $taxRate->getTaxRate();
        }
        else {
            //Bugfix for tax free countries, V6.1.4
            $tax = 0;
        }

        $remarketing_tags['ecomm_pagetype'] = 'product';

        //Product Category - Changed to SEO Category in V6.1.22
        $seoCategory = $product->getSeoCategory();
        if($seoCategory) {
            $remarketing_tags['ecomm_pcat'] = [$seoCategory->getTranslation('name')];
        }

        $remarketing_tags['ecomm_prodid'] = $product->getProductNumber();
        $remarketing_tags['ecomm_pname'] = $product->getTranslation('name');
        $remarketing_tags['ecomm_pvalue'] =  (float) $this->priceHelper->getPrice($brutto_price, $tax, $context);
        $remarketing_tags['ecomm_totalvalue'] = (float) $this->priceHelper->getPrice($brutto_price, $tax, $context);

        return $remarketing_tags;

    }

    /**
     * SW6 ready
     *
     * @param $navigationId
     * @param $listing
     * @param SalesChannelContext $context
     * @param Request $request
     * @return array
     */
    public function getNavigationTags($navigationId, $listing, SalesChannelContext $context, Request $request): array
    {

        $remarketing_tags = [];

        $remarketing_tags['ecomm_pagetype'] = 'category';
        if($this->getActionNameFromRequest($request) == 'home') $remarketing_tags['ecomm_pagetype'] = 'home';

        $category = $this->categoryHelper->getCategoryById($navigationId, $context);
        $remarketing_tags['ecomm_pcat'] = array($category->getTranslation('name'));

        $remarketing_tags['ecomm_prodid'] = array();
        foreach($listing as $prod) {
            if(!is_a($prod, SalesChannelProductEntity::class)) continue;
            /** @var SalesChannelProductEntity $prod */
            $productNumber = $prod->getProductNumber();
            $remarketing_tags['ecomm_prodid'][] = (!empty($productNumber)) ? $productNumber : $prod->getId();
        }
        return $remarketing_tags;

    }

    /**
     * SW6 ready
     *
     * @param Cart|OrderEntity $cartOrOrder
     * @param SalesChannelContext $context
     * @return array
     * @throws \Exception
     */
    public function getCheckoutTags($cartOrOrder, SalesChannelContext $context): array
    {

        $remarketing_tags = array();
        $categoriesAsArray = array();
        $skuAsArray = array();
        $namesAsArray = array();
        $valuesAsArray = array();

        $taxRate = $cartOrOrder->getPrice()->getTaxRules()->first();
        if($taxRate) {
            $tax = $taxRate->getTaxRate();
        }
        else {
            //Bugfix for tax free countries, V6.1.4
            $tax = 0;
        }

        foreach($cartOrOrder->getLineItems() as $item) {
            //Anpassung für Swag Custom Products - V6.1.29
            if($item->getType() == 'customized-products' && $item->getChildren()) {
                foreach ($item->getChildren() as $child) {
                    if ($child->getType() == 'product') {
                        $skuAsArray[] = $child->getPayload()['productNumber'];
                        $namesAsArray[] = $child->getLabel();
                        $valuesAsArray[] = (float)$this->priceHelper->getPrice($item->getPrice()->getUnitPrice(), $tax, $context);
                    }
                }
            }
            elseif($item->getType() == 'customized-products-option') {
                $skuAsArray[] = 'customized-product-option-' . strtolower($item->getLabel());
                $namesAsArray[] = $item->getLabel();
                $valuesAsArray[] = (float) $this->priceHelper->getPrice($item->getPrice()->getUnitPrice(), $tax, $context);
            }
            elseif($item->getType() == 'option-values' || $item->getType() == 'customized-products') {
                continue;
            }
            else {
                /** @var LineItem $item */
                if(isset($item->getPayload()['productNumber'])) {
                    $skuAsArray[] = $item->getPayload()['productNumber'];
                    $namesAsArray[] = $item->getLabel();
                    $valuesAsArray[] = (float) $this->priceHelper->getPrice($item->getPrice()->getUnitPrice(), $tax, $context);
                }
            }
        }

        $remarketing_tags['ecomm_pagetype'] = 'cart';
        $remarketing_tags['ecomm_pcat'] = $categoriesAsArray;
        $remarketing_tags['ecomm_prodid'] = $skuAsArray;
        $remarketing_tags['ecomm_pname'] = $namesAsArray;
        $remarketing_tags['ecomm_pvalue'] = $valuesAsArray;
        //total cart value
        if($this->priceHelper->getPriceType($context) == 'netto') $remarketing_tags['ecomm_totalvalue'] = (float) $cartOrOrder->getPrice()->getNetPrice();
        else $remarketing_tags['ecomm_totalvalue'] = (float) $cartOrOrder->getPrice()->getTotalPrice();

        return $remarketing_tags;

    }

    /**
     * SW6 ready
     *
     * @param OrderEntity $order
     * @return array
     * @throws \Exception
     */
    public function getPurchaseConfirmationTags(OrderEntity $order, SalesChannelContext $context) {

        $remarketing_tags = $this->getCheckoutTags($order, $context);
        //Hier muss nur ein Wert überschrieben werden
        $remarketing_tags['ecomm_pagetype'] = 'purchase';

        return $remarketing_tags;

    }

    /**
     * SW6 ready
     *
     * @param Request $request
     * @return array
     */
    public function getSearchTags(Request $request): array
    {

        $remarketing_tags = $this->getBasicTags($request);
        //Hier muss nur ein Wert überschrieben werden
        $remarketing_tags['ecomm_pagetype'] = 'searchresults';

        return $remarketing_tags;

    }

    /**
     * @param Request $request
     * @return false|string
     */
    private function getActionNameFromRequest(Request $request) {
        $params = explode('::',$request->attributes->get('_controller') ?? '::');
        // $params[1] = 'home';
        return $params[1];
    }

}
