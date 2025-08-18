import AnalyticsEvent from 'src/plugin/google-analytics/analytics-event';
import DomAccessHelper from 'src/helper/dom-access.helper';

export default class GtmQuantityChangeEvent extends AnalyticsEvent
{
    constructor() {
        super();
        this.quantityBeforeChange = new Map();
    }

    supports() {
        return true;
    }

    execute() {
        // Listen for change events that core QuantitySelectorPlugin dispatches
        document.addEventListener('change', this._onQuantityInputChange.bind(this));

        // Store initial quantities when the page loads
        this.storeInitialQuantities();
    }

    storeInitialQuantities() {
        this._refreshStoredQuantities();
    }

    _refreshStoredQuantities() {
        // Find all quantity inputs that use the core QuantitySelectorPlugin
        const quantityInputs = DomAccessHelper.querySelectorAll(document, 'input[name="quantity"].js-quantity-selector', false);
        if (quantityInputs) {
            quantityInputs.forEach(input => {
                const lineItemId = this._getLineItemId(input);
                if (lineItemId) {
                    const currentQuantity = parseInt(input.value) || 1;
                    this.quantityBeforeChange.set(lineItemId, currentQuantity);
                }
            });
        }
    }

    _onQuantityInputChange(event) {
        if (!this.active) {
            return;
        }

        // Check if this is a quantity selector input
        if (!event.target.classList.contains('js-quantity-selector') || event.target.name !== 'quantity') {
            return;
        }

        const quantityInput = event.target;
        const lineItemId = this._getLineItemId(quantityInput);

        if (!lineItemId) {
            return;
        }

        const newQuantity = parseInt(quantityInput.value) || 1;
        const currentQuantity = this.quantityBeforeChange.get(lineItemId) || 1;

        // Fire the GTM event
        this._fireQuantityChangeEvent(quantityInput, currentQuantity, newQuantity);

        // Update stored quantity
        this.quantityBeforeChange.set(lineItemId, newQuantity);
    }

    _getLineItemId(quantityInput) {
        const lineItem = quantityInput.closest('.line-item');
        if (!lineItem) {
            return null;
        }

        // Get the product SKU from the remove form (most reliable method)
        const removeForm = DomAccessHelper.querySelector(lineItem, '.line-item-remove form', false);
        if (removeForm) {
            const productSku = DomAccessHelper.querySelector(removeForm, 'input[name="dtgs-gtm-product-sku"]', false);
            if (productSku && productSku.value) {
                return productSku.value;
            }
        }

        // Fallback: use line item position if SKU is not found
        return this._getFallbackLineItemId(lineItem);
    }

    _getFallbackLineItemId(lineItem) {
        const allLineItems = DomAccessHelper.querySelectorAll(document, '.line-item', false);
        if (allLineItems) {
            const index = Array.from(allLineItems).indexOf(lineItem);
            return `line-item-${index}`;
        }
        return null;
    }

    _fireQuantityChangeEvent(quantityInput, currentQuantity, newQuantity) {
        // Only proceed if the quantity actually changed
        if (currentQuantity === newQuantity) {
            return;
        }

        const lineItemContainer = quantityInput.closest('.line-item');
        if (!lineItemContainer) {
            return;
        }

        // Determine event type and quantity difference
        const quantityDiff = newQuantity - currentQuantity;
        const isIncrease = quantityDiff > 0;
        const quantityChange = Math.abs(quantityDiff);

        // Extract common form data once
        const removeForm = DomAccessHelper.querySelector(lineItemContainer, '.line-item-remove form', false);
        if (!removeForm) {
            console.warn('[GTM Quantity Change] Could not find remove form');
            return;
        }

        const commonFormData = this._extractCommonFormData(removeForm);
        if (!commonFormData) {
            return;
        }

        // Build event data based on increase/decrease
        const eventData = isIncrease 
            ? this._buildIncreaseEventData(commonFormData, quantityChange)
            : this._buildDecreaseEventData(commonFormData, quantityChange);

        // Push to dataLayer
        this._pushToDataLayer(isIncrease ? 'add_to_cart' : 'remove_from_cart', eventData);
    }

    _extractCommonFormData(removeForm) {
        const productSku = DomAccessHelper.querySelector(removeForm, 'input[name="dtgs-gtm-product-sku"]', false);
        const productName = DomAccessHelper.querySelector(removeForm, 'input[name="dtgs-gtm-product-name"]', false);
        const productPrice = DomAccessHelper.querySelector(removeForm, 'input[name="dtgs-gtm-product-price"]', false);
        const currencyCode = DomAccessHelper.querySelector(removeForm, 'input[name="dtgs-gtm-currency-code"]', false);

        if (!productSku || !productName || !currencyCode) {
            console.warn('[GTM Quantity Change] Missing required form data');
            return null;
        }

        return {
            sku: productSku.value,
            name: productName.value,
            price: productPrice ? Number(productPrice.value) : 0,
            currency: currencyCode.value,
            removeForm // Keep reference for additional fields
        };
    }

    _buildIncreaseEventData(commonFormData, quantityChange) {
        // Create item data like add-to-cart but only with available fields
        const itemData = {
            'item_name': commonFormData.name,
            'item_id': commonFormData.sku,
            'quantity': quantityChange
        };

        // Add optional fields
        this._addOptionalFields(itemData, commonFormData, commonFormData.removeForm);

        // Add remarketing fields if enabled
        this._addRemarketingFields(itemData, commonFormData.sku);

        return {
            currency: commonFormData.currency,
            value: commonFormData.price * quantityChange,
            items: [itemData]
        };
    }

    _buildDecreaseEventData(commonFormData, quantityChange) {
        // Create item data exactly like remove-from-cart
        const itemData = {
            'item_id': commonFormData.sku,
            'item_name': commonFormData.name,
            'quantity': quantityChange,
            'price': commonFormData.price,
            'index': 0
        };

        return {
            currency: commonFormData.currency,
            value: commonFormData.price * quantityChange,
            items: [itemData]
        };
    }

    _addOptionalFields(itemData, commonFormData, removeForm) {
        // Add price if available
        if (commonFormData.price > 0) {
            itemData.price = commonFormData.price;
        }

        // Add database ID if available
        const dbId = DomAccessHelper.querySelector(removeForm, 'input[name="dtgs-gtm-product-db-id"]', false);
        if (dbId && dbId.value && dbId.value !== '') {
            itemData.item_db_id = dbId.value;
        }
    }

    _addRemarketingFields(itemData, sku) {
        if (typeof dtgsRemarketingEnabled !== 'undefined' && dtgsRemarketingEnabled === true) {
            itemData.id = sku;
            itemData.google_business_vertical = 'retail';
        }
    }

    _pushToDataLayer(eventType, ecommerceData) {
        // Clear the previous ecommerce object
        dataLayer.push({ ecommerce: null });

        // Push the event
        dataLayer.push({
            event: eventType,
            ecommerce: ecommerceData
        });
    }
}
