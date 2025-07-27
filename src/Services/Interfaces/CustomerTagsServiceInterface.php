<?php

namespace Dtgs\GoogleTagManager\Services\Interfaces;

use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

interface CustomerTagsServiceInterface
{
    /**
     * Gets customer information
     *
     * @param CustomerEntity|null $customer
     * @param SalesChannelContext $context
     * @return array
     */
    public function getCustomerTags(?CustomerEntity $customer, SalesChannelContext $context);
}
