<?php

declare(strict_types=1);

namespace Dtgs\GoogleTagManager\Components\Utils;

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
}
