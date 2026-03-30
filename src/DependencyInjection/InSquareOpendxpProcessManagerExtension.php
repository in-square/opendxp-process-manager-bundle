<?php

/**
 * Created by valantic CX Austria GmbH
 *
 */

namespace InSquare\OpendxpProcessManagerBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

class InSquareOpendxpProcessManagerExtension extends ConfigurableExtension implements PrependExtensionInterface
{
    public function getAlias(): string
    {
        return 'in_square_opendxp_process_manager';
    }

    public function prepend(ContainerBuilder $container): void
    {
        if ($container->hasExtension('doctrine_migrations')) {
            $loader = new YamlFileLoader(
                $container,
                new FileLocator(__DIR__ . '/../Resources/config')
            );

            $loader->load('doctrine_migrations.yml');
        }
    }

    /**
     * {@inheritdoc}
     *
     * @param array<array<mixed>> $mergedConfig
     * @param ContainerBuilder $container
     *
     * @return void
     *
     * @throws \Exception
     */
    public function loadInternal(array $mergedConfig, ContainerBuilder $container): void
    {
        $container->setParameter('in_square_opendxp_process_manager', $mergedConfig);

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config')
        );

        $loader->load('services.yml');
    }
}
