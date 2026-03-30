<?php

/**
 * Created by valantic CX Austria GmbH
 *
 */

namespace InSquare\OpendxpProcessManagerBundle\Executor\Logger;

use InSquare\OpendxpProcessManagerBundle\Model\MonitoringItem;
use OpenDxp\Bundle\ApplicationLoggerBundle\Handler\ApplicationLoggerDb;

class Application extends AbstractLogger
{
    protected ApplicationLoggerDb $streamHandler;

    public string $name = 'application';

    public string $extJsClass = 'opendxp.plugin.processmanager.executor.logger.application';

    /**
     * @param $monitoringItem MonitoringItem
     * @param array<mixed> $actionData
     *
     * @return string
     */
    public function getGridLoggerHtml(MonitoringItem $monitoringItem, array $actionData): string
    {

        return '<a href="#"
                        data-process-manager-trigger="showApplicationLogs"
                data-process-manager-id="' . $monitoringItem->getId() . '"
                data-process-manager-action-index="' . (int)$actionData['index'] . '"
        class=" " alt="Show logs"><img src="/bundles/opendxpadmin/img/flat-color-icons/text.svg" alt="Application Logger" height="18" title="Application Logger"/></a>';
    }

    /**
     * @param array<mixed> $config
     *
     */
    public function createStreamHandler(array $config, MonitoringItem $monitoringItem): ApplicationLoggerDb
    {
        if (!isset($this->streamHandler)) {
            if (!$config['logLevel']) {
                $config['logLevel'] = 'DEBUG';
            }
            $logLevel = constant('\Psr\Log\LogLevel::'.$config['logLevel']);

            $this->streamHandler = new ApplicationLoggerDb(\OpenDxp\Db::get(), $logLevel);
        }

        return $this->streamHandler;
    }
}
