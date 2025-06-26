<?php
/**
 * Created by PhpStorm.
 * User: constantin
 * Date: 28.02.17
 * Time: 15:35
 */
namespace Dtgs\GoogleTagManager\Components\Helper;

use Shopware\Core\Checkout\Customer\Aggregate\CustomerGroup\CustomerGroupCollection;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerGroup\CustomerGroupEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class PriceHelper
{
    private $systemConfigService;
    /**
     * @var EntityRepository
     */
    private $customerGroupRepository;

    public function __construct(SystemConfigService $systemConfigService, EntityRepository $customerGroupRepository)
    {
        $this->systemConfigService = $systemConfigService;
        $this->customerGroupRepository = $customerGroupRepository;
    }

    /**
     * V 1.3.5 - Netto/Bruttopreise übergeben - Preistyp ermitteln
     * @param SalesChannelContext $context
     * @return string
     * @throws \Exception
     */
    public function getPriceType(SalesChannelContext $context) {

        $tagManagerConfig = $this->systemConfigService->get('DtgsGoogleTagManagerSw6.config', $context->getSalesChannelId());

        if(isset($tagManagerConfig['showPriceType'])) {

            $price_type = $tagManagerConfig['showPriceType'];

            if($price_type == 'netto') return 'netto';
            else return 'brutto';

        }

    }

    /**
     * V 1.3.5 - Netto/Bruttopreise übergeben - Preis errechnen
     * @param $price
     * @param int $tax
     * @param SalesChannelContext $context
     * @return string
     * @throws \Exception
     */
    public function getPrice($price, $tax, SalesChannelContext $context) {

        if(!is_numeric($tax)) $tax = 19;

        //Einstellung im Plugin - Netto oder Bruttopreise ausgeben?
        $plugin_price_type = $this->getPriceType($context);

        //see FD-32842
        $customerGroup = $context->getCurrentCustomerGroup();
        $isPriceTypeGross = $customerGroup->getDisplayGross();

        //nur wenn im Plugin Netto eingestellt und die SW Preise Brutto sind! (Änderung in V2.6.1)
        if($plugin_price_type == 'netto' && $isPriceTypeGross) {
            return number_format($price / (100 + $tax) * 100, 2, '.', ''); //1.3.6 - 1000er Separator entfernt
        }
        else {
            return number_format($price, 2, '.', '');
        }

    }

    /**
     * Float Converter
     * @param $str
     * @return float
     */
    public function parseFloat($str) {
        if (is_int($str) || is_float($str)) { return floatval($str); }

        $str = trim($str);

        $last = max(strrpos($str, ','), strrpos($str, '.'));
        if ($last!==false) {
            $str = strtr($str, ',.', 'XX');
            $str[$last] = '.';
            $str = str_replace('X', '', $str); // strtr funktioniert nicht mit $to=''
        }
        return (float)$str;
    }

    public function formatPrice($price) {
        return number_format($price, 2, '.', '');
    }

    /**
     * @param $groupId
     * @param Context $context
     * @return CustomerGroupEntity|null
     */
    private function getCustomerGroup($groupId, Context $context) {

        $criteria = new Criteria([$groupId]);
        /** @var CustomerGroupCollection $customerGroupCollection */
        $customerGroupCollection = $this->customerGroupRepository->search($criteria, $context)->getEntities();
        return $customerGroupCollection->get($groupId);

    }

    /**
     * @param $groupId
     * @param Context $context
     * @return CustomerGroupEntity|null
     */
    private function getDefaultCustomerGroup(Context $context) {

        $criteria = new Criteria();
        /** @var CustomerGroupCollection $customerGroupCollection */
        return $this->customerGroupRepository->search($criteria, $context)->first();

    }
}
