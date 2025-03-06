import Plugin from 'src/plugin-system/plugin.class';
import { COOKIE_CONFIGURATION_UPDATE } from 'src/plugin/cookie/cookie-configuration.plugin';

import GtmAddToCartEvent from './events/add-to-cart.event';
import GtmRemoveFromCartEvent from './events/remove-from-cart.event';
import CookieStorageHelper from 'src/helper/storage/cookie-storage.helper';
import LineItemHelper from 'src/plugin/google-analytics/line-item.helper';
import DomAccessHelper from 'src/helper/dom-access.helper';

export default class DtgsGoogleTagManagerPlugin extends Plugin
{
    init() {
        this.cookieEnabledName = 'dtgsAllowGtmTracking';
        this.cookieAdsEnabledName = 'google-ads-enabled';

        this.handleCookieChangeEvent();

        this.startGoogleTagManager();

        if (!CookieStorageHelper.getItem(this.cookieEnabledName)) {
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
        const wishlistBasketElement = DomAccessHelper.querySelector(document, '#wishlist-basket', false);
        if (wishlistBasketElement) {
            const wishlistPlugin = window.PluginManager.getPluginInstanceFromElement(wishlistBasketElement, 'WishlistStorage');
            if(wishlistPlugin) wishlistPlugin.$emitter.subscribe('Wishlist/onProductAdded', this.onWishlistAdd.bind(this));
            if(wishlistPlugin) wishlistPlugin.$emitter.subscribe('Wishlist/onProductRemoved', this.onWishlistRemove.bind(this));
        }
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
            //this.startGoogleTagManager();
            return;
        }

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
     * Added in 6.3.10
     * @param updatedCookies
     */
    updateConsentMode(updatedCookies) {
        if (Object.keys(updatedCookies).length === 0) {
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
     * added in 6.3.16
     */
    onWishlistAdd(event) {
        let skuField = this.getSkuFromEvent(event);
        this.fireWishlistEvent(skuField, 'add_to_wishlist');
    }

    /**
     * added in 6.3.16
     */
    onWishlistRemove(event) {
        let skuField = this.getSkuFromEvent(event);
        this.fireWishlistEvent(skuField, 'remove_from_wishlist');
    }

    /**
     * added in 6.3.18
     * @param event
     */
    onWishlistRemoveFormSubmit(event) {

        let skuField = DomAccessHelper.querySelector(event.target, 'input[name="dtgs-gtm-product-sku"]', false);
        this.fireWishlistEvent(skuField, 'remove_from_wishlist');

    }

    /**
     * added in 6.3.16
     */
    fireWishlistEvent(skuField, gtm_event_name) {

        if(skuField !== null) {

            dataLayer.push({
                'event': gtm_event_name,
                'ecommerce': {
                    'items': {
                        'item_id': skuField.value
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

    }

    getLineItems() {
        const lineItemsContainer = DomAccessHelper.querySelector(document, '.hidden-line-items-information', false);
        const lineItemDataElements = DomAccessHelper.querySelectorAll(lineItemsContainer, '.hidden-line-item', false);
        const lineItems = [];

        lineItemDataElements.forEach(itemEl => {
            lineItems.push({
                item_id: DomAccessHelper.getDataAttribute(itemEl, 'data-dtgs-sku'),
                item_name: DomAccessHelper.getDataAttribute(itemEl, 'name'),
                quantity: DomAccessHelper.getDataAttribute(itemEl, 'quantity'),
                price: DomAccessHelper.getDataAttribute(itemEl, 'data-dtgs-price'),
                //added in 6.3.19 - this is always here, regardless of config setting
                //maybe optimize in the future
                item_db_id: DomAccessHelper.getDataAttribute(itemEl, 'id'),
            });
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
        let siblingHiddenField = DomAccessHelper.querySelector(document, 'input[value="' + productId + '"]', false);
        if(siblingHiddenField) {
            return DomAccessHelper.querySelector(siblingHiddenField.parentNode, 'input[name="dtgs-gtm-product-sku"]', false);
        }

    }
}
