<?php

/**
 * Created by valantic CX Austria GmbH
 *
 */

namespace InSquare\OpendxpProcessManagerBundle;

use InSquare\OpendxpProcessManagerBundle\Message\ExecuteCommandMessage;
use InSquare\OpendxpProcessManagerBundle\Model\Configuration;
use InSquare\OpendxpProcessManagerBundle\Model\MonitoringItem;

class Helper
{
    /**
     * @param string $configId
     * @param array<mixed> $callbackSettings
     * @param int $userId
     * @param mixed $metaData
     * @param mixed $parentMonitoringItemId
     * @param callable|null $callback
     *
     * @return array<mixed>
     *
     */
    public static function executeJob(string $configId, array $callbackSettings = [], int $userId = 0, mixed $metaData = [], mixed $parentMonitoringItemId = null, ?callable $callback = null)
    {
        try {
            $config = Configuration::getById($configId);

            if (!$config instanceof \InSquare\OpendxpProcessManagerBundle\Model\Configuration) {
                $config = new Configuration();
                $config->setExecutorClass(Executor\PimcoreCommand::class);
            }

            $executor = $config->getExecutorClassObject();
            $uniqueExecution = $executor->getValues()['uniqueExecution'] ?? false;
            if ($uniqueExecution && is_null($parentMonitoringItemId)) {
                $running = $config->getRunningProcesses();
                if ($running !== []) {
                    $msg = "Can't start the process because " . count($running) . ' process is running (ID: ' . $running[0]->getId() . '). Please wait until this processes is finished.';

                    throw new \Exception($msg);
                }
            }

            $monitoringItem = new MonitoringItem();
            $monitoringItem->setName($config->getName());
            $monitoringItem->setStatus($monitoringItem::STATUS_INITIALIZING);
            $monitoringItem->setConfigurationId($config->getId());
            $monitoringItem->setCallbackSettings($callbackSettings);
            $monitoringItem->setExecutedByUser($userId);
            $monitoringItem->setActions($executor->getActions());
            $monitoringItem->setLoggers($executor->getLoggers());
            $monitoringItem->setMetaData($metaData);
            $monitoringItem->setParentId($parentMonitoringItemId);

            if (($executorSettings = $config->getExecutorSettings()) !== '' && ($executorSettings = $config->getExecutorSettings()) !== '0') {
                $executorData = json_decode((string) $config->getExecutorSettings(), true, 512, JSON_THROW_ON_ERROR);

                if ($executorData['values']) {
                    $hideMonitoringItem = $executorData['values']['hideMonitoringItem'] ?? false;
                    if ($hideMonitoringItem == 'on') {
                        $monitoringItem->setPublished(false);
                    }
                    $monitoringItem->setGroup($executorData['values']['group']);
                    $monitoringItem->setDeleteAfterHours(isset($executorData['values']['deleteAfterHours']) ? (int)$executorData['values']['deleteAfterHours'] : null);
                }
            }

            if ($callback) {
                $callback($monitoringItem, $executor);
            }
            $monitoringItem->setMessengerPending(true);
            $item = $monitoringItem->save();

            $command = $executor->getCommand($callbackSettings, $monitoringItem);
            $command = escapeshellcmd($command); //prevent os command injection

            putenv(InSquareOpendxpProcessManagerBundle::MONITORING_ITEM_ENV_VAR . '=' . $item->getId());

            if (!$executor->getIsShellCommand()) {
                $monitoringItem->setCommand($command)->save();
            } else {
                $monitoringItem->setCommand($command)->save();
                $command = $executor->getShellCommand($monitoringItem);
            }
            $messageBus = \OpenDxp::getContainer()->get('messenger.bus.opendxp-core');
            $message = new ExecuteCommandMessage($command, $monitoringItem->getId());
            $messageBus->dispatch($message);

            return ['success' => true, 'executedCommand' => $command, 'monitoringItemId' => $item->getId()];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * @param \OpenDxp\Model\User $user
     *
     * @return array<mixed>
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public static function getAllowedConfigIdsByUser(\OpenDxp\Model\User $user)
    {
        if (!$user->isAdmin()) {
            $roles = $user->getRoles();
            if ($roles === []) {
                $roles[] = 'no_result';
            }
            $c = [];
            foreach ($roles as $r) {
                $c[] = 'restrictToRoles LIKE "%,' . $r . ',%" ';
            }
            $c = ' (restrictToRoles = "" OR (' . implode(' OR ', $c) . ')) ';

            if ($user->getPermissions()) {

                $permissionConditions = [];
                foreach ($user->getPermissions() as $permission) {
                    $permissionConditions[] = 'restrictToPermissions LIKE "%,' . $permission . ',%" ';
                }
                $c .= ' AND (restrictToPermissions = "" OR (' . implode(' OR ', $permissionConditions) . ')) ';
            }

            return \OpenDxp\Db::get()->fetchFirstColumn('SELECT id FROM ' . Configuration\Listing\Dao::getTableName() . ' WHERE ' . $c);
        } else {
            return \OpenDxp\Db::get()->fetchFirstColumn('SELECT id FROM ' . Configuration\Listing\Dao::getTableName());
        }
    }

    /**
     * @param MonitoringItem $monitoringItem
     * @param bool $preventModificationDateUpdate
     *
     * @return MonitoringItem
     *
     * @throws \JsonException
     */
    public static function executeMonitoringItemLoggerShutdown(MonitoringItem $monitoringItem, $preventModificationDateUpdate = false): MonitoringItem
    {
        $loggers = $monitoringItem->getLoggers();

        foreach ((array)$loggers as $i => $loggerConfig) {
            $loggerClass = $loggerConfig['class'];
            if (!class_exists($loggerClass)) {
                continue;
            }
            $logObj = new $loggerClass;
            if (method_exists($logObj, 'handleShutdown')) {
                $result = $logObj->handleShutdown($monitoringItem, $loggerConfig);
                if ($result) {
                    $loggers[$i] = array_merge($loggerConfig, $result);
                }
            }
        }
        $monitoringItem->setLoggers($loggers)->save();

        return $monitoringItem;
    }
}
