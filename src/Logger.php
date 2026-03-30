<?php

/**
 * Created by valantic CX Austria GmbH
 *
 */

namespace InSquare\OpendxpProcessManagerBundle;

use OpenDxp\Bundle\ApplicationLoggerBundle\ApplicationLogger;

class Logger extends ApplicationLogger
{
    public function log($level, string|\Stringable $message, array $context = []): void
    {
        parent::log($level, $message, $context);
        $monitoringItem = \InSquare\OpendxpProcessManagerBundle\InSquareOpendxpProcessManagerBundle::getMonitoringItem();

        if (($check = $monitoringItem->getCriticalErrorLevel()) && in_array($level, $check)) {
            $monitoringItem->setHasCriticalError(true)->save();
        }
    }

    public function closeLoggerHandlers(): void
    {

        /**
         * @var \Monolog\Logger $logger
         */
        foreach ($this->loggers as $logger) {
            foreach ($logger->getHandlers() as $handler) {
                $handler->close();
            }
        }
    }
}
