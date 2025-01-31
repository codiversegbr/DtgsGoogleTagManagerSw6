<?php
/**
 * Created by PhpStorm.
 * User: constantin
 * Date: 28.02.17
 * Time: 15:35
 */
namespace Dtgs\GoogleTagManager\Components\Helper;

use Shopware\Core\Content\Product\Exception\ProductNotFoundException;
use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Content\Product\SalesChannel\Detail\AbstractProductDetailRoute;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;

class ProductHelper
{

    /**
     * @var EntityRepository
     */
    private EntityRepository $productRepository;

    /**
     * @var AbstractProductDetailRoute
     */
    private AbstractProductDetailRoute $productDetailRoute;

    public function __construct(EntityRepository $productRepository,
                                AbstractProductDetailRoute $productDetailRoute)
    {
        $this->productRepository = $productRepository;
        $this->productDetailRoute = $productDetailRoute;
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
     * @return SalesChannelProductEntity|null
     */
    public function getSalesChannelProductEntityByProductId($productId, $context)
    {
        try {
            $result = $this->productDetailRoute->load($productId, new Request(), $context, new Criteria());
        }
        catch (ProductNotFoundException $exception) {
            return null;
        }
        catch (\Exception $exception) {
            return null;
        }
        return $result->getProduct();
    }

}
