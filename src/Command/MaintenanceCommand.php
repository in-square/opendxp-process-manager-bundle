<?php

/**
 * Created by valantic CX Austria GmbH
 *
 */

namespace InSquare\OpendxpProcessManagerBundle\Command;

use InSquare\OpendxpProcessManagerBundle\InSquareOpendxpProcessManagerBundle;
use InSquare\OpendxpProcessManagerBundle\ExecutionTrait;
use InSquare\OpendxpProcessManagerBundle\Maintenance;
use OpenDxp\Console\AbstractCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Twig\Environment;

#[\Symfony\Component\Console\Attribute\AsCommand('process-manager:maintenance', 'Executes regular maintenance tasks (Check Processes, execute cronjobs)')]
class MaintenanceCommand extends AbstractCommand
{
    use ExecutionTrait;

    public function __construct(private readonly Environment $templatingEngine)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('monitoring-item-id', null, InputOption::VALUE_REQUIRED, 'Contains the monitoring item if executed via the Pimcore backend');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $options = InSquareOpendxpProcessManagerBundle::getMaintenanceOptions();
        $monitoringItem = static::initProcessManager($input->getOption('monitoring-item-id'), $options);
        static::doUniqueExecutionCheck(null, ['command' => static::getCommand($options)]);

        self::checkExecutingUser((array)InSquareOpendxpProcessManagerBundle::getConfiguration()->getAdditionalScriptExecutionUsers());

        $maintenance = new Maintenance($this->templatingEngine);
        $maintenance->execute();

        return Command::SUCCESS;
    }
}
