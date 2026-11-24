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
    /**
     * @var array<string, string|null>
     */
    private array $productNumberCache = [];

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
     * Returns the product number (SKU) of a product by its id.
     * Used to resolve the parent product number for variants.
     *
     * @param string $productId
     * @param SalesChannelContext $context
     * @return string|null
     */
    public function getProductNumberById($productId, $context): ?string
    {
        if(empty($productId)) {
            return null;
        }

        if(isset($this->productNumberCache[$productId])) {
            return $this->productNumberCache[$productId];
        }

        $criteria = new Criteria([$productId]);
        /** @var ProductCollection $productCollection */
        $productCollection = $this->productRepository->search($criteria, $context->getContext())->getEntities();
        $product = $productCollection->get($productId);

        $productNumber = ($product !== null) ? $product->getProductNumber() : null;
        $this->productNumberCache[$productId] = $productNumber;

        return $productNumber;
    }

    public function getProductsById($productIds, $context): ProductCollection
    {
        $criteria = new Criteria($productIds);
        $criteria->addAssociation('seoUrls');

        /** @var ProductCollection $productCollection */
        return $this->productRepository->search($criteria, $context->getContext())->getEntities();
    }

    /**
     * @param ProductEntity $product
     * @param SalesChannelContext $context
     */
    public function getSalesChannelSeoCategoryByProduct($product, $context): ?CategoryEntity
    {
        return $this->breadcrumbBuilder->getProductSeoCategory($product, $context);
    }

}
