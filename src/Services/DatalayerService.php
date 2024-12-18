<?php declare(strict_types=1);

namespace Dtgs\GoogleTagManager\Services;

use Dtgs\GoogleTagManager\Components\Helper\CategoryHelper;
use Dtgs\GoogleTagManager\Components\Helper\LoggingHelper;
use Dtgs\GoogleTagManager\Components\Helper\PriceHelper;
use Dtgs\GoogleTagManager\Components\Helper\ProductHelper;
use Exception;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTax;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Content\Category\CategoryCollection;
use Shopware\Core\Content\Product\SalesChannel\Listing\ProductListingResult;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\InvalidCriteriaIdsException;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Uuid\Exception\InvalidUuidException;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\HttpFoundation\Request;

class DatalayerService
{

    private $systemConfigService;
    private $priceHelper;
    private $loggingHelper;
    private $productHelper;
    /**
     * @var CategoryHelper
     */
    private $categoryHelper;

    public function __construct(SystemConfigService $systemConfigService,
                                CategoryHelper $categoryHelper,
                                PriceHelper $priceHelper,
                                ProductHelper $productHelper,
                                LoggingHelper $loggingHelper)
    {
        $this->systemConfigService = $systemConfigService;
        $this->categoryHelper = $categoryHelper;
        $this->priceHelper = $priceHelper;
        $this->loggingHelper = $loggingHelper;
        $this->productHelper = $productHelper;
    }

    /**
     * Maybe move to general helper
     *
     * Helper to get plugin specific config
     *
     * @return array|mixed|null
     */
    public function getGtmConfig($salesChannelId) {
        return $tagManagerConfig = $this->systemConfigService->get('DtgsGoogleTagManagerSw6.config', $salesChannelId);
    }

    /**
     * Maybe move to general helper
     *
     * @param array $generalTags
     * @param array $navigationTags
     * @param array $accountTags
     * @param array $detailTags
     * @param array $checkoutTags
     * @param array $customerTags
     * @param array $utmTags
     * @param array $searchTags
     * @return false|string
     */
    public function prepareTagsForView(
        array $generalTags,
        array $navigationTags,
        array $accountTags,
        array $detailTags,
        array $checkoutTags,
        array $customerTags,
        array $utmTags,
        array $searchTags
        )
    {
        $return = array_merge(
            $generalTags,
            $navigationTags,
            $accountTags,
            $detailTags,
            $checkoutTags,
            $customerTags,
            $utmTags,
            $searchTags
        );

        return json_encode($return);
    }

    /**
     * SW6 ready
     *
     * since V2.5.0
     * multiple Tag Manager Container IDs
     * @return array|bool
     */
    public function getContainerIds($salesChannelId) {

        $tagManagerConfig = $this->getGtmConfig($salesChannelId);

        if(isset($tagManagerConfig['googleId']) && $tagManagerConfig['googleId'] != '') {
            $ids = array_map('trim', explode(',', $tagManagerConfig['googleId']));
            return $ids;
        }

        return false;

    }

    /**
     * SW6 ready
     *
     * @param SalesChannelProductEntity $product
     * @param SalesChannelContext $context
     * @return array
     * @throws Exception
     */
	public function getDetailTags(SalesChannelProductEntity $product, SalesChannelContext $context) {

		$detailTags = [];

	    if($product->getId()) {
			//New in 1.3.5 - select if brutto/netto
            $price = ($product->getCalculatedPrices()->count()) ? $product->getCalculatedPrices()->first()->getUnitPrice() : $product->getCalculatedPrice()->getUnitPrice();
            $brutto_price = (is_float($price)) ? $price : str_replace(',', '.', $price);

            $taxRate = $product->getCalculatedPrice()->getTaxRules()->first();
            if($taxRate) {
                $tax = $taxRate->getTaxRate();
            }
            else {
                //Bugfix for tax free countries, V6.1.4
                $tax = 0;
            }

            $detailTags['productID'] = $product->getId();
            $detailTags['parentProductID'] = $product->getParentId();
            $detailTags['productName'] = $product->getTranslation('name');
            $detailTags['productPrice'] = $this->priceHelper->getPrice($brutto_price, $tax, $context);
            $detailTags['productEAN'] = $product->getEan() ?? '';
            $detailTags['productSku'] = $product->getProductNumber();
            //since V6.1.45
            $detailTags['productManufacturerNumber'] = $product->getManufacturerNumber() ?? '';

            //Product Category - Changed to SEO Category in V6.1.22
            $seoCategory = $product->getSeoCategory();
            if($seoCategory) {
                $detailTags['productCategory'] = $seoCategory->getTranslation('name');
                $detailTags['productCategoryID'] = $seoCategory->getId();
            }

		}
        $detailTags['productCurrency'] = $context->getCurrency()->getIsoCode();

        //Since 2.2.3
        if($this->loggingHelper->loggingType('debug')) $this->loggingHelper->logMsg('Detail-Tags: ' . json_encode($detailTags));

	    return $detailTags;

	}

    /**
     * SW6 ready
     *
     * @param $navigationId
     * @param SalesChannelContext $context
     * @return array
     */
    public function getNavigationTags($navigationId, SalesChannelContext $context): array
    {
        //no explicit navigation Tags so far
        $tags = [];

        $category = $this->categoryHelper->getCategoryById($navigationId, $context);

        $tags['pageCategoryID'] = $category->getId();

        if($this->loggingHelper->loggingType('debug')) $this->loggingHelper->logMsg('Navigation-Tags: ' . json_encode($tags));

        return $tags;
    }

    /**
     * SW6 ready
     *
     * @return array
     */
    public function getAccountTags()
    {
        //no explicit account Tags so far
        $tags = [];

        if($this->loggingHelper->loggingType('debug')) $this->loggingHelper->logMsg('Account-Tags: ' . json_encode($tags));

        return $tags;
    }

    /**
     * SW6 ready
     *
     * @param $cartOrOrder (either Cart or Order)
     * @param SalesChannelContext $context
     * @param bool $isFinish
     * @return array
     * @throws Exception
     */
	public function getCheckoutTags($cartOrOrder, SalesChannelContext $context, $isFinish = false) {

        $pluginConfig = $this->getGtmConfig($context->getSalesChannel()->getId());
        $useNetPrices = isset($pluginConfig['showPriceType']) && $pluginConfig['showPriceType'] == 'netto';

        $checkoutTags = [];

		//Conversion Data
        $checkoutTags['conversionDate'] = date('Ymd');

		//New in 1.3.5 - select if brutto/netto
		if($useNetPrices) $checkoutTags['conversionValue'] = $cartOrOrder->getPrice()->getNetPrice();
		else $checkoutTags['conversionValue'] = $cartOrOrder->getPrice()->getTotalPrice();

        $checkoutTags['conversionType'] = ''; //???
        $checkoutTags['conversionId'] = 'null';
        $checkoutTags['conversionAttributes'] = ''; //tbd

		//Transaction Data
        $checkoutTags['transactionId'] = 'null';
        $checkoutTags['transactionDate'] = date('Ymd');
        $checkoutTags['transactionType'] = ''; //???
        $checkoutTags['transactionAffiliation'] = $context->getSalesChannel()->getName(); //Shopname

		//New in 1.3.5 - select if brutto/netto
		if($useNetPrices) $checkoutTags['transactionTotal'] = $cartOrOrder->getPrice()->getNetPrice();
		else $checkoutTags['transactionTotal'] = $cartOrOrder->getPrice()->getTotalPrice();

		$taxRate = $cartOrOrder->getPrice()->getCalculatedTaxes()->first();
		if($taxRate) {
            /** @var $taxRate CalculatedTax */
		    $checkoutTags['transactionTax'] = (float)$this->priceHelper->formatPrice($taxRate->getTax());
        }
		else {
            //Bugfix for tax free countries, V6.1.4
		    $checkoutTags['transactionTax'] = 0;
        }


		//New in 1.3.5 - select if brutto/netto
		if($useNetPrices) {
            $taxRate = $cartOrOrder->getShippingCosts()->getTaxRules()->first();
            if($taxRate) {
                $tax = $taxRate->getTaxRate();
            }
            else {
                //Bugfix for tax free countries, V6.1.10
                $tax = 0;
            }
		    $checkoutTags['transactionShipping'] = $this->priceHelper->parseFloat($this->priceHelper->getPrice($cartOrOrder->getShippingCosts()->getTotalPrice(), $tax, $context));
        }
		else $checkoutTags['transactionShipping'] = $this->priceHelper->parseFloat($cartOrOrder->getShippingCosts()->getTotalPrice());

        $checkoutTags['transactionPaymentType'] = $context->getPaymentMethod()->getTranslation('name');
        $checkoutTags['transactionCurrency'] = $context->getCurrency()->getIsoCode();

        //V6.1.5
        $deliveryType = $cartOrOrder->getDeliveries()->first();
        if($deliveryType) {
            $checkoutTags['transactionShippingMethod'] = ($cartOrOrder->getDeliveries()->first()->getShippingMethod()) ? $cartOrOrder->getDeliveries()->first()->getShippingMethod()->getName() : 'none';
        }
        else {
            $checkoutTags['transactionShippingMethod'] = 'none';
        }

        $checkoutTags['transactionProducts'] = array();

		//Transaction Product Data
		foreach($cartOrOrder->getLineItems() as $item) {
            if (isset($item->getPayload()['promotionId'])) {
                $voucher = $item->getPayload();
                if(isset($voucher['code'])) {
                    $checkoutTags['transactionPromoCode'] = $voucher['code'];
                }
            } else {
                /** @var LineItem $item */
                $taxRate = $item->getPrice()->getTaxRules()->first();
                if($taxRate) {
                    $tax = $taxRate->getTaxRate();
                }
                else {
                    //Bugfix for tax free countries, V6.1.4
                    $tax = 0;
                }

                $productName = $item->getLabel();
                $productNumber = $item->getPayload()['productNumber'] ?? 'none';

                //Anpassung für Swag Custom Products - V6.1.29
                if($item->getType() == 'customized-products' && $item->getChildren()) {
                    foreach($item->getChildren() as $child) {
                        if($child->getType() == 'product') {
                            $productName = $child->getLabel();
                            $childPayload = $child->getPayload();
                            $productNumber = (isset($childPayload['productNumber'])) ? $childPayload['productNumber'] : 'customized-product';
                        }
                    }
                }
                if($item->getType() == 'customized-products-option') {
                    $productNumber = 'customized-product-option-' . strtolower($item->getLabel());
                }
                if(($item->getType() == 'option-values' || $item->getType() == 'customized-products') && $isFinish) {
                    continue;
                }

                $product = null;
                try {
                    if (method_exists($item, 'getProductId')) {
                        $product = $this->productHelper->getProductyById($item->getProductId(), $context);
                    } elseif (method_exists($item, 'getId')) {
                        $product = $this->productHelper->getProductyById($item->getId(), $context);
                    }
                } catch (InvalidUuidException | InvalidCriteriaIdsException $exception) {
                    //CDVRS-16
                    //Hier haben wir es mit Sonderproduktion zu tun, zB Pfand.
                    //Hat das Produkt keine UUID, müssen wir hier die Exception abfangen.
                    //CDVRS-15
                    //Custom Products werden als eigene Items im WK gehandlet, haben aber keine ID.
                    //Damit die Optionen im WK erhalten bleiben (können ja auch einen Preis haben),
                    //wird hier die Exception gefangen und keine gesonderte Behandlung vorgenommen.
                }
                $checkoutTags['transactionProducts'][] = array(
                    'id' => $item->getId(),
                    'parent_id' => ($product !== null) ? $product->getParentId() : '',
                    'name' => $productName,
                    'sku' => $productNumber,
                    //'category' => '', //nicht vorhanden im Array
                    'price' => $this->priceHelper->getPrice($item->getPrice()->getUnitPrice(), $tax, $context),
                    'quantity' => $item->getQuantity(),
                );
            }
		}

        //Since 2.2.3
		if($this->loggingHelper->loggingType('debug')) $this->loggingHelper->logMsg('Checkout-Tags: ' . json_encode($checkoutTags));

		return $checkoutTags;

	}

    /**
     * SW6 ready
     *
     * @param OrderEntity $order
     * @param SalesChannelContext $context
     * @param Request $request
     * @return array
     * @throws Exception
     */
	public function getFinishTags(OrderEntity $order, SalesChannelContext $context) {

        $pluginConfig = $this->getGtmConfig($context->getSalesChannel()->getId());
	    $checkoutTags = $this->getCheckoutTags($order, $context, true);
        $finishTags = [];

        $checkoutTags['conversionId'] = $order->getOrderNumber();
        $checkoutTags['transactionId'] = $order->getOrderNumber();

        if(isset($pluginConfig['eeEnhancedConversions'])) {
            $ee_ec_setting = $pluginConfig['eeEnhancedConversions'];
            $ee_ec_hash_setting = $pluginConfig['eeEnhancedConversionHashing'] ?? false;
            $hashAlgorithm = 'sha256';

            if($ee_ec_setting == 'mail' || $ee_ec_setting == 'full') {
                //since 6.1.34
                $checkoutTags['transactionEmail'] = ($ee_ec_hash_setting) ?
                    self::normalizeAndHashEmailAddress($hashAlgorithm, $order->getOrderCustomer()->getEmail()) : $order->getOrderCustomer()->getEmail();
            }
            if($ee_ec_setting == 'full') {
                $checkoutTags['transactionFirstname'] = ($ee_ec_hash_setting) ?
                    self::normalizeAndHash($hashAlgorithm, $order->getOrderCustomer()->getFirstName(), false) : $order->getOrderCustomer()->getFirstName();
                $checkoutTags['transactionLastname'] = ($ee_ec_hash_setting) ?
                    self::normalizeAndHash($hashAlgorithm, $order->getOrderCustomer()->getLastName(), false) : $order->getOrderCustomer()->getLastName();
                $checkoutTags['transactionStreet'] = ($ee_ec_hash_setting) ?
                    self::normalizeAndHash($hashAlgorithm, $order->getBillingAddress()->getStreet(), false) : $order->getBillingAddress()->getStreet();
                if(!is_null($order->getBillingAddress()->getPhoneNumber()) && $order->getBillingAddress()->getPhoneNumber() != '') {
                    $checkoutTags['transactionPhone'] = ($ee_ec_hash_setting) ?
                        self::normalizeAndHash($hashAlgorithm, $order->getBillingAddress()->getPhoneNumber(), true) : $order->getBillingAddress()->getPhoneNumber();
                }

                $checkoutTags['transactionCity'] = $order->getBillingAddress()->getCity();
                $checkoutTags['transactionZipcode'] = $order->getBillingAddress()->getZipcode();
                $checkoutTags['transactionStateID'] = $order->getBillingAddress()->getCountryStateId();
                $checkoutTags['transactionCountryID'] = $order->getBillingAddress()->getCountryId();
                //since 6.1.44
                $transactionCountry = $order->getBillingAddress()->getCountry();
                if($transactionCountry !== null) {
                    $checkoutTags['transactionCountryIso'] = $transactionCountry->getIso();
                }
                $transactionCountryState = $order->getBillingAddress()->getCountryState();
                if($transactionCountryState !== null) {
                    $checkoutTags['transactionStateName'] = $transactionCountryState->getName();
                }
            }
        }

        $tags = array_merge(
            $checkoutTags,
            $finishTags
        );

        //Since 2.2.3
        if($this->loggingHelper->loggingType('debug')) $this->loggingHelper->logMsg('Checkout-Tags: ' . json_encode($tags));

        return $tags;

    }

    /**
     * SW6 ready
     *
     * @param $searchTerm
     * @param ProductListingResult $listing
     * @return array
     */
	public function getSearchTags($searchTerm, ProductListingResult $listing) {

        $tags = array();

	    $tags['siteSearchTerm'] = $searchTerm;
		$tags['siteSearchFrom'] = '';
		$tags['siteSearchCategory'] = '';
		$tags['siteSearchResults'] = $listing->getTotal();

		return $tags;

	}

    /**
     * Die folgenden beiden Funktionen stammen von:
     * https://developers.google.com/google-ads/api/docs/conversions/enhanced-conversions/web?hl=de#php
     *
     * @param string $hashAlgorithm
     * @param string $value
     * @param bool $trimIntermediateSpaces
     * @return string
     */
    private static function normalizeAndHash(
        string $hashAlgorithm,
        string $value,
        bool $trimIntermediateSpaces
    ): string {
        // Normalizes by first converting all characters to lowercase, then trimming spaces.
        $normalized = strtolower($value);
        if ($trimIntermediateSpaces === true) {
            // Removes leading, trailing, and intermediate spaces.
            $normalized = str_replace(' ', '', $normalized);
        } else {
            // Removes only leading and trailing spaces.
            $normalized = trim($normalized);
        }
        return hash($hashAlgorithm, strtolower(trim($normalized)));
    }

    /**
     * Returns the result of normalizing and hashing an email address. For this use case, Google
     * Ads requires removal of any '.' characters preceding "gmail.com" or "googlemail.com".
     *
     * @param string $hashAlgorithm the hash algorithm to use
     * @param string $emailAddress the email address to normalize and hash
     * @return string the normalized and hashed email address
     */
    private static function normalizeAndHashEmailAddress(
        string $hashAlgorithm,
        string $emailAddress
    ): string {
        $normalizedEmail = strtolower($emailAddress);
        $emailParts = explode("@", $normalizedEmail);
        if (
            count($emailParts) > 1
            && preg_match('/^(gmail|googlemail)\.com\s*/', $emailParts[1])
        ) {
            // Removes any '.' characters from the portion of the email address before the domain
            // if the domain is gmail.com or googlemail.com.
            $emailParts[0] = str_replace(".", "", $emailParts[0]);
            $normalizedEmail = sprintf('%s@%s', $emailParts[0], $emailParts[1]);
        }
        return self::normalizeAndHash($hashAlgorithm, $normalizedEmail, true);
    }

}
