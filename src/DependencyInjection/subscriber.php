<?php declare(strict_types=1);

use Dtgs\GoogleTagManager\Services\CustomerTagsService;
use Dtgs\GoogleTagManager\Services\DatalayerService;
use Dtgs\GoogleTagManager\Services\Ga4Service;
use Dtgs\GoogleTagManager\Services\GeneralTagsService;
use Dtgs\GoogleTagManager\Services\RemarketingService;
use Dtgs\GoogleTagManager\Subscriber\GeneralSubscriber;
use Dtgs\GoogleTagManager\Subscriber\GtmServicesStorefrontSubscriber;
use Dtgs\GoogleTagManager\Subscriber\HttpCacheKeySubscriber;
use Shopware\Core\System\SystemConfig\SystemConfigService;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator $configurator): void {
    $services = $configurator->services();

    // Subscriber
    $services->set(GeneralSubscriber::class)
        ->args([
            service(SystemConfigService::class),
            service(DatalayerService::class),
            service(Ga4Service::class),
            service(RemarketingService::class),
            service(GeneralTagsService::class),
            service(CustomerTagsService::class),
            service('request_stack'),
        ])
        ->tag('kernel.event_subscriber');

    // Storefront subscriber to expose services to Twig
    $services->set(GtmServicesStorefrontSubscriber::class)
        ->args([
            service('dtgs_gtm_custom_service.repository'),
            service('cache.object'),
        ])
        ->tag('kernel.event_subscriber');

    // Http cache key variation for GTM consent
    $services->set(HttpCacheKeySubscriber::class)
        ->args([
            service(SystemConfigService::class),
        ])
        ->tag('kernel.event_subscriber');
};
