<?php declare(strict_types=1);

namespace Dtgs\GoogleTagManager\Core\Content\DtgsGtmCustomService\Translation;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void                          add(ServiceTranslationEntity $entity)
 * @method void                          set(string $key, ServiceTranslationEntity $entity)
 * @method ServiceTranslationEntity[]    getIterator()
 * @method ServiceTranslationEntity[]    getElements()
 * @method ServiceTranslationEntity|null get(string $key)
 * @method ServiceTranslationEntity|null first()
 * @method ServiceTranslationEntity|null last()
 */
class ServiceTranslationCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return ServiceTranslationEntity::class;
    }
}
