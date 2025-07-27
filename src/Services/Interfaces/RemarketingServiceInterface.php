<?php

namespace Dtgs\GoogleTagManager\Services\Interfaces;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;

interface RemarketingServiceInterface
{
    /**
     * @param $remarketingTags
     * @return false|string
     */
    public function prepareTagsForView($remarketingTags);

    /**
     * @param Request $request
     * @return array
     */
    public function getBasicTags(Request $request): array;

    /**
     * @param SalesChannelProductEntity $product
     * @param SalesChannelContext $context
     * @return array
     * @throws \Exception
     */
    public function getDetailTags(SalesChannelProductEntity $product, SalesChannelContext $context);

    /**
     * @param $navigationId
     * @param $listing
     * @param SalesChannelContext $context
     * @param Request $request
     * @return array
     */
    public function getNavigationTags($navigationId, $listing, SalesChannelContext $context, Request $request): array;

    /**
     * @param Cart|OrderEntity $cartOrOrder
     * @param SalesChannelContext $context
     * @return array
     * @throws \Exception
     */
    public function getCheckoutTags($cartOrOrder, SalesChannelContext $context): array;

    /**
     * @param OrderEntity $order
     * @param SalesChannelContext $context
     * @return array
     * @throws \Exception
     */
    public function getPurchaseConfirmationTags(OrderEntity $order, SalesChannelContext $context);

    /**
     * @param Request $request
     * @return array
     */
    public function getSearchTags(Request $request): array;
}
