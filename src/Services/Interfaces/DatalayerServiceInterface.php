<?php

namespace Dtgs\GoogleTagManager\Services\Interfaces;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Content\Product\SalesChannel\Listing\ProductListingResult;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

interface DatalayerServiceInterface
{
    /**
     * Helper to get plugin-specific config
     *
     * @param string|null $salesChannelId
     * @return array|mixed|null
     */
    public function getGtmConfig($salesChannelId);

    /**
     * @param array $generalTags
     * @param array $navigationTags
     * @param array $accountTags
     * @param array $detailTags
     * @param array $checkoutTags
     * @param array $customerTags
     * @param array $utmTags
     * @param array $searchTags
     * @return false|string
     */
    public function prepareTagsForView(
        array $generalTags,
        array $navigationTags,
        array $accountTags,
        array $detailTags,
        array $checkoutTags,
        array $customerTags,
        array $utmTags,
        array $searchTags
    );

    /**
     * Get multiple Tag Manager Container IDs
     *
     * @param string|null $salesChannelId
     * @return array|bool
     */
    public function getContainerIds($salesChannelId);

    /**
     * @param SalesChannelProductEntity $product
     * @param SalesChannelContext $context
     * @return array
     */
    public function getDetailTags(SalesChannelProductEntity $product, SalesChannelContext $context);

    /**
     * @param string|null $navigationId
     * @param SalesChannelContext $context
     * @return array
     */
    public function getNavigationTags($navigationId, SalesChannelContext $context): array;

    /**
     * @param string $searchTerm
     * @param ProductListingResult $listing
     * @return array
     */
	public function getSearchTags($searchTerm, ProductListingResult $listing);

    /**
     * @return array
     */
    public function getAccountTags();

    /**
     * @param OrderEntity $order
     * @param SalesChannelContext $context
     * @return array
     */
	public function getFinishTags(OrderEntity $order, SalesChannelContext $context);

    /**
     * @param Cart|OrderEntity $cartOrOrder (either Cart or Order)
     * @param SalesChannelContext $context
     * @param bool $isFinish
     * @return array
     */
	public function getCheckoutTags($cartOrOrder, SalesChannelContext $context, $isFinish = false);
}
