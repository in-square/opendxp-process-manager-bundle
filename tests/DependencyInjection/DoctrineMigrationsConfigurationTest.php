<?php

declare(strict_types=1);

namespace InSquare\OpendxpProcessManagerBundle\Tests\DependencyInjection;

use Doctrine\Bundle\MigrationsBundle\DependencyInjection\CompilerPass\ConfigureDependencyFactoryPass;
use Doctrine\Bundle\MigrationsBundle\DependencyInjection\Configuration as DoctrineMigrationsConfiguration;
use Doctrine\Bundle\MigrationsBundle\DependencyInjection\DoctrineMigrationsExtension;
use Doctrine\Migrations\Configuration\Connection\ConnectionRegistryConnection;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Finder\GlobFinder;
use InSquare\OpendxpProcessManagerBundle\DependencyInjection\InSquareOpendxpProcessManagerExtension;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

final class DoctrineMigrationsConfigurationTest extends TestCase
{
    private const MIGRATIONS_NAMESPACE = 'InSquare\OpendxpProcessManagerBundle\Migrations';

    public function testBundlePrependsDbalOnlyMigrationConfiguration(): void
    {
        $config = $this->getProcessedConfiguration();

        self::assertSame('default', $config['connection']);
        self::assertNull($config['em']);
        self::assertSame(
            '@InSquareOpendxpProcessManagerBundle/Migrations',
            $config['migrations_paths'][self::MIGRATIONS_NAMESPACE]
        );
    }

    public function testDefaultConnectionIsSelectedWithoutEntityManagers(): void
    {
        $this->assertConnectionFactoryIsSelected([
            'default' => 'doctrine.dbal.default_connection',
        ]);
    }

    public function testDefaultConnectionIsSelectedWithAdditionalConnectionsAndEntityManagers(): void
    {
        $this->assertConnectionFactoryIsSelected(
            [
                'default' => 'doctrine.dbal.default_connection',
                'reporting' => 'doctrine.dbal.reporting_connection',
            ],
            [
                'default' => 'doctrine.orm.default_entity_manager',
                'reporting' => 'doctrine.orm.reporting_entity_manager',
            ]
        );
    }

    public function testAllBundleMigrationsAreDiscoverable(): void
    {
        $migrations = (new GlobFinder())->findMigrations(
            dirname(__DIR__, 2) . '/src/Migrations',
            self::MIGRATIONS_NAMESPACE
        );

        sort($migrations);

        self::assertSame([
            self::MIGRATIONS_NAMESPACE . '\\Version20210428000000',
            self::MIGRATIONS_NAMESPACE . '\\Version20210802000000',
            self::MIGRATIONS_NAMESPACE . '\\Version20230207000000',
            self::MIGRATIONS_NAMESPACE . '\\Version20230217000000',
            self::MIGRATIONS_NAMESPACE . '\\Version20230321092750',
            self::MIGRATIONS_NAMESPACE . '\\Version20230425000002',
            self::MIGRATIONS_NAMESPACE . '\\Version20241211095632',
            self::MIGRATIONS_NAMESPACE . '\\Version20261004000000',
        ], $migrations);
    }

    /**
     * @param array<string, string>      $connections
     * @param array<string, string>|null $entityManagers
     */
    private function assertConnectionFactoryIsSelected(array $connections, ?array $entityManagers = null): void
    {
        $config = $this->getProcessedConfiguration();
        $container = new ContainerBuilder();

        $container->setParameter('doctrine.connections', $connections);
        $container->setParameter('doctrine.migrations.preferred_connection', $config['connection']);
        $container->setParameter('doctrine.migrations.preferred_em', $config['em']);

        if ($entityManagers !== null) {
            $container->setParameter('doctrine.entity_managers', $entityManagers);
        }

        $container->setDefinition('doctrine', new Definition(stdClass::class));
        $container->setDefinition(
            'doctrine.migrations.dependency_factory',
            new Definition(DependencyFactory::class)
        );
        $container->setDefinition(
            'doctrine.migrations.connection_registry_loader',
            new Definition(ConnectionRegistryConnection::class)
        );

        (new ConfigureDependencyFactoryPass())->process($container);

        $dependencyFactory = $container->getDefinition('doctrine.migrations.dependency_factory');
        self::assertSame([DependencyFactory::class, 'fromConnection'], $dependencyFactory->getFactory());

        $loaderReference = $dependencyFactory->getArgument(1);
        self::assertInstanceOf(Reference::class, $loaderReference);
        self::assertSame('doctrine.migrations.connection_registry_loader', (string) $loaderReference);
        self::assertSame(
            'default',
            $container->getDefinition('doctrine.migrations.connection_registry_loader')->getArgument(1)
        );
    }

    /**
     * @return array{
     *     connection: string,
     *     em: null,
     *     migrations_paths: array<string, string>
     * }
     */
    private function getProcessedConfiguration(): array
    {
        $container = new ContainerBuilder();
        $container->registerExtension(new DoctrineMigrationsExtension());

        (new InSquareOpendxpProcessManagerExtension())->prepend($container);

        /** @var array{connection: string, em: null, migrations_paths: array<string, string>} $config */
        $config = (new Processor())->processConfiguration(
            new DoctrineMigrationsConfiguration(),
            $container->getExtensionConfig('doctrine_migrations')
        );

        return $config;
    }
}
