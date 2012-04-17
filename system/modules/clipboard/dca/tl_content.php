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
 * @license    GNU/GPL 2
 * @filesource
 */

/**
 * Create DCA if clipboard is ready to use 
 */
 
$this->loadLanguageFile('tl_page');

if (Clipboard::getInstance()->isClipboard('content'))
{
    /**
     * Prepare clipboard contextmenu 
     */
    Clipboard::getInstance()->prepareContext();    
    
    /**
     * Config 
     */
    $GLOBALS['TL_DCA']['tl_content']['config']['onload_callback'][] = array('Clipboard', 'init');

    if (Clipboard::getInstance()->cb()->hasElements())
    {
        $GLOBALS['TL_DCA']['tl_content']['config']['dataContainer'] = 'Clipboard';
    }

    /**
     * List operations 
     */
    // Copy button
    $GLOBALS['TL_DCA']['tl_content']['list']['operations']['cl_copy'] = array
        (
        'label' => &$GLOBALS['TL_LANG']['tl_content']['copy']
    );

    $GLOBALS['TL_DCA']['tl_content']['list']['operations']['cl_copy'] = array_merge(
            $GLOBALS['CLIPBOARD']['copy'], $GLOBALS['TL_DCA']['tl_content']['list']['operations']['cl_copy']
    );

    if(Clipboard::getInstance()->cb()->hasFavorite())
    {
        // -----------------------------------------------------------------------------
        // Paste after button    
        $GLOBALS['TL_DCA']['tl_content']['list']['operations']['cl_paste_after'] = array
            (
            'label' => $GLOBALS['TL_LANG']['tl_page']['pasteafter'],
            'attributes' => 'class="cl_paste"'
        );

        $GLOBALS['TL_DCA']['tl_content']['list']['operations']['cl_paste_after'] = array_merge(
                $GLOBALS['CLIPBOARD']['pasteafter'], $GLOBALS['TL_DCA']['tl_content']['list']['operations']['cl_paste_after']
        );
        
        $GLOBALS['TL_DCA']['tl_content']['list']['global_operations']['cl_paste_into'] = array(            
            'label'         => &$GLOBALS['TL_LANG']['tl_content']['pasteafter'],
            'href'          => 'key=cl_header_pastenew',
            'class'         => 'header_clipboard cl_header_pastenew',
            'attributes'    => 'onclick="Backend.getScrollOffset()" accesskey="p"'            
        );
    }
}

?>