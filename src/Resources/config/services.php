<?php declare(strict_types=1);

use Dtgs\GoogleTagManager\Components\Utils\TwigExtension;
use Dtgs\GoogleTagManager\Core\Content\DtgsGtmCustomService\CustomServiceDefinition;
use Dtgs\GoogleTagManager\Core\Content\DtgsGtmCustomService\Translation\ServiceTranslationDefinition;
use Dtgs\GoogleTagManager\Framework\Cookie\CustomCookieProvider;
use Dtgs\GoogleTagManager\Resources\snippet\de_DE\SnippetFile_de_DE;
use Dtgs\GoogleTagManager\Resources\snippet\en_GB\SnippetFile_en_GB;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Framework\Cookie\CookieProviderInterface;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator $configurator): void {
    $services = $configurator->services();

    // DEFINITIONS
    $services->set(CustomServiceDefinition::class)
        ->tag('shopware.entity.definition', ['entity' => 'dtgs_gtm_custom_service']);

    $services->set(ServiceTranslationDefinition::class)
        ->tag('shopware.entity.definition', ['entity' => 'dtgs_gtm_custom_service_translation']);

    $services->set(CustomCookieProvider::class)
        ->decorate(CookieProviderInterface::class)
        ->args([
            service(CustomCookieProvider::class . '.inner'),
            service(SystemConfigService::class),
            service('request_stack'),
            service('dtgs_gtm_custom_service.repository'),
            service('translator'),
            service('cache.object'),
        ]);

    // Twig Function
    $services->set(TwigExtension::class)
        ->tag('twig.extension');

    // Snippets
    $services->set(SnippetFile_en_GB::class)
        ->public()
        ->tag('shopware.snippet.file', ['priority' => 100]);

    $services->set(SnippetFile_de_DE::class)
        ->public()
        ->tag('shopware.snippet.file', ['priority' => 100]);
};
