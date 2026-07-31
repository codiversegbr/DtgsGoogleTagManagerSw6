import Plugin from 'src/plugin-system/plugin.class';
import { COOKIE_CONFIGURATION_UPDATE } from 'src/plugin/cookie/cookie-configuration.plugin';

import GtmAddToCartEvent from './events/add-to-cart.event';
import GtmRemoveFromCartEvent from './events/remove-from-cart.event';
import GtmQuantityChangeEvent from './events/quantity-change.event';
import GtmLoginEvent from './events/login.event';
import GtmSignUpEvent from './events/sign-up.event';
import CookieStorageHelper from 'src/helper/storage/cookie-storage.helper';
import LineItemHelper from 'src/plugin/google-analytics/line-item.helper';
import DomAccessHelper from 'src/helper/dom-access.helper';

export default class DtgsGoogleTagManagerPlugin extends Plugin
{
    init() {
        this.cookieEnabledName = 'dtgsAllowGtmTracking';
        this.cookieAdsEnabledName = window.googleAdsCookieName || 'google-ads-enabled';

        this.handleCookieChangeEvent();

        this.startGoogleTagManager();

        if (!CookieStorageHelper.getItem(this.cookieEnabledName)) {

            const offCanvasCartElement = document.querySelector('[data-off-canvas-cart]');
            if(offCanvasCartElement) {
                const offCanvasCartPlugin = window.PluginManager.getPluginInstanceFromElement(offCanvasCartElement, 'OffCanvasCart');
                if(offCanvasCartPlugin) offCanvasCartPlugin.$emitter.subscribe('offCanvasOpened', this.onOffCanvasOpenedForInitialQuantities.bind(this));
            }

            return;
        }

        this.fireCookieConsentEvent();

        //subscribe to opening off canvas WK - added in 6.3.14
        const offCanvasCartElement = document.querySelector('[data-off-canvas-cart]');
        if(offCanvasCartElement) {
            const offCanvasCartPlugin = window.PluginManager.getPluginInstanceFromElement(offCanvasCartElement, 'OffCanvasCart');
            if(offCanvasCartPlugin) offCanvasCartPlugin.$emitter.subscribe('offCanvasOpened', this.onOffCanvasOpened.bind(this));
        }

        const listingElement = document.querySelector('[data-listing-pagination]');
        if(listingElement) {
            const listingPlugin = window.PluginManager.getPluginInstanceFromElement(listingElement, 'Listing');
            if(listingPlugin) listingPlugin.$emitter.subscribe('Listing/afterRenderResponse', this.onPageSwitched.bind(this));
        }

        //subscribe to wishlist events - added in 6.3.16
        this.registerWishlistEvents();
        //subscribe to wishlist remove form
        const wishlistFormElement = DomAccessHelper.querySelector(document, '.product-wishlist-form', false);
        if (wishlistFormElement) {
            wishlistFormElement.addEventListener('submit', this.onWishlistRemoveFormSubmit.bind(this));
        }

    }

    fireCookieConsentEvent() {

        window.dataLayer.push({
            'event': 'cookieConsentGiven'
        });

    }

    fireSelectItemEvent(event) {

        let productBox = event.target.closest('.card-body');

        //Product Array
        let product= {
            'item_name': productBox.querySelector('input[name="product-name"]').value,
            'item_id': productBox.querySelector('input[name="dtgs-gtm-product-sku"]').value,
        };

        let variantname = productBox.querySelector('input[name="dtgs-gtm-product-variantname"]');
        let category = productBox.querySelector('input[name="dtgs-gtm-product-category"]');
        let price = productBox.querySelector('input[name="dtgs-gtm-product-price"]');
        let brand = productBox.querySelector('input[name="dtgs-gtm-product-brand"]');

        //Price, Brand Name & Kategorie optional
        if(variantname !== null) Object.assign(product, {'item_variant': variantname.value});
        if(category !== null) Object.assign(product, {'item_category': category.value});
        if(brand !== null) Object.assign(product, {'item_brand': brand.value});
        if(price !== null) Object.assign(product, {'price': Number(price.value)});

        // Clear the previous ecommerce object
        dataLayer.push({ ecommerce: null });
        dataLayer.push({
            'event': 'select_item',
            'ecommerce': {
                'item_list_name': 'Category',
                'items': [product]
            }
        });

    }

    startGoogleTagManager() {

        this.controllerName = window.controllerName;
        this.actionName = window.actionName;
        this.events = [];

        this.registerDefaultEvents();
        this.handleEvents();

    }

    handleEvents() {
        this.events.forEach(event => {
            if (!event.supports(this.controllerName, this.actionName)) {
                return;
            }

            event.execute();
        });
    }

    registerDefaultEvents() {
        this.registerEvent(GtmAddToCartEvent);
        this.registerEvent(GtmRemoveFromCartEvent);
        this.registerEvent(GtmQuantityChangeEvent);
        this.registerEvent(GtmLoginEvent);
        this.registerEvent(GtmSignUpEvent);
        this.registerSelectItemEvent();
    }

    registerEvent(event) {
        this.events.push(new event());
    }

    handleCookieChangeEvent() {
        document.$emitter.subscribe(COOKIE_CONFIGURATION_UPDATE, this.handleCookies.bind(this));
    }

    handleCookies(cookieUpdateEvent) {
        const updatedCookies = cookieUpdateEvent.detail;

        this.updateConsentMode(updatedCookies);

        if (!updatedCookies.hasOwnProperty(this.cookieEnabledName)) {
            return;
        }

        if (updatedCookies[this.cookieEnabledName]) {
            this.fireCookieConsentEvent();
            this.loadGoogleTagManager();
            this.startGoogleTagManager();
            return;
        }

        this.removeGoogleTagManager();
        this.removeCookies();
        this.disableEvents();
    }

    removeCookies() {
        const allCookies = document.cookie.split(';');
        const gaCookieRegex = /^(_ga|_gat_UA$|_gid)/;

        allCookies.forEach(cookie => {
            const cookieName = cookie.split('=')[0].trim();
            if (!cookieName.match(gaCookieRegex)) {
                return;
            }

            CookieStorageHelper.removeItem(cookieName);
        });
    }

    disableEvents() {
        this.events.forEach(event => {
            event.disable();
        });
    }

    /**
     * Dynamically load GTM scripts when consent is given
     * @private
     */
    loadGoogleTagManager() {
        if (!window.dtgsGtmConfig || !window.dtgsGtmConfig.containerIds) {
            return;
        }

        // Respect loadGoogleScriptAfterConsent setting
        // If true, only load after consent (which is the case when this method is called)
        // If false, GTM should have been loaded on page load, but load it anyway if not present
        if (window.dtgsGtmConfig.loadGoogleScriptAfterConsent === false) {
            // Check if GTM is already loaded (should be if loadGoogleScriptAfterConsent is false)
            const hasGtmScript = document.querySelector('script[src*="googletagmanager.com/gtm.js"], script[src*="/gtm.js"]');
            if (hasGtmScript) {
                // GTM already loaded on page load, no need to reload
                return;
            }
        }

        // Load GTM for each container ID
        window.dtgsGtmConfig.containerIds.forEach(containerId => {
            if (typeof window.dtgsLoadGTM === 'function') {
                window.dtgsLoadGTM(containerId);
            }
        });
    }

    /**
     * Remove GTM scripts from DOM when consent is revoked
     * @private
     */
    removeGoogleTagManager() {
        if (typeof window.dtgsRemoveGTM === 'function') {
            window.dtgsRemoveGTM();
        }
    }

    /**
     * Added in 6.3.10
     * @param updatedCookies
     */
    updateConsentMode(updatedCookies) {
        if (Object.keys(updatedCookies).length === 0) {
            return;
        }

        //GTM-GH-21: let 3rdparty system handle consent
        if(typeof dtgsConsentHandler !== 'undefined' && dtgsConsentHandler === 'thirdpartyCmp') {
            return;
        }

        const consentUpdateConfig = {};

        if (Object.prototype.hasOwnProperty.call(updatedCookies, this.cookieEnabledName)) {
            consentUpdateConfig['analytics_storage'] = updatedCookies[this.cookieEnabledName] ? 'granted' : 'denied';
        }

        if (Object.prototype.hasOwnProperty.call(updatedCookies, this.cookieAdsEnabledName)) {
            consentUpdateConfig['ad_storage'] = updatedCookies[this.cookieAdsEnabledName] ? 'granted' : 'denied';
            consentUpdateConfig['ad_user_data'] = updatedCookies[this.cookieAdsEnabledName] ? 'granted' : 'denied';
            consentUpdateConfig['ad_personalization'] = updatedCookies[this.cookieAdsEnabledName] ? 'granted' : 'denied';
        }

        if (Object.keys(consentUpdateConfig).length === 0) {
            return;
        }

        gtag('consent', 'update', consentUpdateConfig);
    }

    registerSelectItemEvent() {

        //Select Item Event
        let productLinkElements = DomAccessHelper.querySelectorAll(document, 'a.product-name, a.product-image-link, a.product-button-detail', false);
        if(productLinkElements) {
            productLinkElements.forEach((item) => {
                item.addEventListener('click', this.fireSelectItemEvent);
            });
        }

    }

    onPageSwitched() {

        this.registerSelectItemEvent();

    }


    /**
     * Subscribe to the wishlist storage events.
     *
     * The WishlistStorage plugin is registered as an async (lazy loaded)
     * plugin. On pages where the header is loaded delayed (e.g. the
     * checkout/confirm page) the storage plugin instance is not available
     * yet when this plugin's init() runs. Instead of polling for the plugin
     * instance (which caused a noticeable delay and could miss the events),
     * we attach native listeners directly to the wishlist basket element.
     *
     * The Shopware NativeEventEmitter dispatches its events as DOM
     * CustomEvents on the plugin element (#wishlist-basket). Since that
     * element already exists in the DOM on page load, listeners registered
     * here will catch the events as soon as the storage plugin publishes
     * them, regardless of when the plugin itself is initialized.
     */
    registerWishlistEvents() {

        const wishlistBasketElement = DomAccessHelper.querySelector(document, '#wishlist-basket', false);
        if (!wishlistBasketElement) {
            return;
        }
        
        wishlistBasketElement.addEventListener('Wishlist/onProductAdded', this.onWishlistAdd.bind(this));
        wishlistBasketElement.addEventListener('Wishlist/onProductRemoved', this.onWishlistRemove.bind(this));

    }

    /**
     * added in 6.3.16
     */
    onWishlistAdd(event) {
        let sku = this.getSkuFromEvent(event);
        this.fireWishlistEvent(sku, 'add_to_wishlist');
    }

    /**
     * added in 6.3.16
     */
    onWishlistRemove(event) {
        let sku = this.getSkuFromEvent(event);
        this.fireWishlistEvent(sku, 'remove_from_wishlist');
    }

    /**
     * added in 6.3.18
     * @param event
     */
    onWishlistRemoveFormSubmit(event) {

        let skuField = DomAccessHelper.querySelector(event.target, 'input[name="dtgs-gtm-product-sku"]', false);
        this.fireWishlistEvent(skuField ? skuField.value : undefined, 'remove_from_wishlist');

    }

    /**
     * added in 6.3.16
     */
    fireWishlistEvent(sku, gtm_event_name) {

        if(sku) {

            dataLayer.push({
                'event': gtm_event_name,
                'ecommerce': {
                    'items': {
                        'item_id': sku
                    }
                }
            });

        }

    }

    /**
     * added in 6.3.14
     */
    onOffCanvasOpened() {

        let additionalProperties = LineItemHelper.getAdditionalProperties();
        let lineItems = this.getLineItems();

        window.dataLayer.push({
            'event': 'view_cart',
            'currency': additionalProperties.currency,
            'ecommerce': {
                'items': lineItems
            }
        });

        // store initial quantities
        this.events.forEach(event => {
            if (event.hasOwnProperty("quantityBeforeChange")) {
                event.storeInitialQuantities();
            }
        });

    }

    onOffCanvasOpenedForInitialQuantities() {
        // store initial quantities
        this.events.forEach(event => {
            if (event.hasOwnProperty("quantityBeforeChange")) {
                event.storeInitialQuantities();
            }
        });
    }

    getLineItems() {
        const lineItemsContainer = DomAccessHelper.querySelector(document, '.hidden-line-items-information', false);
        const lineItemDataElements = DomAccessHelper.querySelectorAll(lineItemsContainer, '.hidden-line-item', false);
        const lineItems = [];

        if(lineItemDataElements === false) return [];

        lineItemDataElements.forEach(itemEl => {
            let item = {
                item_id: DomAccessHelper.getDataAttribute(itemEl, 'data-dtgs-sku'),
                item_name: DomAccessHelper.getDataAttribute(itemEl, 'name'),
                quantity: DomAccessHelper.getDataAttribute(itemEl, 'quantity'),
                price: DomAccessHelper.getDataAttribute(itemEl, 'data-dtgs-price'),
            }
            if(DomAccessHelper.getDataAttribute(itemEl, 'data-dtgs-db-id', false) !== undefined) {
                item['item_db_id'] = DomAccessHelper.getDataAttribute(itemEl, 'id');
            }
            lineItems.push(item);
        });

        return lineItems;
    }

    /**
     * added in 6.3.18
     * @param event
     * @returns {*}
     */
    getSkuFromEvent(event) {

        let productId = event.detail.productId;

        // Primary lookup: hidden sku field next to the product id hidden field
        // (used on product listing / detail pages).
        let siblingHiddenField = DomAccessHelper.querySelector(document, 'input[value="' + productId + '"]', false);
        if(siblingHiddenField) {
            let skuField = DomAccessHelper.querySelector(siblingHiddenField.parentNode, 'input[name="dtgs-gtm-product-sku"]', false);
            if(skuField) {
                return skuField.value;
            }
        }

        // Fallback: resolve the sku from the hidden line items, matching the
        // product id via the data-id attribute (e.g. on the checkout/confirm page).
        return this.getSkuFromLineItems(productId);

    }

    /**
     * Resolve the article number (sku) from the hidden line items by matching
     * the given product id against the data-id attribute.
     *
     * @param productId
     * @returns {string|undefined}
     */
    getSkuFromLineItems(productId) {

        const lineItemsContainer = DomAccessHelper.querySelector(document, '.hidden-line-items-information', false);
        if(!lineItemsContainer) return undefined;

        const lineItemDataElements = DomAccessHelper.querySelectorAll(lineItemsContainer, '.hidden-line-item', false);
        if(lineItemDataElements === false) return undefined;

        let sku;
        lineItemDataElements.forEach(itemEl => {
            if(DomAccessHelper.getDataAttribute(itemEl, 'data-id', false) == productId) {
                sku = DomAccessHelper.getDataAttribute(itemEl, 'data-dtgs-sku', false);
            }
        });

        return sku;

    }
}
