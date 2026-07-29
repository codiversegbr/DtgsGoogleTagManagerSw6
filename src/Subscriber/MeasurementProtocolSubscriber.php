<?php declare(strict_types=1);

namespace Dtgs\GoogleTagManager\Subscriber;

use Dtgs\GoogleTagManager\Components\Helper\LoggingHelper;
use Dtgs\GoogleTagManager\Services\Interfaces\Ga4ServiceInterface;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Page\Checkout\Finish\CheckoutFinishPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class MeasurementProtocolSubscriber implements EventSubscriberInterface
{
    private SystemConfigService $systemConfigService;
    private Ga4ServiceInterface $ga4Service;
    private LoggingHelper $loggingHelper;
    private RequestStack $requestStack;

    public function __construct(
        SystemConfigService $systemConfigService,
        Ga4ServiceInterface $ga4Service,
        LoggingHelper $loggingHelper,
        RequestStack $requestStack
    ) {
        $this->systemConfigService = $systemConfigService;
        $this->ga4Service = $ga4Service;
        $this->loggingHelper = $loggingHelper;
        $this->requestStack = $requestStack;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutFinishPageLoadedEvent::class => 'onCheckoutFinish',
        ];
    }

    public function onCheckoutFinish(CheckoutFinishPageLoadedEvent $event): void
    {
        $salesChannelId = $event->getSalesChannelContext()->getSalesChannel()->getId();
        $config = $this->getConfig($salesChannelId);

        if (!$this->isMeasurementProtocolEnabled($config)) {
            return;
        }

        if (($config['measurementProtocolTrackingTrigger'] ?? 'checkout_complete') !== 'checkout_complete') {
            return;
        }

        $order = $event->getPage()->getOrder();
        $context = $event->getSalesChannelContext();

        $request = $event->getRequest();
        $this->sendPurchaseEvent($order, $context, $config, $request);
    }

    private function sendPurchaseEvent(OrderEntity $order, SalesChannelContext $context, array $config, $request): void
    {
        $ga4Tags = $this->ga4Service->getPurchaseConfirmationTags($order, $context);

        $ecommerceData = $ga4Tags['ecommerce'] ?? [];

        $eventParams = [];
        foreach ($ecommerceData as $key => $value) {
            if ($key !== 'event') {
                $eventParams[$key] = $value;
            }
        }

        //Custom-Attribut mitschicken, um zwischen Server-Event und Client-Event unterscheiden zu können
        $eventParams['event_source'] = 'server';
        $eventParams['engagement_time_msec'] = 1;

        $clientId = $this->getClientIdFromCookie($request, $order);

        $sessionId = $this->getSessionIdFromCookie($request, $config);
        if ($sessionId !== null) {
            $eventParams['session_id'] = $sessionId;
        }

        $payload = [
            'client_id' => $clientId,
            'consent' => $this->getConsentFromCookie($request),
            'events' => [
                [
                    'name' => 'purchase',
                    'params' => $eventParams,
                ],
            ],
        ];

        $endpoint = $config['measurementProtocolEndpoint'] ?? 'https://region1.google-analytics.com/mp/collect';
        $apiSecret = $config['measurementProtocolApiSecret'] ?? '';
        $measurementId = $config['measurementProtocolMeasurementId'] ?? '';

        $url = $endpoint . '?' . http_build_query([
            'measurement_id' => $measurementId,
            'api_secret' => $apiSecret,
        ]);

        $this->sendRequest($url, $payload);
    }

    /**
     * Builds the consent block for the Measurement Protocol payload based on the consent cookies.
     *
     * @return array<string, string>
     */
    private function getConsentFromCookie($request): array
    {
        // Advertising consent is stored either in the dedicated Google Ads cookie
        // or (since SW 6.6.9) in the general GTM tracking cookie.
        $adsEnabled = $request->cookies->get('google-ads-enabled', '') === '1'
            || $request->cookies->get('dtgsAllowGtmTracking', '') === '1';

        $consentValue = $adsEnabled ? 'GRANTED' : 'DENIED';

        return [
            'ad_user_data' => $consentValue,
            'ad_personalization' => $consentValue,
        ];
    }

    private function getClientIdFromCookie($request, OrderEntity $order): string
    {
        $gaCookie = $request->cookies->get('_ga', '');

        // _ga=GA1.1.123456789.1691234567 => client_id = 123456789.1691234567
        if (preg_match('/GA\d+\.\d+\.(.+)/', $gaCookie, $matches)) {
            return $matches[1];
        }

        // Fallback if cookie is not available
        return $order->getOrderNumber() . '.' . time();
    }

    private function getSessionIdFromCookie($request, array $config): ?string
    {
        $measurementId = $config['measurementProtocolMeasurementId'] ?? '';

        // Measurement ID is e.g. G-XXXXXXXXXX, cookie name is _ga_XXXXXXXXXX (without "G-")
        $streamId = str_replace('G-', '', $measurementId);
        if (empty($streamId)) {
            return null;
        }

        $cookieName = '_ga_' . $streamId;
        $cookieValue = $request->cookies->get($cookieName, '');

        // _ga_xxxxxxxxxx=GS1.1.1697002512.2.1.1697001234.45.0.0 => session_id = 1697002512
        if (preg_match('/GS\d+\.\d+\.(\d+)/', $cookieValue, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function sendRequest(string $url, array $payload): void
    {
        $jsonPayload = json_encode($payload);

        if ($this->loggingHelper->loggingType('debug')) {
            $this->loggingHelper->logMsg('Measurement Protocol Request URL: ' . $url);
            $this->loggingHelper->logMsg('Measurement Protocol Payload: ' . $jsonPayload);
        }

        try {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if ($this->loggingHelper->loggingType('debug')) {
                $this->loggingHelper->logMsg('Measurement Protocol Response Code: ' . $httpCode);
                $this->loggingHelper->logMsg('Measurement Protocol Response: ' . ($response ?: '(empty)'));
            }

            if (curl_errno($ch)) {
                $this->loggingHelper->logMsg('Measurement Protocol cURL Error: ' . curl_error($ch));
            }

            curl_close($ch);
        } catch (\Exception $e) {
            $this->loggingHelper->logMsg('Measurement Protocol Error: ' . $e->getMessage());
        }
    }

    private function getConfig(string $salesChannelId): array
    {
        return $this->systemConfigService->get('DtgsGoogleTagManagerSw6.config', $salesChannelId) ?? [];
    }

    private function isMeasurementProtocolEnabled(array $config): bool
    {
        return !empty($config['measurementProtocolEnabled'])
            && !empty($config['measurementProtocolApiSecret'])
            && !empty($config['measurementProtocolMeasurementId']);
    }
}
