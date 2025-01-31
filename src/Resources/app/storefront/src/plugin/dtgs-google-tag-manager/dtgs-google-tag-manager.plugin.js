import Plugin from 'src/plugin-system/plugin.class';
import { COOKIE_CONFIGURATION_UPDATE } from 'src/plugin/cookie/cookie-configuration.plugin';

import GtmAddToCartEvent from './events/add-to-cart.event';
import GtmRemoveFromCartEvent from './events/remove-from-cart.event';
import CookieStorageHelper from 'src/helper/storage/cookie-storage.helper';

export default class DtgsGoogleTagManagerPlugin extends Plugin
{
    init() {
        this.cookieEnabledName = 'dtgsAllowGtmTracking';

        this.handleCookieChangeEvent();

        this.startGoogleTagManager();

        if (!CookieStorageHelper.getItem(this.cookieEnabledName)) {
            return;
        }

        this.fireCookieConsentEvent();
    }

    fireCookieConsentEvent() {

        window.dataLayer.push({
            'event': 'cookieConsentGiven'
        });

    }

    fireSelectItemEvent(event) {

        let productBox = event.target.closest('.card-body');
        let itemName = productBox.querySelector('input[name="product-name"]').value;
        let orderNumber = productBox.querySelector('input[name="dtgs-gtm-product-sku"]').value;

        // Clear the previous ecommerce object
        dataLayer.push({ ecommerce: null });
        dataLayer.push({
            'event': 'select_item',
            'ecommerce': {
                'item_list_name': 'Category',
                'items': [
                    {
                        'item_id': orderNumber,
                        'item_name': itemName,
                    }
                ]
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

        //Select Item Event
        let productLinkElements = document.querySelectorAll('a.product-name, a.product-image-link, a.product-button-detail');
        productLinkElements.forEach((item) => {
            item.addEventListener('click', this.fireSelectItemEvent);
        });
    }

    registerEvent(event) {
        this.events.push(new event());
    }

    handleCookieChangeEvent() {
        document.$emitter.subscribe(COOKIE_CONFIGURATION_UPDATE, this.handleCookies.bind(this));
    }

    handleCookies(cookieUpdateEvent) {
        const updatedCookies = cookieUpdateEvent.detail;

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
}
