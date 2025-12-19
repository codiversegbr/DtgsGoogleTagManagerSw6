<?php declare(strict_types=1);

namespace Dtgs\GoogleTagManager\Core\Content\DtgsGtmCustomService\Translation;

use Dtgs\GoogleTagManager\Core\Content\DtgsGtmCustomService\CustomServiceDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityTranslationDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class ServiceTranslationDefinition extends EntityTranslationDefinition
{
    public const ENTITY_NAME = 'dtgs_gtm_custom_service_translation';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return ServiceTranslationEntity::class;
    }

    public function getCollectionClass(): string
    {
        return ServiceTranslationCollection::class;
    }

    protected function getParentDefinitionClass(): string
    {
        return CustomServiceDefinition::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new StringField('name', 'name'))->addFlags(new Required()),
        ]);
    }
}
