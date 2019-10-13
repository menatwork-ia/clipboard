<?php

/**
 * Contao Open Source CMS
 *
 * @copyright  MEN AT WORK 2013
 * @package    clipboard
 * @license    GNU/LGPL
 * @filesource
 */

/**
 * Palettes
 */
foreach ($GLOBALS['TL_DCA']['tl_user']['palettes'] as $key => $row) {
    if ($key == '__selector__') {
        continue;
    }

    $arrPalettes   = explode(";", $row);
    $arrPalettes[] = '{clipboard_legend},clipboard,clipboard_context';

    $GLOBALS['TL_DCA']['tl_user']['palettes'][$key] = implode(";", $arrPalettes);
}

/**
 * Fields
 */
$GLOBALS['TL_DCA']['tl_user']['fields']['clipboard'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_user']['clipboard'],
    'inputType' => 'checkbox',
    'eval'      => ['tl_class' => 'clr w50'],
    'sql'       => 'char(1) NOT NULL default \'\''
];

$GLOBALS['TL_DCA']['tl_user']['fields']['clipboard_context'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_user']['clipboard_context'],
    'inputType' => 'checkbox',
    'eval'      => ['tl_class' => 'w50'],
    'sql'       => 'char(1) NOT NULL default \'\''
];