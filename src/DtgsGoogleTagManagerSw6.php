<?php declare(strict_types=1);

namespace Dtgs\GoogleTagManager;

use Shopware\Core\Framework\Plugin;

use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

class DtgsGoogleTagManagerSw6 extends Plugin
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/DependencyInjection/'));
        $loader->load('helper.xml');
        $loader->load('gtm_services.xml');
        $loader->load('subscriber.xml');
    }

    /**
     * @param InstallContext $context
     */
    public function install(InstallContext $context): void
    {
        parent::install($context);
    }

    /**
     * @param UninstallContext $context
     */
    public function uninstall(UninstallContext $context): void
    {
        parent::uninstall($context);

        if ($context->keepUserData()) {
            return;
        }
    }
}
