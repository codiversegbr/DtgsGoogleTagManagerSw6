<?php declare(strict_types=1);

namespace Dtgs\GoogleTagManager\Components\Helper;

use Shopware\Core\Framework\Log\LoggingService;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class LoggingHelper
{

    private $systemConfigService;
    private $loggingService;

    /**
     * LoggingHelper constructor.
     *
     * @param SystemConfigService $systemConfigService
     */
    public function __construct(SystemConfigService $systemConfigService)
    {
        $this->systemConfigService = $systemConfigService;
    }

    /**
     * @TODO: this needs to be Saleschannel specific, please see Datalayer service
     *
     * Helper to get plugin specific config
     *
     * @return array|mixed|null
     */
    public function getGtmConfig() {
        return $this->systemConfigService->get('DtgsGoogleTagManagerSw6.config');
    }

    /**
     * V 2.2.3 - Logging an/aus?
     * @return boolean
     */
    private function loggingEnabled() {

        $tagManagerConfig = $this->getGtmConfig();

        if(isset($tagManagerConfig['tagmanager_logging'])) {
            return ($tagManagerConfig['tagmanager_logging'] == 'off') ? false : true;
        }
        return false;

    }

    /**
     * V 2.2.3 - Welcher Logging Typ ist an?
     * @param $type string
     * @return boolean
     */
    public function loggingType($type) {

        $tagManagerConfig = $this->getGtmConfig();

        if($this->loggingEnabled() && $tagManagerConfig['tagmanager_logging'] == $type)
            return true;
        return false;

    }

    /**
     * @TODO!!
     *
     * @param $msg string
     * @return void
     */
    public function logMsg($msg) {

        echo $msg;

    }

}
