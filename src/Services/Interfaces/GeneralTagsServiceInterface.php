<?php

namespace Dtgs\GoogleTagManager\Services\Interfaces;

use Shopware\Core\Framework\Context;
use Shopware\Storefront\Page\Page;
use Symfony\Component\HttpFoundation\Request;

interface GeneralTagsServiceInterface
{
    /**
     * @param Page $page
     * @param Context $context
     * @param Request $request
     * @return array
     */
    public function getGeneralTags(Page $page, Context $context, Request $request);

    /**
     * @param Request $request
     * @return array
     */
    public function getUtmTags(Request $request);

    /**
     * @param Context $context
     * @return string
     */
    public function getLocaleCode(Context $context): string;

    /**
     * @param Context $context
     * @return string
     */
    public function getLanguageCode(Context $context): string;

    /**
     * @param string $localeCode
     * @return string
     */
    public function getCldrLanguageCode(string $localeCode);

    /**
     * @param string $localeCode
     * @return string
     */
    public function getCldrCountryCode(string $localeCode);
}
