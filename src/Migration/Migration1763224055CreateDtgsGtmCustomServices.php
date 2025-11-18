<?php declare(strict_types=1);

namespace Dtgs\GoogleTagManager\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * @internal
 */
class Migration1763224055CreateDtgsGtmCustomServices extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1763224055;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
            CREATE TABLE IF NOT EXISTS `dtgs_gtm_custom_service` (
                `id` BINARY(16) NOT NULL,
                `event_name` VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL,
                `category` VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL,
                `active` TINYINT(1),
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3),
                PRIMARY KEY (`id`)
            )
                ENGINE = InnoDB
                DEFAULT CHARSET = utf8mb4
                COLLATE = utf8mb4_unicode_ci;
SQL;

        $sqlTranslation = <<<SQL_TR
            CREATE TABLE IF NOT EXISTS `dtgs_gtm_custom_service_translation` (
                `dtgs_gtm_custom_service_id` BINARY(16) NOT NULL,
                `language_id` BINARY(16) NOT NULL,
                `name` VARCHAR(255) COLLATE utf8mb4_unicode_ci NULL,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
                PRIMARY KEY (`dtgs_gtm_custom_service_id`,`language_id`),
                KEY `fk.dtgs_gtm_custom_service_tr.dtgs_gtm_custom_service_id` (`dtgs_gtm_custom_service_id`),
                KEY `fk.dtgs_gtm_custom_service_tr.language_id` (`language_id`),
                CONSTRAINT `fk.dtgs_gtm_custom_service_tr.dtgs_gtm_custom_service_id` 
                    FOREIGN KEY (`dtgs_gtm_custom_service_id`) 
                    REFERENCES `dtgs_gtm_custom_service` (`id`) 
                    ON DELETE CASCADE
                    ON UPDATE CASCADE,
                CONSTRAINT `fk.dtgs_gtm_custom_service_tr.language_id` FOREIGN KEY (`language_id`) REFERENCES `language` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            )
            ENGINE = InnoDB
            DEFAULT CHARSET = utf8mb4
            COLLATE = utf8mb4_unicode_ci;
SQL_TR;

        $connection->executeStatement($sql);
        $connection->executeStatement($sqlTranslation);
    }
}
