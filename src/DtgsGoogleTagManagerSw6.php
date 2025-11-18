<?php declare(strict_types=1);

namespace Dtgs\GoogleTagManager;

use Doctrine\DBAL\Connection;
use Dtgs\GoogleTagManager\Core\Content\DtgsGtmCustomService\CustomServiceDefinition;
use Dtgs\GoogleTagManager\Core\Content\DtgsGtmCustomService\Translation\ServiceTranslationDefinition;
use Shopware\Core\Framework\Plugin;

use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

class DtgsGoogleTagManagerSw6 extends Plugin
{
    public const CUSTOM_TABLES = [
        ServiceTranslationDefinition::ENTITY_NAME,
        CustomServiceDefinition::ENTITY_NAME
    ];

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

        $this->deleteTables();
    }

    private function deleteTables(): void
    {
        $connection = $this->container->get(Connection::class);
        foreach (self::CUSTOM_TABLES as $table) {
            try {
                $connection->executeStatement('DROP TABLE IF EXISTS `' . $table. '`;');
            } catch (\Exception $exception) {
            }
        }
    }
}
