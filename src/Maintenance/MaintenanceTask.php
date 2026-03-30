<?php

/**
 * Created by valantic CX Austria GmbH
 *
 */

namespace InSquare\OpendxpProcessManagerBundle\Maintenance;

use InSquare\OpendxpProcessManagerBundle\InSquareOpendxpProcessManagerBundle;
use InSquare\OpendxpProcessManagerBundle\Installer;
use InSquare\OpendxpProcessManagerBundle\Maintenance;
use OpenDxp\Maintenance\TaskInterface;
use Twig\Environment;

class MaintenanceTask implements TaskInterface
{
    protected \Twig\Environment $renderingEngine;

    /**
     * SystemEventsListener constructor.
     */
    public function __construct(Environment $renderingEngine, private readonly Installer $installer)
    {
        $this->renderingEngine = $renderingEngine;
    }

    public function execute(): void
    {
        if (!$this->installer->isInstalled()) {
            return;
        }

        $config = InSquareOpendxpProcessManagerBundle::getConfiguration();
        if ($config['general']['executeWithMaintenance']) {
            InSquareOpendxpProcessManagerBundle::initProcessManager(
                null,
                InSquareOpendxpProcessManagerBundle::getMaintenanceOptions()
            );
            $maintenance = new Maintenance($this->renderingEngine);
            $maintenance->execute();
        }
    }
}
