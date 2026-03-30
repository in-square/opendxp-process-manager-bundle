<?php

/**
 * Created by valantic CX Austria GmbH
 *
 */

namespace InSquare\OpendxpProcessManagerBundle\Executor;

use InSquare\OpendxpProcessManagerBundle\Model\Configuration;
use InSquare\OpendxpProcessManagerBundle\Model\MonitoringItem;
use InSquare\OpendxpProcessManagerBundle\Service\CommandsValidator;
use Exception;
use OpenDxp\Tool\Console;

class PimcoreCommand extends AbstractExecutor
{
    protected string $name = 'pimcoreCommand';

    protected string $extJsClass = 'opendxp.plugin.processmanager.executor.class.pimcoreCommand';

    /**
     * @param string[] $callbackSettings
     * @param null | MonitoringItem $monitoringItem
     *
     * @return mixed
     *
     * @throws Exception
     */
    public function getCommand($callbackSettings = [], $monitoringItem = null)
    {
        $options = $this->getValues()['commandOptions'] ?? '';
        $options = str_replace('|', '', trim((string) $options));
        $command = Console::getPhpCli() . ' ' . realpath(OPENDXP_PROJECT_ROOT . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'console') . ' ' . $this->getValues()['command'];

        if ($options !== '' && $options !== '0') {
            $command .= ' ' . $options;
        }

        if ($monitoringItem instanceof \InSquare\OpendxpProcessManagerBundle\Model\MonitoringItem) {
            $commands = \OpenDxp::getKernel()->getContainer()->get(CommandsValidator::class)->getValidCommands();

            if (!array_key_exists($this->getValues()['command'], $commands)) {
                throw new Exception('Invalid command - not in valid commands');
            }
            /**
             * @var \OpenDxp\Console\AbstractCommand $commandObject
             */
            $commandObject = $commands[$this->getValues()['command']];

            if ($commandObject->getDefinition()->hasOption('monitoring-item-id')) {
                $command .= ' --monitoring-item-id='.$monitoringItem->getId();
            }

            if ($monitoringItem->getParentId()) {
                $command .= ' --monitoring-item-parent-id='.$monitoringItem->getParentId();
            }
        }

        return $command;
    }

    public function validateConfiguration(Configuration $configuration): void
    {

        if ($configuration->getExecutorSettings()) {
            $settings = $configuration->getExecutorSettingsAsArray();
            $values = $settings['values'];
            if (!$values['command']) {
                throw new Exception('Please provide a command.');
            }
            $commandValidator = \OpenDxp::getKernel()->getContainer()->get(CommandsValidator::class);
            $commands = \OpenDxp::getKernel()->getContainer()->get(CommandsValidator::class)->getValidCommands();
            $commandValidator->validateCommandConfiguration($commands[$values['command']], $configuration);

        }
    }
}
