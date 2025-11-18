<?php declare(strict_types=1);

namespace Dtgs\GoogleTagManager\Core\Content\DtgsGtmCustomService;

use Dtgs\GoogleTagManager\Core\Content\DtgsGtmCustomService\Translation\ServiceTranslationDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TranslatedField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TranslationsAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;

class CustomServiceDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'dtgs_gtm_custom_service';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return CustomServiceEntity::class;
    }

    public function getCollectionClass(): string
    {
        return CustomServiceCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey()),
            (new TranslatedField('name')),
            (new StringField('event_name', 'eventName'))->addFlags(new Required()),
            (new StringField('category', 'category'))->addFlags(new Required()),
            (new BoolField('active', 'active')),

            (new TranslationsAssociationField(ServiceTranslationDefinition::class, 'dtgs_gtm_custom_service_id'))->addFlags(new Required()),

        ]);
    }
}
