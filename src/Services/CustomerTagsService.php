<?php

namespace Dtgs\GoogleTagManager\Services;

use Dtgs\GoogleTagManager\Components\Helper\CustomerHelper;
use Dtgs\GoogleTagManager\Components\Helper\LoggingHelper;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class CustomerTagsService
{

    private $loggingHelper;

    private $customerHelper;

    public function __construct(CustomerHelper $customerHelper, LoggingHelper $loggingHelper)
    {
        $this->customerHelper = $customerHelper;
        $this->loggingHelper = $loggingHelper;
    }

    /**
     * SW6 ready
     *
     * Gets customer information
     *
     * @param CustomerEntity|null $customer or null
     * @param SalesChannelContext $context
     * @return array
     */
    public function getCustomerTags(?CustomerEntity $customer, SalesChannelContext $context) {

        $tags = array();

        if($customer) {
            $tags['visitorLoginState'] = 'Logged In';

            $customerGroup = $this->customerHelper->getCustomerGroup($customer->getGroupId(), $context);
            $customerOrderStatistics = $this->customerHelper->getCustomerOrderStatisticsByCustomerId($customer->getId(), $context);

            $tags['visitorType'] = ($customerGroup) ? $customerGroup->getName() : 'default';
            $tags['visitorId'] = $customer->getCustomerNumber();
            $tags['visitorLifetimeValue'] = $customerOrderStatistics['orderSum'];
            $tags['visitorLifetimeOrderCount'] = $customerOrderStatistics['orderCount'];
            $tags['visitorHasPlacedOrderBefore'] = ($customerOrderStatistics['orderCount'] > 0) ? 'Yes' : 'No';
            $tags['visitorExistingCustomer'] = 'Yes';

        } else {
            $tags['visitorLoginState'] = 'Logged Out';
            $tags['visitorType'] = 'NOT LOGGED IN';
            $tags['visitorLifetimeValue'] = 0;
            $tags['visitorExistingCustomer'] = 'No';
        }

        //Since 2.2.3
        if($this->loggingHelper->loggingType('debug')) $this->loggingHelper->logMsg('Customer-Tags: ' . json_encode($tags));

        return $tags;

    }

}