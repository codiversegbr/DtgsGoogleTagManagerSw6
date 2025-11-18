import Plugin from 'src/plugin-system/plugin.class';
import CookieStorageHelper from 'src/helper/storage/cookie-storage.helper';
import { COOKIE_CONFIGURATION_UPDATE } from 'src/plugin/cookie/cookie-configuration.plugin';

export default class DtgsGtmServicesPlugin extends Plugin {
    init() {
        this.gtmConsentCookie = window.dtgsGtmConsentCookieName || 'dtgsAllowGtmTracking';
        this.services = Array.isArray(window.dtgsGtmCustomServices) ? window.dtgsGtmCustomServices : [];

        this.handleCookieChangeEvent();

        // On page load: if consent already given, fire for all consented services
        this.fireForConsentedServices();
    }

    handleCookieChangeEvent() {
        document.$emitter.subscribe(COOKIE_CONFIGURATION_UPDATE, this.handleCookies.bind(this));
    }

    handleCookies(cookieUpdateEvent) {
        const updatedCookies = cookieUpdateEvent.detail;

        // If GTM consent is not part of update and not granted, do nothing
        if (!this.isGtmConsentGranted(updatedCookies)) {
            return;
        }

        // Fire events for any services whose cookies have just been granted
        this.services.forEach((svc) => {
            const cookieName = svc.cookie;
            if (updatedCookies.hasOwnProperty(cookieName) && updatedCookies[cookieName]) {
                this.pushEvent(svc.eventName);
            }
        });
    }

    fireForConsentedServices() {
        if (!CookieStorageHelper.getItem(this.gtmConsentCookie)) {
            return;
        }

        this.services.forEach((svc) => {
            if (CookieStorageHelper.getItem(svc.cookie)) {
                this.pushEvent(svc.eventName);
            }
        });
    }

    isGtmConsentGranted(updatedCookies) {
        if (updatedCookies && Object.prototype.hasOwnProperty.call(updatedCookies, this.gtmConsentCookie)) {
            return !!updatedCookies[this.gtmConsentCookie];
        }
        // Fallback to cookie storage
        return !!CookieStorageHelper.getItem(this.gtmConsentCookie);
    }

    pushEvent(eventName) {
        if (typeof window.dataLayer === 'undefined') {
            window.dataLayer = [];
        }
        window.dataLayer.push({ event: eventName });
    }
}
