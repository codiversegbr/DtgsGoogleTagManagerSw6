<?php declare(strict_types=1);

namespace Dtgs\GoogleTagManager\Components\Helper;

use Monolog\Level;
use Monolog\Logger;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class LoggingHelper
{

    private $systemConfigService;
    private $loggingService;

    /**
     * LoggingHelper constructor.
     *
     * @param SystemConfigService $systemConfigService
     * @param Logger $loggingService
     */
    public function __construct(SystemConfigService $systemConfigService, Logger $loggingService)
    {
        $this->systemConfigService = $systemConfigService;
        $this->loggingService = $loggingService;
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
    private function loggingEnabled(): bool
    {

        $tagManagerConfig = $this->getGtmConfig();

        if(isset($tagManagerConfig['tagmanagerLogging'])) {
            return !(($tagManagerConfig['tagmanagerLogging'] == 'off'));
        }
        return false;

    }

    /**
     * V 2.2.3 - Welcher Logging Typ ist an?
     * @param $type string
     * @return boolean
     */
    public function loggingType($type): bool
    {

        $tagManagerConfig = $this->getGtmConfig();

        if($this->loggingEnabled() && $tagManagerConfig['tagmanagerLogging'] == $type)
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

        $this->loggingService->log(Level::Debug, $msg, ['source' => 'DtgsGoogleTagManagerSw6']);

    }

}
