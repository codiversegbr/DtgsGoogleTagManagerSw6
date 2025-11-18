<?php declare(strict_types=1);

namespace Dtgs\GoogleTagManager\Core\Content\DtgsGtmCustomService\Translation;

use Shopware\Core\Framework\DataAbstractionLayer\TranslationEntity;
use Dtgs\GoogleTagManager\Core\Content\DtgsGtmCustomService\CustomServiceEntity;

class ServiceTranslationEntity extends TranslationEntity
{
    protected ?string $name = null;

    protected string $dtgsGtmCustomServiceId;

    /** @var CustomServiceEntity */
    protected $dtgsGtmCustomService;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getDtgsGtmCustomServiceId(): string
    {
        return $this->dtgsGtmCustomServiceId;
    }

    public function setDtgsGtmCustomServiceId(string $dtgsGtmCustomServiceId): void
    {
        $this->dtgsGtmCustomServiceId = $dtgsGtmCustomServiceId;
    }

    public function getDtgsGtmCustomService(): CustomServiceEntity
    {
        return $this->dtgsGtmCustomService;
    }

    public function setDtgsGtmCustomService(CustomServiceEntity $dtgsGtmCustomService): void
    {
        $this->dtgsGtmCustomService = $dtgsGtmCustomService;
    }
}
