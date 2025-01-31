import AnalyticsEvent from 'src/plugin/google-analytics/analytics-event';
import DomAccessHelper from 'src/helper/dom-access.helper';

export default class GtmRemoveFromCart extends AnalyticsEvent
{
    supports() {
        return true;
    }

    execute() {
        document.addEventListener('click', this._onRemoveFromCart.bind(this));
    }

    _onRemoveFromCart(event) {
        if (!this.active) {
            return;
        }

        const closest = event.target.closest('.line-item-remove-button');
        if (!closest) {
            return;
        }

        const hiddenInput = DomAccessHelper.querySelector(event.target.closest('.line-item-remove'), 'input[name=dtgs-gtm-product-sku]');
        if (!hiddenInput) {
            return;
        }

        const itemName = DomAccessHelper.querySelector(event.target.closest('.line-item-remove'), 'input[name=dtgs-gtm-product-name]');
        const productPrice = DomAccessHelper.querySelector(event.target.closest('.line-item-remove'), 'input[name=dtgs-gtm-product-price]');
        const totalPrice = DomAccessHelper.querySelector(event.target.closest('.line-item-remove'), 'input[name=dtgs-gtm-total-price]');
        const quantity = DomAccessHelper.querySelector(event.target.closest('.line-item-remove'), 'input[name=dtgs-gtm-product-quantity]');
        const currencyCode = DomAccessHelper.querySelector(event.target.closest('.line-item-remove'), 'input[name=dtgs-gtm-currency-code]');

        // Clear the previous ecommerce object
        dataLayer.push({ ecommerce: null });
        dataLayer.push({
            'event': 'remove_from_cart',
            'currency': currencyCode.value,
            'value': Number(totalPrice.value),
            'ecommerce': {
                'items': [{
                    'item_id': hiddenInput.value,
                    'item_name': itemName.value,
                    'quantity': Number(quantity.value),
                    'price': Number(productPrice.value),
                    'index': 0
                }]
            }
        });
    }
}
