<?php

/**
 * Contao Open Source CMS
 *
 * @copyright  MEN AT WORK 2018
 * @package    clipboard
 * @author     Stefan Heimes <stefan_heimes@hotmail.com>
 * @license    GNU/LGPL
 */

namespace MenAtWork\ClipboardBundle\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerBundle\ContaoManagerBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use MenAtWork\ClipboardBundle\ClipboardBundle;

/**
 * Plugin for the Contao Manager.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class Plugin implements BundlePluginInterface
{
    /**
     * {@inheritdoc}
     */
    public function getBundles(ParserInterface $parser)
    {
        return [
            BundleConfig::create(ClipboardBundle::class)
                        ->setLoadAfter(
                            [
                                ContaoCoreBundle::class,
                                ContaoManagerBundle::class,
                            ]
                        ),
        ];
    }
}
