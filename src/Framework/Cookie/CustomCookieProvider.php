<?php declare(strict_types=1);

namespace Dtgs\GoogleTagManager\Framework\Cookie;

use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Framework\Cookie\CookieProviderInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class CustomCookieProvider implements CookieProviderInterface {

    private $originalService;
    private SystemConfigService $systemConfigService;
    private $requestStack;

    public function __construct(CookieProviderInterface $service,
                                SystemConfigService $systemConfigService,
                                RequestStack $requestStack)
    {
        $this->originalService = $service;
        $this->systemConfigService = $systemConfigService;
        $this->requestStack = $requestStack;
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

        return $cookieGroups;
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