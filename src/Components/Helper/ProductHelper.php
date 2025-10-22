<?php
/**
 * ProductHelper Class
 */
namespace Dtgs\GoogleTagManager\Components\Helper;

use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Content\Category\Service\CategoryBreadcrumbBuilder;
use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class ProductHelper
{
    public function __construct(
        private EntityRepository $productRepository,
        private CategoryBreadcrumbBuilder $breadcrumbBuilder,
    )
    {
    }

    /**
     * @param $productId
     * @param SalesChannelContext $context
     * @return ProductEntity|null
     */
    public function getProductyById($productId, $context)
    {
        $criteria = new Criteria([$productId]);
        $criteria->addAssociation('seoUrls');
        /** @var ProductCollection $productCollection */
        $productCollection = $this->productRepository->search($criteria, $context->getContext())->getEntities();
        return $productCollection->get($productId);
    }

    /**
     * @param $productId
     * @param $context
     */
    public function getSalesChannelSeoCategoryByProductId($productId, $context): ?CategoryEntity
    {
        $criteria = new Criteria();
        $criteria->setIds([$productId]);
        $criteria->setTitle('product-detail-route');

        $product = $this->productRepository->search($criteria, $context)->getEntities()->first();

        if ($product === null) {
            return null;
        }

        return $this->breadcrumbBuilder->getProductSeoCategory($product, $context);
    }

}
