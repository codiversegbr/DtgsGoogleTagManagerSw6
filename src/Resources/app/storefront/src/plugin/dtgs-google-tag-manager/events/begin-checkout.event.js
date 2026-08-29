import AnalyticsEvent from 'src/plugin/google-analytics/analytics-event';
import DomAccessHelper from 'src/helper/dom-access.helper';
import LineItemHelper from 'src/plugin/google-analytics/line-item.helper';

export default class GtmBeginCheckoutEvent extends AnalyticsEvent
{
    supports() {
        return true;
    }

    execute() {
        document.addEventListener('click', this._onBeginCheckout.bind(this));
    }

    _onBeginCheckout(event) {
        if (!this.active) {
            return;
        }

        const checkoutButton = event.target.closest('.begin-checkout-btn');
        if (!checkoutButton) {
            return;
        }

        const lineItemsContainer = DomAccessHelper.querySelector(document, '.hidden-line-items-information', false);
        if (!lineItemsContainer) {
            return;
        }

        let additionalProperties = LineItemHelper.getAdditionalProperties();
        let lineItems = this.getLineItems();

        // Clear the previous ecommerce object
        window.dataLayer = window.dataLayer || [];
        window.dataLayer.push({ ecommerce: null });
        window.dataLayer.push({
            'event': 'begin_checkout',
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

        if (lineItemDataElements === false) return [];

        lineItemDataElements.forEach(itemEl => {
            let item = {
                item_id: DomAccessHelper.getDataAttribute(itemEl, 'data-dtgs-sku'),
                item_name: DomAccessHelper.getDataAttribute(itemEl, 'name'),
                quantity: DomAccessHelper.getDataAttribute(itemEl, 'quantity'),
                price: DomAccessHelper.getDataAttribute(itemEl, 'data-dtgs-price'),
            };
            if (DomAccessHelper.getDataAttribute(itemEl, 'data-dtgs-db-id', false) !== undefined) {
                item['item_db_id'] = DomAccessHelper.getDataAttribute(itemEl, 'id');
            }
            lineItems.push(item);
        });

        return lineItems;
    }
}
