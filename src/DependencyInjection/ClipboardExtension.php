<?php

/**
 * Contao Open Source CMS
 *
 * @copyright  MEN AT WORK 2018
 * @package    clipboard
 * @author     Stefan Heimes <stefan_heimes@hotmail.com>
 * @license    GNU/LGPL
 */

namespace MenAtWork\ClipboardBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

/**
 * Class ContaoCoreExtension
 *
 * @package MenAtWork\ClipboardBundle\DependencyInjection
 */
class ClipboardExtension extends ConfigurableExtension
{
    /**
     * @var array
     */
    private $files = [
//        'commands.yml',
//        'listener.yml',
        'services.yml'
    ];

    /**
     * {@inheritdoc}
     */
    public function getAlias()
    {
        return 'clipboard';
    }

    /**
     * {@inheritdoc}
     */
    protected function loadInternal(array $mergedConfig, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config')
        );

        foreach ($this->files as $file) {
            $loader->load($file);
        }
    }
}
