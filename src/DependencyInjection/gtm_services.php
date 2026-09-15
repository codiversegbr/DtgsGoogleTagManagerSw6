<?php declare(strict_types=1);

use Dtgs\GoogleTagManager\Components\Helper\CategoryHelper;
use Dtgs\GoogleTagManager\Components\Helper\CustomerHelper;
use Dtgs\GoogleTagManager\Components\Helper\LoggingHelper;
use Dtgs\GoogleTagManager\Components\Helper\ManufacturerHelper;
use Dtgs\GoogleTagManager\Components\Helper\PriceHelper;
use Dtgs\GoogleTagManager\Components\Helper\ProductHelper;
use Dtgs\GoogleTagManager\Services\CustomerTagsService;
use Dtgs\GoogleTagManager\Services\DatalayerService;
use Dtgs\GoogleTagManager\Services\Ga4Service;
use Dtgs\GoogleTagManager\Services\GeneralTagsService;
use Dtgs\GoogleTagManager\Services\RemarketingService;
use Shopware\Core\System\SystemConfig\SystemConfigService;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator $configurator): void {
    $services = $configurator->services();

    // Services
    $services->set(DatalayerService::class)
        ->args([
            service(SystemConfigService::class),
            service(CategoryHelper::class),
            service(PriceHelper::class),
            service(ProductHelper::class),
            service(LoggingHelper::class),
            service('state_machine_state.repository'),
        ]);

    $services->set(Ga4Service::class)
        ->args([
            service(SystemConfigService::class),
            service(GeneralTagsService::class),
            service('service_container'),
            service(ProductHelper::class),
            service(CategoryHelper::class),
            service(ManufacturerHelper::class),
            service(CustomerHelper::class),
            service(PriceHelper::class),
            service(LoggingHelper::class),
        ]);

    $services->set(RemarketingService::class)
        ->args([
            service(SystemConfigService::class),
            service(CategoryHelper::class),
            service(PriceHelper::class),
            service(LoggingHelper::class),
        ]);

    $services->set(GeneralTagsService::class)
        ->args([
            service(SystemConfigService::class),
            service('language.repository'),
        ]);

    $services->set(CustomerTagsService::class)
        ->args([
            service(CustomerHelper::class),
            service(LoggingHelper::class),
        ]);
};
