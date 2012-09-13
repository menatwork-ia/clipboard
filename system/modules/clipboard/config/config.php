<?php if (!defined('TL_ROOT')) die('You cannot access this file directly!');

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2010 Leo Feyer
 *
 * Formerly known as TYPOlight Open Source CMS.
 *
 * This program is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this program. If not, please visit the Free
 * Software Foundation website at <http://www.gnu.org/licenses/>.
 *
 * PHP version 5
 * @copyright  MEN AT WORK 2012
 * @package    clipboard
 * @license    GNU/LGPL
 * @filesource
 */

// Allowed clipboard locations
$arrAllowedLocations = array(
    'page',
    'article',
    'content'
);

if (TL_MODE == 'BE' && in_array(Input::getInstance()->get('do'), $arrAllowedLocations) && !Input::getInstance()->get('act') ||
    TL_MODE == 'BE' && in_array(Input::getInstance()->get('do'), $arrAllowedLocations) && Input::getInstance()->get('act') == 'select')
{
    /**
     * Set header informations 
     */
    $GLOBALS['TL_CSS']['clipboard']         = "system/modules/clipboard/html/clipboard.css";
    $GLOBALS['TL_JAVASCRIPT']['clipboard']  = "system/modules/clipboard/html/clipboard.js";

    /**
     * Hooks
     */
    $GLOBALS['TL_HOOKS']['outputBackendTemplate'][]                 = array('Clipboard', 'outputBackendTemplate');
    $GLOBALS['TL_HOOKS']['clipboardButtons'][]                      = array('ClipboardHelper', 'clipboardButtons');    
    $GLOBALS['TL_HOOKS']['clipboardActSelectButtonsTreeView'][]     = array('ClipboardHelper', 'clipboardActSelectButtons');
    $GLOBALS['TL_HOOKS']['clipboardActSelectButtonsParentView'][]   = array('ClipboardHelper', 'clipboardActSelectButtons');
    
    /**
     * Config
     */
    $GLOBALS['CLIPBOARD'] = array(
        // Copy button
        'copy' => array(
            'href'          => 'key=cl_copy',
            'icon'          => 'system/modules/clipboard/html/icons/icon-clipboard.png',
            'attributes'    => 'class="clipboardmenu" onclick="Backend.getScrollOffset();"'
        ),
        // Copy with children button
        'copy_childs' => array(
            'href'          => 'key=cl_copy&amp;childs=1',
            'icon'          => 'system/modules/clipboard/html/icons/icon-clipboard-childs.png',
            'attributes'    => 'class="cl_paste" onclick="Backend.getScrollOffset();"'        
        ),
        // Paste into button
        'pasteinto' => array(
            'href'          => 'key=cl_paste_into',            
            'icon'          => 'system/modules/clipboard/html/icons/icon-pasteafter.png',
            'attributes'    => 'class="clipboardmenu" onclick="Backend.getScrollOffset();"'
        ),
        // Paste after button
        'pasteafter' => array(
            'href'          => 'key=cl_paste_after',
            'icon'          => 'system/modules/clipboard/html/icons/icon-pasteinto.png',
            'attributes'    => 'class="clipboardmenu" onclick="Backend.getScrollOffset();"'
        ),
        'childs' => array(
            'icon'          => 'system/modules/clipboard/html/icons/icon-childs.png'
        ),
        'imported' => array(
            'icon'          => 'system/modules/clipboard/html/icons/icon-imported.png'
        ),
        'group' => array(
            'icon'          => 'system/modules/clipboard/html/icons/icon-group.png'
        ),
        'attribute' => array(
            'icon'          => 'system/modules/clipboard/html/icons/icon-attribute.png'
        ),
        'locations' => $arrAllowedLocations
    );
    $GLOBALS['CLIPBOARD']['favorite']['icon']  = 'system/modules/clipboard/html/icons/icon-favorite.gif';
    $GLOBALS['CLIPBOARD']['favorite_']['icon'] = 'system/modules/clipboard/html/icons/icon-favorite_.gif'; 
}
?>