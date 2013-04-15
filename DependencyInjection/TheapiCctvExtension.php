<?php

namespace Theapi\CctvBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class TheapiCctvExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);


        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');

        $container->setParameter('theapi_cctv.web_root', $config['web_root']);
        $container->setParameter('theapi_cctv.save_dir', $config['save_dir']);
        $container->setParameter('theapi_cctv.image_settings', $config['image_settings']);
        $container->setParameter('theapi_cctv.mailer_sender', $config['mailer_sender']);

    }
}
