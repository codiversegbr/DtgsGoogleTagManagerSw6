<?php declare(strict_types=1);

namespace Dtgs\GoogleTagManager\Subscriber;

use Dtgs\GoogleTagManager\Services\AdwordsService;
use Dtgs\GoogleTagManager\Services\CustomerTagsService;
use Dtgs\GoogleTagManager\Services\DatalayerService;
use Dtgs\GoogleTagManager\Services\EnhancedEcommerceService;
use Dtgs\GoogleTagManager\Services\Ga4Service;
use Dtgs\GoogleTagManager\Services\GeneralTagsService;
use Dtgs\GoogleTagManager\Services\RemarketingService;
use Shopware\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotEntity;
use Shopware\Core\Content\Cms\SalesChannel\Struct\ProductListingStruct;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Framework\Struct\ArrayEntity;
use Shopware\Core\Framework\Struct\Struct;
use Shopware\Core\PlatformRequest;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Page\Account\Login\AccountLoginPageLoadedEvent;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPageLoadedEvent;
use Shopware\Storefront\Page\Account\Order\AccountOrderPageLoadedEvent;
use Shopware\Storefront\Page\Account\Overview\AccountOverviewPageLoadedEvent;
use Shopware\Storefront\Page\Account\PaymentMethod\AccountPaymentMethodPageLoadedEvent;
use Shopware\Storefront\Page\Account\Profile\AccountProfilePageLoadedEvent;
use Shopware\Storefront\Page\Address\Listing\AddressListingPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Cart\CheckoutCartPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Finish\CheckoutFinishPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Register\CheckoutRegisterPageLoadedEvent;
use Shopware\Storefront\Page\Contact\ContactPageLoadedEvent;
use Shopware\Storefront\Page\LandingPage\LandingPageLoadedEvent;
use Shopware\Storefront\Page\Maintenance\MaintenancePageLoadedEvent;
use Shopware\Storefront\Page\Navigation\Error\ErrorPageLoadedEvent;
use Shopware\Storefront\Page\Navigation\NavigationPageLoadedEvent;
use Shopware\Storefront\Page\Newsletter\Register\NewsletterRegisterPageLoadedEvent;
use Shopware\Storefront\Page\Newsletter\Subscribe\NewsletterSubscribePageLoadedEvent;
use Shopware\Storefront\Page\Page;
use Shopware\Storefront\Page\PageLoadedEvent;
use Shopware\Storefront\Page\Product\ProductPageLoadedEvent;
use Shopware\Storefront\Page\Search\SearchPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class GeneralSubscriber implements EventSubscriberInterface
{
    /**
     * @var SystemConfigService
     */
    private $systemConfigService;
    /**
     * @var DatalayerService
     */
    private $datalayerService;
    /**
     * @var Ga4Service
     */
    private $ga4Service;
    /**
     * @var EnhancedEcommerceService
     */
    private $enhancedEcomService;
    /**
     * @var RemarketingService
     */
    private $remarketingService;
    /**
     * @var AdwordsService
     */
    private $adwordsService;
    /**
     * @var GeneralTagsService
     */
    private $generalTagsService;
    /**
     * @var CustomerTagsService
     */
    private $customerTagsService;

    public function __construct(SystemConfigService $systemConfigService,
                                DatalayerService $datalayerService,
                                Ga4Service $ga4Service,
                                EnhancedEcommerceService $enhancedEcomService,
                                RemarketingService $remarketingService,
                                AdwordsService $adwordsService,
                                GeneralTagsService $generalTagsService,
                                CustomerTagsService $customerTagsService)
    {
        $this->systemConfigService = $systemConfigService;
        $this->datalayerService = $datalayerService;
        $this->ga4Service = $ga4Service;
        $this->enhancedEcomService = $enhancedEcomService;
        $this->remarketingService = $remarketingService;
        $this->adwordsService = $adwordsService;
        $this->generalTagsService = $generalTagsService;
        $this->customerTagsService = $customerTagsService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ProductPageLoadedEvent::class => 'onPageLoaded',
            //
            CheckoutCartPageLoadedEvent::class => 'onPageLoaded',
            CheckoutConfirmPageLoadedEvent::class => 'onPageLoaded',
            CheckoutRegisterPageLoadedEvent::class => 'onPageLoaded',
            CheckoutFinishPageLoadedEvent::class => 'onPageLoaded',
            //
            AccountOverviewPageLoadedEvent::class => 'onPageLoaded',
            AccountProfilePageLoadedEvent::class => 'onPageLoaded',
            AccountLoginPageLoadedEvent::class => 'onPageLoaded',
            AccountOrderPageLoadedEvent::class => 'onPageLoaded',
            AccountPaymentMethodPageLoadedEvent::class => 'onPageLoaded',
            AddressListingPageLoadedEvent::class => 'onPageLoaded',
            //
            SearchPageLoadedEvent::class => 'onPageLoaded',
            //
            NavigationPageLoadedEvent::class => 'onPageLoaded',
            LandingPageLoadedEvent::class => 'onPageLoaded',
            //
            ErrorPageLoadedEvent::class => 'onPageLoaded',
            MaintenancePageLoadedEvent::class => 'onPageLoaded',
            //
            NewsletterSubscribePageLoadedEvent::class => 'onPageLoaded',
        ];
    }

    /**
     * Event für alle Seiten
     *
     * @param PageLoadedEvent $event
     * @throws \Exception
     */
    public function onPageLoaded($event)
    {
        /** @var Page $page */
        $page = $event->getPage();
        $status = 'enabled';

        $salesChannelId = $event->getSalesChannelContext()->getSalesChannel()->getId();
        $containerIds = $this->datalayerService->getContainerIds($salesChannelId);
        $tagManagerConfig = $this->datalayerService->getGtmConfig($salesChannelId);
        if(!$containerIds && $tagManagerConfig['removeContainerCode'] === false) {
            $status = 'disabled';
        }

        //The following tags will always be there
        $generalTags = $this->generalTagsService->getGeneralTags($page, $event->getSalesChannelContext()->getContext(), $event->getRequest());
        $customerTags = $this->customerTagsService->getCustomerTags($event->getSalesChannelContext()->getCustomer(), $event->getSalesChannelContext()->getContext());
        $utmTags = $this->generalTagsService->getUtmTags($event->getRequest());
        //Specific page tags
        $navigationTags = [];
        $accountTags = [];
        $detailTags = [];
        $checkoutTags = [];
        $searchTags = [];
        //Remarketing - V6.0.1
        $remarketingTags = [];
        //EnhancedEcommerce - 6.0.2
        $enhancedEcomTags = [];
        //GA4 - 6.2.0
        $ga4Tags = [];

        /**
         * Code insertion delay exception on finish pages - since 6.2.9
         */
        $codeDelayActive = $tagManagerConfig['delayCodeInsertion'];

        switch (get_class($event)) {
            case ProductPageLoadedEvent::class:
                $detailTags = $this->datalayerService->getDetailTags($event->getPage()->getProduct(), $event->getSalesChannelContext());
                $remarketingTags = $this->remarketingService->getDetailTags($event->getPage()->getProduct(), $event->getSalesChannelContext());
                $ga4Tags = $this->ga4Service->getDetailTags($event->getPage()->getProduct(), $event->getSalesChannelContext());
                $enhancedEcomTags = $this->enhancedEcomService->getDetailTags($event->getPage()->getProduct(), $event->getSalesChannelContext());

                $addToCartInfo = new ArrayEntity([
                    'price' => $ga4Tags['ecommerce']['items'][0]['price'],
                    'sku' => $ga4Tags['ecommerce']['items'][0]['item_id'],
                    'category' => $ga4Tags['ecommerce']['items'][0]['item_category'],
                ]);

                if(isset($ga4Tags['ecommerce']['items'][0]['item_variant'])) {
                    $addToCartInfo->set('variantname', $ga4Tags['ecommerce']['items'][0]['item_variant']);
                }

                $page->addExtension('GtmAddToCartInfo', $addToCartInfo);
                break;
            case CheckoutCartPageLoadedEvent::class:
            case CheckoutConfirmPageLoadedEvent::class:
            case CheckoutRegisterPageLoadedEvent::class:
                $checkoutTags = $this->datalayerService->getCheckoutTags($page->getCart(), $event->getSalesChannelContext());
                $remarketingTags = $this->remarketingService->getCheckoutTags($page->getCart(), $event->getSalesChannelContext());
                $ga4Tags = $this->ga4Service->getCheckoutTags($page->getCart(), $event);
                $enhancedEcomTags = $this->enhancedEcomService->getCheckoutTags($page->getCart(), $event);
                break;
            case CheckoutFinishPageLoadedEvent::class:
                $checkoutTags = $this->datalayerService->getFinishTags($page->getOrder(), $event->getSalesChannelContext());
                $remarketingTags = $this->remarketingService->getPurchaseConfirmationTags($page->getOrder(), $event->getSalesChannelContext());
                $ga4Tags = $this->ga4Service->getPurchaseConfirmationTags($page->getOrder(), $event->getSalesChannelContext());
                $enhancedEcomTags = $this->enhancedEcomService->getPurchaseConfirmationTags($page->getOrder(), $event->getSalesChannelContext());
                /**
                 * Code insertion delay exception on finish pages - since 6.2.9
                 */
                if(isset($tagManagerConfig['delayCodeInsertionFinishpageException']) && $tagManagerConfig['delayCodeInsertionFinishpageException'] === true) {
                    $codeDelayActive = false;
                }
                break;
            case AccountOverviewPageLoadedEvent::class:
            case AccountProfilePageLoadedEvent::class:
            case AccountLoginPageLoadedEvent::class:
            case AccountEditOrderPageLoadedEvent::class:
            case AccountOrderPageLoadedEvent::class:
            case AddressListingPageLoadedEvent::class:
            case AccountPaymentMethodPageLoadedEvent::class:
                $accountTags = $this->datalayerService->getAccountTags();
                $remarketingTags = $this->remarketingService->getBasicTags($event->getRequest());
                break;
            case SearchPageLoadedEvent::class:
                $searchTags = $this->datalayerService->getSearchTags($page->getSearchTerm(), $page->getListing());
                $remarketingTags = $this->remarketingService->getSearchTags($event->getRequest());
                $enhancedEcomTags = $this->enhancedEcomService->getSearchTags($page->getSearchTerm(), $page->getListing(), $event->getSalesChannelContext());
                break;
            case NavigationPageLoadedEvent::class:
                $navigationId = $event->getRequest()->get('navigationId', $event->getSalesChannelContext()->getSalesChannel()->getNavigationCategoryId());
                $navigationTags = $this->datalayerService->getNavigationTags($navigationId, $event->getSalesChannelContext());

                /** @var SalesChannelProductEntity[] $products */
                $listing = $this->getListingOnNavigationPage($event->getPage());
                if($listing) {
                    $remarketingTags = $this->remarketingService->getNavigationTags($navigationId, $listing, $event->getSalesChannelContext(), $event->getRequest());
                    $enhancedEcomTags = $this->enhancedEcomService->getNavigationTags($navigationId, $listing, $event->getSalesChannelContext());
                    $ga4Tags = $this->ga4Service->getNavigationTags($navigationId, $listing, $event->getSalesChannelContext());
                }
                else {
                    $remarketingTags = $this->remarketingService->getBasicTags($event->getRequest());
                    $enhancedEcomTags = $this->enhancedEcomService->getBasicTags($event->getSalesChannelContext());
                }
                break;
            case ErrorPageLoadedEvent::class:
            case NewsletterSubscribePageLoadedEvent::class:
            case MaintenancePageLoadedEvent::class:
            case LandingPageLoadedEvent::class:
            default:
                $remarketingTags = $this->remarketingService->getBasicTags($event->getRequest());
                $enhancedEcomTags = $this->enhancedEcomService->getBasicTags($event->getSalesChannelContext());
                break;
        }

        $datalayerTags = $this->datalayerService->prepareTagsForView(
            $generalTags,
            $navigationTags,
            $accountTags,
            $detailTags,
            $checkoutTags,
            $customerTags,
            $utmTags,
            $searchTags
        );

        $remarketingTags = $this->remarketingService->prepareTagsForView($remarketingTags);
        $ga4Tags = $this->ga4Service->prepareTagsForView($ga4Tags);
        $enhancedEcomTags = $this->enhancedEcomService->prepareTagsForView($enhancedEcomTags);

        $adwords_tracking_enabled = isset($tagManagerConfig['googleAdwordsId']) && $tagManagerConfig['googleAdwordsId'] != '';

        /**
         * UserCentrics compatiblity & CookieFirst compatiblity - since 6.1.26
         */
        $additionalServiceCode = '';
        if(isset($tagManagerConfig['usercentricsEnabled']) && $tagManagerConfig['usercentricsEnabled'] === true) {
            $additionalServiceCode .= ' type="text/plain" data-usercentrics="Google Tag Manager"';
        }
        if(isset($tagManagerConfig['cookiefirstEnabled']) && $tagManagerConfig['cookiefirstEnabled'] != 'off' && $tagManagerConfig['cookiefirstEnabled'] != '') {
            $additionalServiceCode .= ' type="text/plain" data-cookiefirst-category="'.$tagManagerConfig['cookiefirstEnabled'].'"';
        }

        $page->addExtension('GoogleTagManagerConfig', new ArrayEntity([
            'containerIds' => $containerIds,
            'tags' => $datalayerTags,
            'remarketing_tags' => $remarketingTags,
            'ga4_tags' => $ga4Tags,
            'enhancedecommerce_tags' => $enhancedEcomTags,
            'adwords_tracking_enabled' => $adwords_tracking_enabled,
            'status' => $status,
            'additionalServiceCode' => $additionalServiceCode,
            'code_delay_active' => $codeDelayActive,
        ]));

    }

    /**
     * @param Struct $page
     * @return array|void
     */
    private function getListingOnNavigationPage(Struct $page)
    {
        $cmsPage = $page->getCmsPage();

        if (!$cmsPage || $cmsPage->getType() !== 'product_list') {
            return;
        }

        $slots = $cmsPage->getSections()->getBlocks()->getSlots();
        foreach($slots as $slot) {
            /** @var CmsSlotEntity $slot */
            if($slot->getType() == 'product-listing') {
                /** @var ProductListingStruct $productListingStruct */
                $productListingStruct = $slot->getData();
                break;
            }
        }
        if(!isset($productListingStruct) || !is_a($productListingStruct, 'Shopware\Core\Content\Cms\SalesChannel\Struct\ProductListingStruct')) return;

        return $productListingStruct->getListing()->getElements();
    }
}
