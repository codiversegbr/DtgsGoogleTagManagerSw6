<?php

namespace Dtgs\GoogleTagManager\Services;

use Dtgs\GoogleTagManager\Components\Helper\LoggingHelper;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerGroup\CustomerGroupCollection;
use Shopware\Core\Checkout\Order\OrderCollection;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

class CustomerTagsService
{

    private $loggingHelper;
    /**
     * @var EntityRepository
     */
    private $orderRepository;
    /**
     * @var EntityRepository
     */
    private $customerGroupRepository;

    public function __construct(EntityRepository $orderRepository, EntityRepository $customerGroupRepository, LoggingHelper $loggingHelper)
    {
        $this->orderRepository = $orderRepository;
        $this->customerGroupRepository = $customerGroupRepository;
        $this->loggingHelper = $loggingHelper;
    }

    /**
     * SW6 ready
     *
     * Gets customer information
     *
     * @param CustomerEntity $customer or null
     * @return array
     */
    public function getCustomerTags(?CustomerEntity $customer, Context $context) {

        $tags = array();

        if($customer) {
            $tags['visitorLoginState'] = 'Logged In';

            $customerGroup = $this->getCustomerGroup($customer->getGroupId(), $context);

            $tags['visitorType'] = ($customerGroup) ? $customerGroup->getName() : 'default';
            $tags['visitorId'] = $customer->getCustomerNumber();
            $tags['visitorLifetimeValue'] = $this->getCustomerLifetimeValue($customer->getId(), $context);
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

    /**
     * @param $groupId
     * @param Context $context
     * @return \Shopware\Core\Checkout\Customer\Aggregate\CustomerGroup\CustomerGroupEntity|null
     */
    private function getCustomerGroup($groupId, Context $context) {

        $criteria = new Criteria([$groupId]);
        /** @var CustomerGroupCollection $customerGroupCollection */
        $customerGroupCollection = $this->customerGroupRepository->search($criteria, $context)->getEntities();
        return $customerGroupCollection->get($groupId);

    }

    /**
     * @param $customerId
     * @param Context $context
     * @return float
     */
    private function getCustomerLifetimeValue($customerId, Context $context) {

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('orderCustomer.customerId', $customerId));
        /** @var OrderCollection $orderCollection */
        $orderCollection = $this->orderRepository->search($criteria, $context)->getEntities();

        if(!$orderCollection) return floatval(0);

        $lifetimeValue = 0;
        /** @var OrderEntity $order */
        foreach ($orderCollection as $order) {
            $lifetimeValue += $order->getAmountTotal();
        }

        return floatval($lifetimeValue);

    }

}
