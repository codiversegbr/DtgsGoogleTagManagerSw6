<?php declare(strict_types=1);

namespace Dtgs\GoogleTagManager\Framework\Cookie;

use Dtgs\GoogleTagManager\Core\Content\DtgsGtmCustomService\CustomServiceEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Framework\Cookie\CookieProviderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class CustomCookieProvider implements CookieProviderInterface {

    private $originalService;
    private SystemConfigService $systemConfigService;
    private $requestStack;
    private $customServiceRepository;
    private $translator;
    private CacheInterface $cache;

    public function __construct(
        CookieProviderInterface $service,
        SystemConfigService     $systemConfigService,
        RequestStack            $requestStack,
        EntityRepository        $customServiceRepository,
        TranslatorInterface     $translator,
        CacheInterface          $cache
    )
    {
        $this->originalService = $service;
        $this->systemConfigService = $systemConfigService;
        $this->requestStack = $requestStack;
        $this->customServiceRepository = $customServiceRepository;
        $this->translator = $translator;
        $this->cache = $cache;
    }

    private const cookie = [
        'snippet_name' => 'cookie.dtgsGtmTracking',
        'cookie' => 'dtgsAllowGtmTracking',
        'value' => '1',
        'expiration' => '30'
    ];

    private const cookieGroup = [
        'snippet_name' => 'cookie.groupStatistical',
        'snippet_description' => 'cookie.groupStatisticalDescription',
        'entries' => [
            self::cookie
        ],
    ];

    public function getCookieGroups(): array
    {
        $cookieGroups = $this->originalService->getCookieGroups();

        if(!$this->gtmPluginActiveInSaleschannel()) return $cookieGroups;

        $addedToGroup = false;

        foreach ($cookieGroups as $cookieGroupKey => $cookieGroup) {
            if ($cookieGroup['snippet_name'] == 'cookie.groupStatistical') {
                $cookieGroups[$cookieGroupKey]['entries'][] = self::cookie;
                $addedToGroup = true;
            }
        }

        if(!$addedToGroup) {
            $cookieGroups = array_merge(
                $cookieGroups,
                [
                    self::cookieGroup
                ]
            );
        }

        $cookieGroups = $this->customServiceCookieGroups($cookieGroups);

        return $cookieGroups;
    }

    private function customServiceCookieGroups(array $groups): array
    {
        $context = Context::createDefaultContext();
        $salesChannelId = 'default';

        if ($this->requestStack
            && ($request = $this->requestStack->getCurrentRequest())
            && ($salesChannelContext = $request->attributes->get('sw-sales-channel-context'))
        ) {
            $context = $salesChannelContext->getContext();
            $salesChannelId = $salesChannelContext->getSalesChannelId();
        }

        $cacheKey = 'dtgs_gtm_custom_cookie_services_' . $salesChannelId . '_' . $context->getLanguageId();

        $servicesData = $this->cache->get($cacheKey, function (ItemInterface $item) use ($context) {
            $item->expiresAfter(3600); // 1 hour

            $criteria = (new Criteria())->addFilter(new EqualsFilter('active', true));
            $criteria->addAssociation('translations');
            $services = $this->customServiceRepository->search($criteria, $context);

            $data = [];
            /** @var CustomServiceEntity $entity */
            foreach ($services->getElements() as $entity) {
                $name = $entity->getName() ?: ($entity->getTranslated()['name'] ?? '');
                $eventName = method_exists($entity, 'getEventName') ? (string) $entity->getEventName() : (string) ($entity->getTranslated()['eventName'] ?? '');
                $category = method_exists($entity, 'getCategory') ? (string) $entity->getCategory() : (string) ($entity->getTranslated()['category'] ?? '');

                if ($name === '' || $eventName === '') {
                    continue;
                }

                $data[] = [
                    'name' => $name,
                    'eventName' => $eventName,
                    'category' => $category,
                ];
            }

            return $data;
        });

        if (empty($servicesData)) {
            return $groups;
        }

        // Find indices of Statistical and Marketing groups if present
        $statIndex = null;
        $marketingIndex = null;
        foreach ($groups as $idx => $group) {
            if (($group['snippet_name'] ?? '') === 'cookie.groupStatistical') {
                $statIndex = $idx;
            }
            if (($group['snippet_name'] ?? '') === 'cookie.groupMarketing') {
                $marketingIndex = $idx;
            }
        }

        foreach ($servicesData as $service) {
            $name = $service['name'];
            $eventName = $service['eventName'];
            $category = $service['category'];

            // Derive a cookie key for this service
            $cookie = self::buildCookieKey($eventName);

            $label = $this->translator->trans(
                'cookie.dtgs-gtm-svc-generic-service.label',
                ['%name%' => $name]
            );

            $entry = [
                'snippet_name'            => $label,
                'cookie'                  => $cookie,
                'expiration'              => '30',
                'value'                   => '1',
            ];

            $targetIdx = null;
            if (strtolower($category) === 'statistic' || strtolower($category) === 'statistical') {
                $targetIdx = $statIndex;
            } else {
                // default to Marketing if unknown
                $targetIdx = $marketingIndex;
            }

            if ($targetIdx === null) {
                // If no target group exists, append under a Marketing-like custom group
                $groups[] = [
                    'snippet_name' => 'cookie.groupMarketing',
                    'entries' => [ $entry ],
                ];
            } else {
                if (!isset($groups[$targetIdx]['entries']) || !is_array($groups[$targetIdx]['entries'])) {
                    $groups[$targetIdx]['entries'] = [];
                }
                $groups[$targetIdx]['entries'][] = $entry;
            }
        }

        return $groups;
    }

    public static function buildCookieKey(string $name): string
    {
        $slug = strtolower(trim($name));
        $slug = preg_replace('~[^a-z0-9]+~', '-', $slug ?? '');
        $slug = trim((string) $slug, '-');
        if ($slug === '') {
            $slug = 'custom-service';
        }
        return 'dtgs-gtm-svc-' . $slug;
    }

    private function gtmPluginActiveInSaleschannel()
    {
        $request = $this->requestStack->getCurrentRequest();
        /** @var SalesChannelContext|null $salesChannelContext */
        $salesChannelContext = $request ? $request->attributes->get('sw-sales-channel-context') : null;
        $salesChannelId = $salesChannelContext->getSalesChannel()->getId();
        $tagManagerConfig = $this->systemConfigService->get('DtgsGoogleTagManagerSw6.config', $salesChannelId);

        return $tagManagerConfig['pluginActiveInSaleschannel'] ?? true;
    }
}
