<?php declare(strict_types=1);

use Dtgs\GoogleTagManager\Components\Helper\CategoryHelper;
use Dtgs\GoogleTagManager\Components\Helper\CustomerHelper;
use Dtgs\GoogleTagManager\Components\Helper\LoggingHelper;
use Dtgs\GoogleTagManager\Components\Helper\ManufacturerHelper;
use Dtgs\GoogleTagManager\Components\Helper\PriceHelper;
use Dtgs\GoogleTagManager\Components\Helper\ProductHelper;
use Shopware\Core\Content\Category\Service\CategoryBreadcrumbBuilder;
use Shopware\Core\System\SystemConfig\SystemConfigService;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator $configurator): void {
    $services = $configurator->services();

    // Helper
    $services->set(PriceHelper::class)
        ->args([
            service(SystemConfigService::class),
            service('customer_group.repository'),
        ]);

    $services->set(LoggingHelper::class)
        ->args([
            service(SystemConfigService::class),
            service('monolog.logger'),
        ]);

    $services->set(CategoryHelper::class)
        ->args([
            service('category.repository'),
        ]);

    $services->set(ProductHelper::class)
        ->args([
            service('product.repository'),
            service(CategoryBreadcrumbBuilder::class),
        ]);

    $services->set(ManufacturerHelper::class)
        ->args([
            service('product_manufacturer.repository'),
        ]);

    $services->set(CustomerHelper::class)
        ->args([
            service('customer.repository'),
            service('customer_group.repository'),
            service('order.repository'),
        ]);
};
