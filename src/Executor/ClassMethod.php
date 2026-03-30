<?php

/**
 * Created by valantic CX Austria GmbH
 *
 */

namespace InSquare\OpendxpProcessManagerBundle\Executor;

use InSquare\OpendxpProcessManagerBundle\Model\MonitoringItem;
use OpenDxp\Tool\Console;

class ClassMethod extends AbstractExecutor
{
    protected string $name = 'classMethod';

    protected string $extJsClass = 'opendxp.plugin.processmanager.executor.class.classMethod';

    /**
     * @param string[] $callbackSettings
     * @param null | MonitoringItem $monitoringItem
     *
     * @return mixed
     */
    public function getCommand($callbackSettings = [], $monitoringItem = null)
    {
        $command =  Console::getPhpCli() . ' ' . realpath(OPENDXP_PROJECT_ROOT . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'console') . ' process-manager:class-method-executor -v';

        if ($monitoringItem instanceof \InSquare\OpendxpProcessManagerBundle\Model\MonitoringItem) {
            $command .= ' --monitoring-item-id='.$monitoringItem->getId();
        }

        return $command;
    }
}
