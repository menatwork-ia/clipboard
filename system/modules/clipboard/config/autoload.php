<?php

/**
 * Contao Open Source CMS
 * 
 * Copyright (C) 2005-2013 Leo Feyer
 * 
 * @package Clipboard
 * @link    https://contao.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 */


/**
 * Register the classes
 */
ClassLoader::addClasses(array
(
    'DC_Clipboard'        => 'system/modules/clipboard/drivers/DC_Clipboard.php',
    'Clipboard'           => 'system/modules/clipboard/classes/Clipboard.php',
    'ClipboardDatabase'   => 'system/modules/clipboard/classes/ClipboardDatabase.php',
    'ClipboardHelper'     => 'system/modules/clipboard/classes/ClipboardHelper.php',
    'ClipboardXml'        => 'system/modules/clipboard/classes/ClipboardXml.php',
    'ClipboardXmlElement' => 'system/modules/clipboard/classes/ClipboardXmlElement.php',
    'ClipboardXmlReader'  => 'system/modules/clipboard/classes/ClipboardXmlReader.php',
    'ClipboardXmlWriter'  => 'system/modules/clipboard/classes/ClipboardXmlWriter.php',
));


/**
 * Register the templates
 */
TemplateLoader::addFiles(array
(
    'be_clipboard' => 'system/modules/clipboard/templates',
));