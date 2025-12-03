<?php declare(strict_types=1);

namespace Dtgs\GoogleTagManager\Core\Content\DtgsGtmCustomService;

use Dtgs\GoogleTagManager\Core\Content\DtgsGtmCustomService\Translation\ServiceTranslationCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class CustomServiceEntity extends Entity
{
    use EntityIdTrait;

    protected ?string $name = null;

    protected ?string $eventName;

    protected ?string $category;

    protected bool $active;

    protected ?ServiceTranslationCollection $translations = null;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getEventName(): ?string
    {
        return $this->eventName;
    }

    public function setEventName(?string $eventName): void
    {
        $this->eventName = $eventName;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): void
    {
        $this->category = $category;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    public function getTranslations(): ?ServiceTranslationCollection
    {
        return $this->translations;
    }

    public function setTranslations(?ServiceTranslationCollection $translations): void
    {
        $this->translations = $translations;
    }
}
