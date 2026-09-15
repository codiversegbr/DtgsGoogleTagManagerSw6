<?php

namespace Dtgs\GoogleTagManager\Services\Interfaces;

use Shopware\Core\Checkout\Order\Event\OrderStateMachineStateChangeEvent;
use Shopware\Storefront\Page\Checkout\Finish\CheckoutFinishPageLoadedEvent;

interface MeasurementProtocolSubscriberInterface
{
    /**
     * Handles the checkout finish page load.
     *
     * Depending on the configured tracking trigger the server-side purchase event
     * is either sent immediately (checkout_complete) or the required client data is
     * stored on the order to be sent once the order is marked as paid (order_paid).
     *
     * @param CheckoutFinishPageLoadedEvent $event
     * @return void
     */
    public function onCheckoutFinish(CheckoutFinishPageLoadedEvent $event): void;

    /**
     * Sends the server-side purchase event via the Measurement Protocol once the
     * order transaction enters the "paid" state (order_paid tracking trigger).
     *
     * @param OrderStateMachineStateChangeEvent $event
     * @return void
     */
    public function onOrderPaid(OrderStateMachineStateChangeEvent $event): void;
}
