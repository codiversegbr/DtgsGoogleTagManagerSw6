<?php

namespace Dtgs\GoogleTagManager\Services\Interfaces;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

interface Ga4ServiceInterface
{
    /**
     * Helper to get plugin-specific config
     *
     * @param string|null $salesChannelId
     * @return array|mixed|null
     */
    public function getGtmConfig($salesChannelId);

    /**
     * Get Google Ads ID
     *
     * @param string|null $salesChannelId
     * @return string|false
     */
    public function getAdwordsId($salesChannelId);

    /**
     * Check if remarketing is enabled
     *
     * @param string|null $salesChannelId
     * @return bool
     */
    public function remarketingEnabled($salesChannelId);

    /**
     * Check if database product ID should be added
     *
     * @param string|null $salesChannelId
     * @return bool
     */
    public function addDatabaseProductId($salesChannelId);

    /**
     * Prepare tags for view
     *
     * @param array $ga4Tags
     * @return false|string
     */
    public function prepareTagsForView($ga4Tags);

    /**
     * Get detail tags for product view
     *
     * @param SalesChannelProductEntity $product
     * @param SalesChannelContext $context
     * @return array
     */
    public function getDetailTags(SalesChannelProductEntity $product, SalesChannelContext $context);

    /**
     * Get navigation tags for the category / listing view
     *
     * @param string|null $navigationId
     * @param mixed $listing
     * @param SalesChannelContext $context
     * @param string $listName
     * @return array
     */
    public function getNavigationTags($navigationId, $listing, SalesChannelContext $context, string $listName = 'Category');

    /**
     * Get checkout tags
     *
     * @param Cart|OrderEntity $cartOrOrder
     * @param mixed $event
     * @return array
     */
    public function getCheckoutTags($cartOrOrder, $event);

    /**
     * Get purchase confirmation tags
     *
     * @param OrderEntity $order
     * @param SalesChannelContext $context
     * @return array
     */
    public function getPurchaseConfirmationTags(OrderEntity $order, SalesChannelContext $context): array;

    /**
     * Get add payment info tags
     *
     * @param Cart $cart
     * @param SalesChannelContext $context
     * @return array
     */
    public function getAddPaymentInfoTags($cart, SalesChannelContext $context): array;

    /**
     * Get to add shipping info tags
     *
     * @param Cart $cart
     * @param SalesChannelContext $context
     * @return array
     */
    public function getAddShippingInfoTags($cart, SalesChannelContext $context): array;
}
