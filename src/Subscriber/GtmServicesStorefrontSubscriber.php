<?php declare(strict_types=1);

namespace Dtgs\GoogleTagManager\Subscriber;

use Dtgs\GoogleTagManager\Core\Content\DtgsGtmCustomService\CustomServiceEntity;
use Dtgs\GoogleTagManager\Framework\Cookie\CustomCookieProvider;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Storefront\Event\StorefrontRenderEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class GtmServicesStorefrontSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly EntityRepository $customServiceRepository
    )
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            StorefrontRenderEvent::class => 'onStorefrontRender',
        ];
    }

    public function onStorefrontRender(StorefrontRenderEvent $event): void
    {
        // Use the current SalesChannel context to respect inheritance, translations, and visibility
        $context = $event->getSalesChannelContext()->getContext();
        $criteria = (new Criteria())->addFilter(new EqualsFilter('active', true));
        $criteria->addAssociation('translations');
        $result = $this->customServiceRepository->search($criteria, $context);

        $services = [];
        /** @var CustomServiceEntity $entity */
        foreach ($result->getElements() as $entity) {
            $name = $entity->getName() ?: ($entity->getTranslated()['name'] ?? '');
            $eventName = $entity->getEventName() ?? '';

            $cookie = CustomCookieProvider::buildCookieKey($eventName);

            if ($name === '' || $eventName === '') {
                continue; // skip incomplete entries
            }

            $services[] = [
                'name' => $name,
                'eventName' => $eventName,
                'cookie' => $cookie,
            ];
        }

        $event->setParameter('dtgsGtmCustomServices', $services);
        $event->setParameter('dtgsGtmConsentCookieName', 'dtgsAllowGtmTracking');
    }
}
