<?php declare(strict_types=1);

namespace Dtgs\GoogleTagManager\Framework\Cookie;

use Shopware\Storefront\Framework\Cookie\CookieProviderInterface;

class CustomCookieProvider implements CookieProviderInterface {

    private $originalService;

    public function __construct(CookieProviderInterface $service)
    {
        $this->originalService = $service;
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
}
