<?php

/**
 * Created by valantic CX Austria GmbH
 *
 */

namespace App\Command;

use InSquare\OpendxpProcessManagerBundle\Executor\Action;
use OpenDxp\Console\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ProcessManagerSampleCommandAdvanced extends AbstractCommand
{
    use \InSquare\OpendxpProcessManagerBundle\ExecutionTrait;

    protected function configure(): void
    {
        $this
            ->setName('process-manager:sample-command-advanced')
            ->setDescription('Just an example - using the ProcessManager with steps and actions')
            ->addOption(
                'monitoring-item-id',
                null,
                InputOption::VALUE_REQUIRED,
                'Contains the monitoring item if executed via the Pimcore backend'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->initProcessManager($input->getOption('monitoring-item-id'), ['autoCreate' => true]);

        $classList = new \OpenDxp\Model\DataObject\ClassDefinition\Listing();
        $classList->setLimit(1);
        $classes = $classList->load();

        $monitoringItem = $this->getMonitoringItem();
        $totalSteps = count($classes) + 1;
        $monitoringItem->setTotalSteps($totalSteps)->save();

        $data = [];
        foreach ($classes as $i => $class) {
            /**
             * @var $list \OpenDxp\Model\DataObject\Listing
             * @var $class \OpenDxp\Model\DataObject\ClassDefinition
             * @var $o \OpenDxp\Model\DataObject\AbstractObject
             */
            $monitoringItem->setCurrentStep($i + 1)->setMessage('Processing Object of class '.$class->getName())->save();
            sleep(5);
            $listName = '\OpenDxp\Model\DataObject\\'.ucfirst($class->getName()).'\Listing';
            $list = new $listName();
            $total = $list->getTotalCount();
            $perLoop = 50;

            for ($k = 0, $kMax = ceil($total / $perLoop); $k < $kMax; $k++) {
                $list->setLimit($perLoop);
                $offset = $k * $perLoop;
                $list->setOffset($offset);

                $monitoringItem->setCurrentWorkload(($offset ?: 1))
                    ->setTotalWorkload($total)
                    ->setDefaultProcessMessage($class->getName())
                    ->save();
                sleep(2);

                $monitoringItem->getLogger()->info($monitoringItem->getMessage());
                $objects = $list->load();

                foreach ($objects as $o) {
                    $data[] = [
                        'ObjectType' => $class->getName(),
                        'id' => $o->getId(),
                        'modificationDate' => $o->getModificationDate(),
                    ];
                    $monitoringItem->getLogger()->info('Processing Object with id: '.$o->getId());
                }
            }

            $monitoringItem->setWorkloadCompleted()->save();
            \OpenDxp::collectGarbage();
        }
        $monitoringItem->setCurrentStep($totalSteps)->setCurrentWorkload(0)->setTotalWorkload(0)->setMessage(
            'creating csv file'
        )->save();

        $csvFile = PIMCORE_SYSTEM_TEMP_DIRECTORY . '/process-manager-example.csv';

        $file = fopen($csvFile, 'w');
        if (!empty($data)) {
            array_unshift($data, array_keys($data[0]));
            foreach ($data as $row) {
                fputcsv($file, $row);
            }
        }
        fclose($file);
        $monitoringItem->setCurrentWorkload(1)->setTotalWorkload(1)->setMessage('csv file created')->save();

        //adding some actions programmatically
        $downloadAction = new Action\Download();
        $downloadAction
            ->setAccessKey('myIcon')
            ->setLabel('Download Icon')
            ->setFilePath('/public/bundles/insquareopendxpprocessmanager/img/sprite-open-item-action.png')
            ->setDeleteWithMonitoringItem(false);

        $openItemAction = new Action\OpenItem();
        $openItemAction
            ->setLabel('Open document')
            ->setItemId(1)
            ->setType('document');

        $monitoringItem->setActions([
            $downloadAction,
            $openItemAction,
        ]);

        $monitoringItem->setMessage('Job finished')->setCompleted();

        return self::SUCCESS;
    }
}
