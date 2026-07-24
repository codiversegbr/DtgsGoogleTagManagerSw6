<?php declare(strict_types=1);

namespace Dtgs\GoogleTagManager\Subscriber;

use Dtgs\GoogleTagManager\Core\Content\DtgsGtmCustomService\CustomServiceEntity;
use Dtgs\GoogleTagManager\Framework\Cookie\CustomCookieProvider;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Storefront\Event\StorefrontRenderEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class GtmServicesStorefrontSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly EntityRepository $customServiceRepository,
        private readonly CacheInterface $cache
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
        $salesChannelContext = $event->getSalesChannelContext();
        $context = $salesChannelContext->getContext();

        $cacheKey = 'dtgs_gtm_custom_services_' . $salesChannelContext->getSalesChannelId() . '_' . $context->getLanguageId();

        $services = $this->cache->get($cacheKey, function (ItemInterface $item) use ($context) {
            $item->expiresAfter(3600); // 1 hour

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

            return $services;
        });

        $event->setParameter('dtgsGtmCustomServices', $services);
        $event->setParameter('dtgsGtmConsentCookieName', 'dtgsAllowGtmTracking');
    }
}
