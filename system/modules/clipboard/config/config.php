<?php

/**
 * Contao Open Source CMS
 *
 * @copyright  MEN AT WORK 2013 
 * @package    clipboard
 * @license    GNU/LGPL 
 * @filesource
 */

// Allowed clipboard locations
$arrAllowedLocations = array(
    'page',
    'article',
    'content',
    'module'
);

$strDo = Input::getInstance()->get('do');
$strAct = Input::getInstance()->get('act');
$strTable = Input::getInstance()->get('table');

if(TL_MODE == 'BE' &&
    (in_array($strDo, $arrAllowedLocations) || ($strDo == 'themes' && $strTable == 'tl_module')) &&
    (!$strAct || $strAct == 'select'))
{
    /**
     * Set header informations 
     */
    $GLOBALS['TL_CSS']['clipboard']         = "system/modules/clipboard/assets/clipboard.css";
    $GLOBALS['TL_JAVASCRIPT']['clipboard']  = "system/modules/clipboard/assets/clipboard.js";

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
            'icon'          => 'system/modules/clipboard/assets/icons/icon-clipboard.png',
            'attributes'    => 'class="clipboardmenu" onclick="Backend.getScrollOffset();"'
        ),
        // Copy with children button
        'copy_childs' => array(
            'href'          => 'key=cl_copy&amp;childs=1',
            'icon'          => 'system/modules/clipboard/assets/icons/icon-clipboard-childs.png',
            'attributes'    => 'class="cl_paste" onclick="Backend.getScrollOffset();"'        
        ),
        // Paste into button
        'pasteinto' => array(
            'href'          => 'key=cl_paste_into',            
            'icon'          => 'system/modules/clipboard/assets/icons/icon-pasteafter.png',
            'attributes'    => 'class="clipboardmenu" onclick="Backend.getScrollOffset();"'
        ),
        // Paste after button
        'pasteafter' => array(
            'href'          => 'key=cl_paste_after',
            'icon'          => 'system/modules/clipboard/assets/icons/icon-pasteinto.png',
            'attributes'    => 'class="clipboardmenu" onclick="Backend.getScrollOffset();"'
        ),
        'childs' => array(
            'icon'          => 'system/modules/clipboard/assets/icons/icon-childs.png'
        ),
        'imported' => array(
            'icon'          => 'system/modules/clipboard/assets/icons/icon-imported.png'
        ),
        'group' => array(
            'icon'          => 'system/modules/clipboard/assets/icons/icon-group.png'
        ),
        'favorite' => array(
            'icon'          => 'system/modules/clipboard/assets/icons/icon-favorite.png',
            'icon_'          => 'system/modules/clipboard/assets/icons/icon-favorite_.png'
        ),
        'attribute' => array(
            'icon'          => 'system/modules/clipboard/assets/icons/icon-attribute.png'
        ),
        'locations' => $arrAllowedLocations
    );
}
?>