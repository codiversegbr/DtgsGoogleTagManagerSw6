<?php declare(strict_types=1);

namespace Dtgs\GoogleTagManager\Subscriber;

use Dtgs\GoogleTagManager\Components\Helper\LoggingHelper;
use Dtgs\GoogleTagManager\Services\Interfaces\Ga4ServiceInterface;
use Dtgs\GoogleTagManager\Services\Interfaces\MeasurementProtocolSubscriberInterface;
use Shopware\Core\Checkout\Order\Event\OrderStateMachineStateChangeEvent;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Page\Checkout\Finish\CheckoutFinishPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class MeasurementProtocolSubscriber implements EventSubscriberInterface, MeasurementProtocolSubscriberInterface
{
    /**
     * Custom-Field-Key, unter dem die für das serverseitige Purchase-Event benötigten
     * Client-Daten (client_id, session_id, consent, ...) an der Bestellung zwischengespeichert werden,
     * bis diese als bezahlt markiert wird.
     */
    private const PENDING_CUSTOM_FIELD = 'dtgs_gtm_mp_pending_purchase';

    private SystemConfigService $systemConfigService;
    private Ga4ServiceInterface $ga4Service;
    private LoggingHelper $loggingHelper;
    private RequestStack $requestStack;
    private EntityRepository $orderRepository;

    public function __construct(
        SystemConfigService $systemConfigService,
        Ga4ServiceInterface $ga4Service,
        LoggingHelper $loggingHelper,
        RequestStack $requestStack,
        EntityRepository $orderRepository
    ) {
        $this->systemConfigService = $systemConfigService;
        $this->ga4Service = $ga4Service;
        $this->loggingHelper = $loggingHelper;
        $this->requestStack = $requestStack;
        $this->orderRepository = $orderRepository;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutFinishPageLoadedEvent::class => 'onCheckoutFinish',
            'state_enter.order_transaction.state.paid' => 'onOrderPaid',
        ];
    }

    public function onCheckoutFinish(CheckoutFinishPageLoadedEvent $event): void
    {
        $salesChannelId = $event->getSalesChannelContext()->getSalesChannel()->getId();
        $config = $this->getConfig($salesChannelId);

        if (!$this->isMeasurementProtocolEnabled($config)) {
            return;
        }

        $trigger = $config['measurementProtocolTrackingTrigger'] ?? 'checkout_complete';

        $order = $event->getPage()->getOrder();
        $context = $event->getSalesChannelContext();
        $request = $event->getRequest();

        if ($trigger === 'order_paid') {
            // Beim Bestellabschluss stehen noch die GA-Cookies zur Verfügung.
            // Wir speichern die benötigten Daten an der Bestellung und senden das
            // Event erst, wenn die Bestellung als bezahlt markiert wird.
            $this->storePendingPurchase($order, $context, $config, $request);
            return;
        }

        // Standard: Beim Bestellabschluss im Shop tracken
        $eventParams = $this->buildEventParams($order, $context);

        $clientId = $this->getClientIdFromCookie($request);
        if ($clientId === null) {
            // No GA cookie available => do not send the event
            if ($this->loggingHelper->loggingType('debug')) {
                $this->loggingHelper->logMsg('Measurement Protocol: no _ga cookie found, purchase event not sent.');
            }
            return;
        }

        $sessionId = $this->getSessionIdFromCookie($request, $config);
        $consent = $this->getConsentFromCookie($request);
        $userId = $this->getCustomerNumber($order);

        $this->sendPurchaseEvent($config, $clientId, $sessionId, $consent, $userId, $eventParams);
    }

    /**
     * Wird ausgelöst, sobald eine Zahlungstransaktion der Bestellung in den Status "bezahlt" wechselt.
     * Sendet das serverseitige Purchase-Event, sofern beim Bestellabschluss entsprechende
     * Client-Daten zwischengespeichert wurden.
     */
    public function onOrderPaid(OrderStateMachineStateChangeEvent $event): void
    {
        $salesChannelId = $event->getSalesChannelId();
        $config = $this->getConfig($salesChannelId);

        if (!$this->isMeasurementProtocolEnabled($config)) {
            return;
        }

        if (($config['measurementProtocolTrackingTrigger'] ?? 'checkout_complete') !== 'order_paid') {
            return;
        }

        $context = $event->getContext();
        $order = $this->loadOrder($event->getOrderId(), $context);
        if ($order === null) {
            return;
        }

        $customFields = $order->getCustomFields() ?? [];
        $pending = $customFields[self::PENDING_CUSTOM_FIELD] ?? null;
        if (!is_array($pending) || empty($pending['event_params'])) {
            // Keine zwischengespeicherten Daten (z.B. kein GA-Cookie oder Bestellung nicht über den Shop abgeschlossen)
            return;
        }

        $this->sendPurchaseEvent(
            $config,
            $pending['client_id'] ?? null,
            $pending['session_id'] ?? null,
            $pending['consent'] ?? [],
            $pending['user_id'] ?? null,
            $pending['event_params']
        );

        // Zwischengespeicherte Daten entfernen, damit das Event nicht mehrfach gesendet wird.
        $this->clearPendingPurchase($order->getId(), $context);
    }

    /**
     * Speichert die für das serverseitige Purchase-Event benötigten Daten an der Bestellung.
     */
    private function storePendingPurchase(OrderEntity $order, SalesChannelContext $context, array $config, $request): void
    {
        $clientId = $this->getClientIdFromCookie($request);
        if ($clientId === null) {
            // No GA cookie available => nothing to send later
            if ($this->loggingHelper->loggingType('debug')) {
                $this->loggingHelper->logMsg('Measurement Protocol: no _ga cookie found, purchase event not stored for order_paid trigger.');
            }
            return;
        }

        $pending = [
            'client_id' => $clientId,
            'session_id' => $this->getSessionIdFromCookie($request, $config),
            'consent' => $this->getConsentFromCookie($request),
            'user_id' => $this->getCustomerNumber($order),
            'event_params' => $this->buildEventParams($order, $context),
        ];

        $this->orderRepository->update([
            [
                'id' => $order->getId(),
                'customFields' => [
                    self::PENDING_CUSTOM_FIELD => $pending,
                ],
            ],
        ], $context->getContext());
    }

    private function clearPendingPurchase(string $orderId, Context $context): void
    {
        $this->orderRepository->update([
            [
                'id' => $orderId,
                'customFields' => [
                    self::PENDING_CUSTOM_FIELD => null,
                ],
            ],
        ], $context);
    }

    private function loadOrder(string $orderId, Context $context): ?OrderEntity
    {
        $criteria = new Criteria([$orderId]);

        return $this->orderRepository->search($criteria, $context)->first();
    }

    /**
     * Baut die GA4-Event-Parameter aus den Purchase-Confirmation-Tags auf.
     *
     * @return array<string, mixed>
     */
    private function buildEventParams(OrderEntity $order, SalesChannelContext $context): array
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

        return $eventParams;
    }

    /**
     * Sendet das Purchase-Event via Measurement Protocol.
     *
     * @param array<string, mixed> $config
     * @param array<string, string> $consent
     * @param array<string, mixed> $eventParams
     */
    private function sendPurchaseEvent(array $config, ?string $clientId, ?string $sessionId, array $consent, ?string $userId, array $eventParams): void
    {
        if ($clientId === null) {
            if ($this->loggingHelper->loggingType('debug')) {
                $this->loggingHelper->logMsg('Measurement Protocol: no client_id available, purchase event not sent.');
            }
            return;
        }

        if ($sessionId !== null) {
            $eventParams['session_id'] = $sessionId;
        }

        $payload = [
            'client_id' => $clientId,
            'consent' => $consent,
            'events' => [
                [
                    'name' => 'purchase',
                    'params' => $eventParams,
                ],
            ],
        ];
        if ($userId !== null) {
            $payload['user_id'] = $userId;
        }

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
     * Reads the customer number from the order to be sent as user_id.
     */
    private function getCustomerNumber(OrderEntity $order): ?string
    {
        $orderCustomer = $order->getOrderCustomer();
        if ($orderCustomer === null) {
            return null;
        }

        $customerNumber = $orderCustomer->getCustomerNumber();
        if ($customerNumber === null || $customerNumber === '') {
            return null;
        }

        return $customerNumber;
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

    private function getClientIdFromCookie($request): ?string
    {
        $gaCookie = $request->cookies->get('_ga', '');

        // _ga=GA1.1.123456789.1691234567 => client_id = 123456789.1691234567
        if (preg_match('/GA\d+\.\d+\.(.+)/', $gaCookie, $matches)) {
            return $matches[1];
        }

        // No GA cookie available => no client_id
        return null;
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
