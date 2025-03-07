<?php
/**
 * ProductHelper Class
 */
namespace Dtgs\GoogleTagManager\Components\Helper;

use Shopware\Core\Checkout\Customer\Aggregate\CustomerGroup\CustomerGroupCollection;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerGroup\CustomerGroupEntity;
use Shopware\Core\Checkout\Customer\CustomerCollection;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Order\OrderCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class CustomerHelper
{

    /**
     * @var EntityRepository
     */
    private EntityRepository $customerRepository;

    /**
     * @var EntityRepository
     */
    private EntityRepository $customerGroupRepository;

    /**
     * @var EntityRepository
     */
    private EntityRepository $orderRepository;

    public function __construct(EntityRepository $customerRepository,
                                EntityRepository $customerGroupRepository,
                                EntityRepository $orderRepository)
    {
        $this->customerRepository = $customerRepository;
        $this->customerGroupRepository = $customerGroupRepository;
        $this->orderRepository = $orderRepository;
    }

    /**
     * @param $customerId
     * @param SalesChannelContext $context
     * @return CustomerEntity|null
     */
    public function getCustomerById($customerId, $context)
    {
        $criteria = new Criteria([$customerId]);
        /** @var CustomerCollection $customerCollection */
        $customerCollection = $this->customerRepository->search($criteria, $context->getContext())->getEntities();
        return $customerCollection->get($customerId);
    }

    /**
     * @param $customerId
     * @param $context
     * @return array
     */
    public function getCustomerOrderStatisticsByCustomerId($customerId, $context): array
    {
        $criteria = new Criteria();
        $criteria->addAssociation('orderCustomer');
        $criteria->addFilter(new EqualsFilter('orderCustomer.customerId', $customerId));
        /** @var OrderCollection $orderCollection */
        $orderCollection = $this->orderRepository->search($criteria, $context->getContext())->getEntities();

        $sum = 0;
        foreach ($orderCollection as $order) {
            $sum += $order->getAmountTotal();
        }

        return ['orderCount' => $orderCollection->count(), 'orderSum' => (float)number_format($sum, 2, '.', '')];
    }

    /**
     * @param $groupId
     * @param Context $context
     * @return CustomerGroupEntity|null
     */
    public function getCustomerGroup($groupId, SalesChannelContext $context) {

        $criteria = new Criteria([$groupId]);
        /** @var CustomerGroupCollection $customerGroupCollection */
        $customerGroupCollection = $this->customerGroupRepository->search($criteria, $context->getContext())->getEntities();
        return $customerGroupCollection->get($groupId);

    }

}
