<?php
/**
 * ProductHelper Class
 */
namespace Dtgs\GoogleTagManager\Components\Helper;

use Shopware\Core\Checkout\Customer\Aggregate\CustomerGroup\CustomerGroupCollection;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerGroup\CustomerGroupEntity;
use Shopware\Core\Checkout\Customer\CustomerCollection;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\CountAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\SumAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\Metric\CountResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\Metric\SumResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Contracts\Service\ResetInterface;

class CustomerHelper implements ResetInterface
{

    /**
     * Order statistics per customer id. The page subscriber asks for them on GenericPageLoadedEvent and again on the
     * page-specific event of the same request.
     *
     * @var array<string, array{orderCount: int, orderSum: float}>
     */
    private array $orderStatistics = [];

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
        if (isset($this->orderStatistics[$customerId])) {
            return $this->orderStatistics[$customerId];
        }

        // Count and sum in the database instead of loading every order of the customer as an entity on each page load.
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('orderCustomer.customerId', $customerId));
        $criteria->addAggregation(new CountAggregation('orderCount', 'id'));
        $criteria->addAggregation(new SumAggregation('orderSum', 'amountTotal'));
        $aggregations = $this->orderRepository->aggregate($criteria, $context->getContext());

        $count = $aggregations->get('orderCount');
        $sum = $aggregations->get('orderSum');

        return $this->orderStatistics[$customerId] = [
            'orderCount' => $count instanceof CountResult ? $count->getCount() : 0,
            'orderSum' => (float)number_format($sum instanceof SumResult ? $sum->getSum() : 0, 2, '.', ''),
        ];
    }

    public function reset(): void
    {
        $this->orderStatistics = [];
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
