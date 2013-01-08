<?php

namespace Hackhouse\JobServerBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class HackhouseJobServerExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        $config = $configs[0];

        if (!isset($config['host'])) {
            throw new \InvalidArgumentException('The host option must be set');
        }

        if (!isset($config['port'])) $config['port'] = 11300;

        $container->setParameter('jobserver.host', $config['host']);
        $container->setParameter('jobserver.port', $config['port']);
        $container->setParameter('jobserver.tube', $config['tube']);
    }
}
