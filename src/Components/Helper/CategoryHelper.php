<?php
/**
 * Created by PhpStorm.
 * User: constantin
 * Date: 28.02.17
 * Time: 15:35
 */
namespace Dtgs\GoogleTagManager\Components\Helper;

use Shopware\Core\Content\Category\CategoryCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class CategoryHelper
{

    public function __construct(EntityRepository $categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * @var EntityRepository
     */
    private EntityRepository $categoryRepository;

    /**
     * @param $categoryId
     * @param SalesChannelContext $context
     * @return \Shopware\Core\Content\Category\CategoryEntity|null
     */
    public function getCategoryById($categoryId, $context)
    {
        $criteria = new Criteria([$categoryId]);
        /** @var CategoryCollection $categoryCollection */
        $categoryCollection = $this->categoryRepository->search($criteria, $context->getContext())->getEntities();
        return $categoryCollection->get($categoryId);
    }

}
