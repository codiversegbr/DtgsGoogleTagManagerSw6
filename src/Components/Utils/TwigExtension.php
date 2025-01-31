<?php

declare(strict_types=1);

namespace Dtgs\GoogleTagManager\Components\Utils;

use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class TwigExtension extends AbstractExtension
{
    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('gtmIsActive', [$this, 'isActive']),
            new TwigFunction('gtmGetContainerIds', [$this, 'getContainerIds']),
            new TwigFunction('gtmGetJsUrl', [$this, 'getJsUrl']),
            new TwigFunction('gtmGetNoScriptUrl', [$this, 'getNoScriptUrl']),
            new TwigFunction('gtmGetVariantName', [$this, 'getVariantName']),
            new TwigFunction('gtmGetCalculatedProductPrice', [$this, 'getCalculatedProductPrice']),
        ];
    }

    /**
     * @param string|null $containerIds
     * @return bool
     */
    public function isActive(?string $containerIds): bool
    {
        if(empty($this->getContainerIds($containerIds))) return false;

        return true;
    }

    /**
     * @param string|null $containerIds
     * @return array
     */
    public function getContainerIds(?string $containerIds): array
    {
        if(empty($containerIds) || $containerIds == '') return [];

        return explode(',', $containerIds);
    }

    /**
     * @param $config
     * @return string
     */
    public function getJsUrl($customGtmJsUrl): string
    {
        // Remove all illegal characters from url
        $customUrl = filter_var($customGtmJsUrl, FILTER_SANITIZE_URL);

        if (filter_var($customUrl, FILTER_VALIDATE_URL) !== FALSE) {
            $hasFilename = preg_match_all('/(\/)+([a-zA-Z0-9\s_\\.\-\(\):])+(.js)$/', $customUrl);
            if ($hasFilename === 0) {
                return rtrim($customUrl, "/") . '/gtm.js';
            } elseif ($hasFilename === 1) {
                return $customUrl;
            }
        }

        return 'https://www.googletagmanager.com/gtm.js';
    }

    /**
     * since 6.2.7: returns Noscript URL for server-side-tagging
     * if necessary
     * @param $customGtmJsUrl
     * @return string
     */
    public function getNoScriptUrl($customGtmJsUrl): string
    {
        // Remove all illegal characters from url
        $customUrl = filter_var($customGtmJsUrl, FILTER_SANITIZE_URL);

        if (filter_var($customUrl, FILTER_VALIDATE_URL) !== FALSE) {
            $hasFilename = preg_match_all('/(\/)+([a-zA-Z0-9\s_\\.\-\(\):])+(.js)$/', $customUrl, $matches);
            if ($hasFilename === 0) {
                return rtrim($customUrl, "/") . '/ns.html';
            } elseif ($hasFilename === 1) {
                $customUrl = str_replace($matches[0][0], '', $customUrl);
                return rtrim($customUrl, "/") . '/ns.html';
            }
        }

        return 'https://www.googletagmanager.com/ns.html';
    }

    /**
     * since 6.2.16: returns variant name from option array
     * if necessary
     * @param array $options
     * @return string
     */
    public function getVariantName(array $options)
    {
        if (empty($options)) return null;

        $variantName = '';
        foreach ($options as $option) {

            //$variantName .= $option['group'].': '.$option['option'];
            $variantName .= $option['option'].' ';

        }

        return trim($variantName);
    }

    /**
     * since 6.2.20: Get Calculated Price for listing pages
     * search for calculated price in GA4 Tags given to template
     * use SKU to identify the product
     *
     * @param $lineItem
     * @param $ga4tags
     * @param $cmsGa4tags
     * @return mixed
     */
    public function getCalculatedProductPrice($lineItem, $ga4tags, $cmsGa4tags = null): mixed
    {
        if ($ga4tags === null && $cmsGa4tags === null) return '';
        if ($ga4tags === null && $cmsGa4tags) $ga4tags = $cmsGa4tags;

        $ga4tagsAsObject = json_decode($ga4tags);

        $sku = $this->getSkuFromLineItem($lineItem);
        if(false === $sku) return '';

        try {
            if(!is_object($ga4tagsAsObject)) return '';
            if(!is_object($ga4tagsAsObject->ecommerce)) return '';
            if(!is_array($ga4tagsAsObject->ecommerce->items)) return '';
            $items = $ga4tagsAsObject->ecommerce->items;

            foreach ($items as $item) {
                if(!is_object($item)) return '';
                if($item->item_id == $sku) return $item->price;
            }
        }
        catch (\Exception $exception) {

        }

        return '';
    }

    /**
     * @param $item
     * @return false|mixed|string
     */
    private function getSkuFromLineItem($item): mixed
    {
        if(is_array($item)) {
            if(isset($item['productNumber'])) return $item['productNumber'];
            //Coupon?
            if(isset($item['promotionId'])) return 'voucher';
        }
        if(!is_array($item) && get_class($item) == SalesChannelProductEntity::class) {
            return $item->getProductNumber();
        }

        return false;
    }
}
