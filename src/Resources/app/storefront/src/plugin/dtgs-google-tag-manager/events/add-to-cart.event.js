import EventAwareAnalyticsEvent from 'src/plugin/google-analytics/event-aware-analytics-event';

export default class GtmAddToCartEvent extends EventAwareAnalyticsEvent
{
    supports() {
        return true;
    }

    getPluginName() {
        return 'AddToCart';
    }

    getEvents() {
        return {
            'beforeFormSubmit':  this._beforeFormSubmit.bind(this)
        };
    }

    _beforeFormSubmit(event) {
        if (!this.active) {
            return;
        }

        const formData = event.detail;
        let productId = null;

        formData.forEach((value, key) => {
            if (key.endsWith('[id]')) {
                productId = value;
            }
        });

        if (!productId) {
            console.warn('[codiverse GTM] Product ID could not be fetched. Skipping.');
            return;
        }

        let products = this.getProductsObjectFromFormData(formData, productId);

        let event_name = 'add_to_cart';
        //added in 6.2.1
        const body = document.querySelector("body");
        if(body.classList.contains("is-ctl-navigation")) event_name = 'add_to_cart_list';

        // Clear the previous ecommerce object
        dataLayer.push({ ecommerce: null });
        dataLayer.push({
            'event': event_name,
            'ecommerce': {
                'currency': formData.get('dtgs-gtm-currency-code'),
                'value': products.price * products.quantity,
                'items': [products]
            }
        });
    }

    getProductsObjectFromFormData(formData, productId) {

        //Product Array
        let products = {
            'item_name': formData.get('product-name'),
            'item_id': formData.get('dtgs-gtm-product-sku'),
            'quantity': Number(formData.get('lineItems[' + productId + '][quantity]'))
        };

        //Price, Brand Name & Kategorie optional
        if(formData.get('dtgs-gtm-product-variantname') !== null) Object.assign(products, {'item_variant': formData.get('dtgs-gtm-product-variantname')});
        if(formData.get('dtgs-gtm-product-category') !== null) Object.assign(products, {'item_category': formData.get('dtgs-gtm-product-category')});
        if(formData.get('dtgs-gtm-product-price') !== null) Object.assign(products, {'price': Number(formData.get('dtgs-gtm-product-price'))});
        if(formData.get('brand-name') !== null) Object.assign(products, {'item_brand': formData.get('brand-name')});

        return products;

    }
}
