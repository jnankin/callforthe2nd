<?php

namespace Hackhouse\FilestoreBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class HackhouseFilestoreExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        $config = $configs[0];

        if (!isset($config['storage_method'])) {
            throw new \InvalidArgumentException('The storage_method option must be set');
        }

        $container->setParameter('filestore.storage_method', $config['storage_method']);
        $container->setParameter('filestore.bucket_name', $config['bucket_name']);
        $container->setParameter('aws.key', $config['aws']['key']);
        $container->setParameter('aws.secret', $config['aws']['secret']);
        $container->setParameter('aws.object_ttl', $config['aws']['object_ttl']);
        $container->setParameter('aws.region', $config['aws']['region']);
    }
}
