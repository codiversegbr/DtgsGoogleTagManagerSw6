<?php

namespace Dtgs\GoogleTagManager\Subscriber;

use Shopware\Core\Framework\Adapter\Cache\Event\HttpCacheKeyEvent;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Ensures the storefront cache depends on the GTM consent cookie to prevent
 * serving pages without GTM after re-consent.
 */
class HttpCacheKeySubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly SystemConfigService $systemConfigService
    )
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            HttpCacheKeyEvent::class => 'onHttpCacheKey',
        ];
    }

    public function onHttpCacheKey(HttpCacheKeyEvent $event): void
    {
        $request = $event->request;

        // Only act on GET requests (typical for cached pages)
        if ($request->getMethod() !== 'GET') {
            return;
        }

        if ($request->getPathInfo() === '/admin') {
            return;
        }

        if (substr($request->getPathInfo(), 0, 5) === '/api/') {
            return;
        }

        $config = (array) $this->systemConfigService->get('DtgsGoogleTagManagerSw6.config');

        $loadAfterConsent = (bool)($config['loadGoogleScriptAfterConsent'] ?? false);
        $pluginActiveInSc = (bool)($config['pluginActiveInSaleschannel'] ?? true);
        $removeContainer = (bool)($config['removeContainerCode'] ?? false);

        if (!$pluginActiveInSc || $removeContainer) {
            return;
        }

        if (!$loadAfterConsent) {
            return;
        }

        $cookieVal = $request->cookies->get('dtgsAllowGtmTracking', '0');
        $cookieVal = (string) ((int) $cookieVal === 1 ? 1 : 0);

        // Add a dedicated part to the cache key to distinguish the consent state
        $event->add('dtgs-gtm-consent', $cookieVal);
    }
}
