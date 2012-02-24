<?php if (!defined('TL_ROOT')) 
    die('You cannot access this file directly!');

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

 if (TL_MODE == 'BE' && in_array(Input::getInstance()->get('do'), $arrAllowedLocations))
 {
    /**
     * Set header informations 
     */
    $GLOBALS['TL_CSS']['Clipboard']         = "system/modules/clipboard/html/clipboard_src.css";
    $GLOBALS['TL_JAVASCRIPT']['Clipboard']  = "system/modules/clipboard/html/clipboard_src.js";

    /**
     * Hooks
     */
    $GLOBALS['TL_HOOKS']['outputBackendTemplate'][]                 = array('Clipboard', 'outputBackendTemplate');
    $GLOBALS['TL_HOOKS']['independentlyButtons'][]                  = array('ClipboardHelper', 'independentlyButtons');
    $GLOBALS['TL_HOOKS']['independentlyTlContentHeaderButtons'][]   = array('ClipboardHelper', 'independentlyTlContentHeaderButtons');    
    
    /**
     * Config
     */
    $GLOBALS['CLIPBOARD'] = array(
        // Copy button
        'copy' => array(
            'href'          => 'key=cl_copy',
            'icon'          => 'featured.gif',
            'attributes'    => 'class="clipboardmenu" onclick="Backend.getScrollOffset();"',            
        ),
        // Copy with children button
        'copy_childs' => array(
            'href'          => 'key=cl_copy&amp;childs=1',
            'icon'          => 'copychilds.gif',
            'attributes'    => 'class="cl_paste" onclick="Backend.getScrollOffset();"',            
        ),
        // Paste into button
        'pasteinto' => array(            
            'href'          => '&amp;act=copy&amp;mode=2',
            'icon'          => 'pasteafter.gif',
            'attributes'    => ''
        ),
        // Paste after button
        'pasteafter' => array(
            'href'          => '&amp;act=copy&amp;mode=1',
            'icon'          => 'pasteinto.gif',
            'attributes'    => ''            
        ),
        'locations' => $arrAllowedLocations
    );
}
?>