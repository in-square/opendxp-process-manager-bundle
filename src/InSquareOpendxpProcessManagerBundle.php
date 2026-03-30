<?php

/**
 * Created by valantic CX Austria GmbH
 *
 */

namespace InSquare\OpendxpProcessManagerBundle;

use InSquare\OpendxpProcessManagerBundle\DependencyInjection\Compiler;
use InSquare\OpendxpProcessManagerBundle\Model\Configuration;
use InSquare\OpendxpProcessManagerBundle\Model\MonitoringItem;
use OpenDxp\Bundle\AdminBundle\OpenDxpAdminBundle;
use OpenDxp\Extension\Bundle\AbstractOpenDxpBundle;
use OpenDxp\Extension\Bundle\Installer\InstallerInterface;
use OpenDxp\Extension\Bundle\OpenDxpBundleAdminClassicInterface;
use OpenDxp\Extension\Bundle\Traits\BundleAdminClassicTrait;
use OpenDxp\Extension\Bundle\Traits\PackageVersionTrait;
use OpenDxp\HttpKernel\Bundle\DependentBundleInterface;
use OpenDxp\HttpKernel\BundleCollection\BundleCollection;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Filesystem\Filesystem;

class InSquareOpendxpProcessManagerBundle extends AbstractOpenDxpBundle implements OpenDxpBundleAdminClassicInterface, DependentBundleInterface
{
    use ExecutionTrait;
    use PackageVersionTrait;
    use BundleAdminClassicTrait;

    /**
     * @return array<mixed>
     */
    public static function getMaintenanceOptions(): array
    {

        $logDir = str_replace(OPENDXP_PROJECT_ROOT, '', (string) self::getLogDir());

        return [
            'autoCreate' => true,
            'name' => 'ProcessManager maintenance',
            'loggers' => [
                [
                    'logLevel' => 'DEBUG',
                    'class' => '\\' . \InSquare\OpendxpProcessManagerBundle\Executor\Logger\Console::class,
                    'simpleLogFormat' => true,
                ],
                [
                    'logLevel' => 'DEBUG',
                    'filepath' => $logDir . 'process-manager-maintenance.log',
                    'class' => '\\' . \InSquare\OpendxpProcessManagerBundle\Executor\Logger\File::class,
                    'simpleLogFormat' => true,
                    'maxFileSizeMB' => 50,
                ],
            ],
        ];
    }

    /**
     * @var BundleConfiguration|null
     */
    protected static BundleConfiguration|null $_config = null;

    protected static ?MonitoringItem $monitoringItem = null;

    final public const BUNDLE_NAME = 'InSquareOpendxpProcessManagerBundle';

    final public const TABLE_NAME_CONFIGURATION = 'bundle_process_manager_configuration';

    final public const TABLE_NAME_MONITORING_ITEM = 'bundle_process_manager_monitoring_item';

    final public const TABLE_NAME_CALLBACK_SETTING = 'bundle_process_manager_callback_setting';

    final public const MONITORING_ITEM_ENV_VAR = 'monitoringItemId';

    /**
     * @return array<mixed>
     */
    public function getCssPaths(): array
    {
        return [
            '/bundles/insquareopendxpprocessmanager/css/admin.css',
        ];
    }

    /**
     * @return array<mixed>
     */
    public function getJsPaths(): array
    {
        $files = [
            '/bundles/insquareopendxpprocessmanager/js/startup.js',
            '/bundles/insquareopendxpprocessmanager/js/window/detailwindow.js',
            '/bundles/insquareopendxpprocessmanager/js/helper/form.js',

            '/bundles/insquareopendxpprocessmanager/js/panel/config.js',
            '/bundles/insquareopendxpprocessmanager/js/panel/general.js',
            '/bundles/insquareopendxpprocessmanager/js/panel/monitoringItem.js',
            '/bundles/insquareopendxpprocessmanager/js/panel/callbackSetting.js',

            '/bundles/insquareopendxpprocessmanager/js/executor/class/abstractExecutor.js',
            '/bundles/insquareopendxpprocessmanager/js/executor/class/command.js',
            '/bundles/insquareopendxpprocessmanager/js/executor/class/classMethod.js',
            '/bundles/insquareopendxpprocessmanager/js/executor/class/pimcoreCommand.js',

            '/bundles/insquareopendxpprocessmanager/js/executor/action/abstractAction.js',
            '/bundles/insquareopendxpprocessmanager/js/executor/action/download.js',
            '/bundles/insquareopendxpprocessmanager/js/executor/action/openItem.js',
            '/bundles/insquareopendxpprocessmanager/js/executor/action/jsEvent.js',

            '/bundles/insquareopendxpprocessmanager/js/executor/logger/abstractLogger.js',
            '/bundles/insquareopendxpprocessmanager/js/executor/logger/file.js',
            '/bundles/insquareopendxpprocessmanager/js/executor/logger/console.js',
            '/bundles/insquareopendxpprocessmanager/js/executor/logger/application.js',
            '/bundles/insquareopendxpprocessmanager/js/executor/logger/emailSummary.js',

            '/bundles/insquareopendxpprocessmanager/js/executor/callback/abstractCallback.js',
            '/bundles/insquareopendxpprocessmanager/js/executor/callback/example.js',
            '/bundles/insquareopendxpprocessmanager/js/executor/callback/default.js',
            '/bundles/insquareopendxpprocessmanager/js/window/activeProcesses.js',
        ];

        $callbackClasses = InSquareOpendxpProcessManagerBundle::getConfiguration()->getClassTypes()['executorCallbackClasses'];
        foreach ($callbackClasses as $e) {
            if ($file = $e['jsFile']) {
                $files[] = $file;
            }
        }

        return $files;
    }



    public function getRoutesPath(): ?string
    {
        return __DIR__ . '/Resources/config/pimcore/routing.yml';
    }

    /**
     * @inheritDoc
     */
    public function getInstaller(): InstallerInterface
    {
        return $this->container->get(Installer::class);
    }

    public function build(ContainerBuilder $container): void
    {
        $container
            ->addCompilerPass(new Compiler\ExecutorDefinitionPass());
    }

    /**
     * @param array<mixed> $arguments
     *
     * @return void
     */
    public static function shutdownHandler(array $arguments): void
    {
        if (($monitoringItem = self::getMonitoringItem()) instanceof MonitoringItem) {
            $error = error_get_last();
            Helper::executeMonitoringItemLoggerShutdown($monitoringItem);

            if (in_array($error['type'], [E_WARNING, E_DEPRECATED, E_STRICT, E_NOTICE])) {
                if (($config = Configuration::getById($monitoringItem->getConfigurationId())) instanceof \InSquare\OpendxpProcessManagerBundle\Model\Configuration) {
                    $versions = $config->getKeepVersions();
                    if (is_numeric($versions)) {
                        $list = new MonitoringItem\Listing();
                        $list->setOrder('DESC')->setOrderKey('id')->setOffset((int)$versions)->setLimit(100_000_000_000); //a limit has to defined otherwise the offset wont work
                        $list->setCondition('status ="finished" AND configurationId=? AND IFNULL(pid,0) != ? AND parentId IS NULL ', [$config->getId(), $monitoringItem->getPid()]);

                        $items = $list->load();
                        foreach ($items as $item) {
                            $item->delete();
                        }
                    }
                }
                if (!$monitoringItem->getMessage()) {
                    $monitoringItem->setMessage('finished');
                }
                $monitoringItem->setCompleted();
                $monitoringItem->setPid(null)->save();
            } else {
                $monitoringItem->setMessage('ERROR:' . print_r($error, true) . $monitoringItem->getMessage());
                $monitoringItem->setPid(null)->setStatus($monitoringItem::STATUS_FAILED)->save();
            }
        }
    }

    /**
     * @param array<mixed> $arguments
     *
     * @return void
     */
    public static function startup(array $arguments): void
    {
        $monitoringItem = $arguments['monitoringItem'];
        if ($monitoringItem instanceof MonitoringItem) {
            $monitoringItem->resetState()->save();
            $monitoringItem->setPid(getmypid());
            $monitoringItem->setStatus($monitoringItem::STATUS_RUNNING);
            $monitoringItem->save();
        }
    }

    /**
     * @return BundleConfiguration
     */
    public static function getConfiguration(): BundleConfiguration
    {
        if (is_null(self::$_config)) {
            $configArray = \OpenDxp::getKernel()->getContainer()->getParameter('in_square_opendxp_process_manager');
            self::$_config = new BundleConfiguration($configArray);
        }

        return self::$_config;
    }

    public static function getLogDir(): string
    {
        $dir = OPENDXP_LOG_DIRECTORY . '/process-manager/';
        if (!is_dir($dir)) {
            $filesystem = new Filesystem();
            $filesystem->mkdir($dir, 0775);
        }

        return $dir;
    }

    public function getDescription(): string
    {
        return 'Process Manager';
    }

    public static function setMonitoringItem(mixed $monitoringItem): void
    {
        self::$monitoringItem = $monitoringItem;
    }

    /**
     * @param bool $createDummyObjectIfRequired
     *
     * @return MonitoringItem|null
     */
    public static function getMonitoringItem(bool $createDummyObjectIfRequired = true): ?MonitoringItem
    {
        if ($createDummyObjectIfRequired && !self::$monitoringItem) {
            if (getenv(self::MONITORING_ITEM_ENV_VAR)) {
                self::$monitoringItem = MonitoringItem::getById((int)getenv(self::MONITORING_ITEM_ENV_VAR));
                self::$monitoringItem->setStatus(MonitoringItem::STATUS_RUNNING)->save();
            } else {
                self::$monitoringItem = new MonitoringItem();
                self::$monitoringItem->setIsDummy(true);
            }
        }

        return self::$monitoringItem;
    }

    protected function getComposerPackageName(): string
    {
        return 'insquare/opendxp-process-manager-bundle';
    }

    public function getNiceName(): string
    {
        return self::BUNDLE_NAME;
    }

    public static function registerDependentBundles(BundleCollection $collection): void
    {
        $collection->addBundle(new OpenDxpAdminBundle(), 60);
    }
}
