<?php

/**
 * Created by valantic CX Austria GmbH
 *
 */

namespace InSquare\OpendxpProcessManagerBundle\MessageHandler;

use InSquare\OpendxpProcessManagerBundle\Message\ExecuteCommandMessage;
use InSquare\OpendxpProcessManagerBundle\Model\MonitoringItem;
use OpenDxp\Tool\Console;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ExecuteCommandHandler
{
    public function __invoke(ExecuteCommandMessage $message): void
    {
        $pid = Console::execInBackground($message->getCommand(), $message->getOutputFile());
        if ($monitoringItem = MonitoringItem::getById($message->getMonitoringItemId())) {
            $monitoringItem
                ->setMessengerPending(false)
                ->setPid($pid)
                ->save();

            $monitoringItem->getLogger()->info('Execution Command: ' . $message->getCommand() . ' in Background');
        }
    }
}
