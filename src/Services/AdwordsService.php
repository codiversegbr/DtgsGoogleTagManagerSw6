<?php declare(strict_types=1);

namespace Dtgs\GoogleTagManager\Services;

use Dtgs\GoogleTagManager\Components\Helper\LoggingHelper;
use Dtgs\GoogleTagManager\Components\Helper\PriceHelper;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;

/**
 * @deprecated since 6.2.0
 */
class AdwordsService
{

    private $systemConfigService;
    private $generalTagsService;
    private $priceHelper;
    private $loggingHelper;

    public function __construct(SystemConfigService $systemConfigService,
                                GeneralTagsService $generalTagsService,
                                PriceHelper $priceHelper,
                                LoggingHelper $loggingHelper)
    {
        $this->systemConfigService = $systemConfigService;
        $this->generalTagsService = $generalTagsService;
        $this->priceHelper = $priceHelper;
        $this->loggingHelper = $loggingHelper;
    }

    public function getGtmConfig($salesChannelId) {
        return $tagManagerConfig = $this->systemConfigService->get('DtgsGoogleTagManagerSw6.config', $salesChannelId);
    }

    /**
     * SW6 ready
     *
     */
    public function getAdwordsId($salesChannelId) {

        $tagManagerConfig = $this->getGtmConfig($salesChannelId);

        if(isset($tagManagerConfig['googleAdwordsId'])) {
            return $tagManagerConfig['googleAdwordsId'];
        }

        return false;

    }

    /**
     * @param $enhancedEcomTags
     * @return false|string
     */
    public function prepareTagsForView($enhancedEcomTags)
    {
        return json_encode($enhancedEcomTags);
    }

    /**
     * since 6.0.5
     *
     * @param array $tags
     * @param string $event_name
     * @return array
     */
    private function addEvent($tags, $event_name = '')
    {
        $event_array = [];
        if($event_name != '') $event_array = ['event' => $event_name];

        return array_merge(
            $event_array,
            $tags
        );
    }

    /**
     * SW6 ready
     *
     * @param OrderEntity $order
     * @param SalesChannelContext $context
     * @return array
     * @throws \Exception
     */
    public function getPurchaseConfirmationTags(OrderEntity $order, SalesChannelContext $context) {

        $adwords_tags = [];

        $product_tax_rate = 19;
        $shipping_tax_rate = 19;

        if(!$shipping_tax_rate) $shipping_tax_rate = $product_tax_rate;

        //Finish fields
        $adwords_tags['transaction_id'] = $order->getOrderNumber();
        $adwords_tags['value'] = (float) $this->priceHelper->getPrice($order->getAmountTotal(), $product_tax_rate, $context);
        $adwords_tags['currency'] = $context->getCurrency()->getIsoCode();
        $adwords_tags['tax'] = $order->getAmountTotal() - $order->getAmountNet();
        $adwords_tags['shipping'] = (float) $this->priceHelper->getPrice($order->getShippingTotal(), $shipping_tax_rate, $context);
        $adwords_tags['aw_merchant_id'] = $this->getAdwordsId($context->getSalesChannel()->getId());
        $adwords_tags['items'] = $this->getBasketItems($order->getLineItems());

        $localeCode = $this->generalTagsService->getLocaleCode($context->getContext());
        $adwords_tags['aw_feed_country'] = $this->generalTagsService->getCldrCountryCode($localeCode);
        $adwords_tags['aw_feed_language'] = $this->generalTagsService->getCldrLanguageCode($localeCode);

        $adwords_tags = $this->addEvent($adwords_tags, 'purchase');
        return $adwords_tags;

    }

    /**
     * @param $listing
     * @return array
     * @throws \Exception
     */
    private function getBasketItems($listing) {

        $tags = array();
        if(empty($listing)) return $tags;

        foreach($listing as $product) {
            /** @var LineItem $product */
            $item = array(
                'id'        =>  $product->getId(),
                'price'     =>  $product->getPrice()->getUnitPrice(),
                'quantity'  =>  $product->getQuantity(),
            );

            $tags[] = $item;
        }

        return $tags;

    }

}
