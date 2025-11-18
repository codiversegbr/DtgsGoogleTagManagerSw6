<?php
declare(strict_types=1);

namespace Dtgs\GoogleTagManager\Core\Content\DtgsGtmCustomService;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void add(CustomServiceEntity $entity)
 * @method void set(string $key, CustomServiceEntity $entity)
 * @method CustomServiceEntity[] getIterator()
 * @method CustomServiceEntity[] getElements()
 * @method CustomServiceEntity|null get(string $key)
 * @method CustomServiceEntity|null first()
 * @method CustomServiceEntity|null last()
 */
class CustomServiceCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return CustomServiceEntity::class;
    }
}
