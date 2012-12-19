<?php if (!defined('TL_ROOT')) die('You cannot access this file directly!');

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2012 Leo Feyer
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

/**
 * Create DCA if clipboard is ready to use 
 */

if (Clipboard::getInstance()->isClipboard('module'))
{
    /**
     * Prepare clipboard contextmenu 
     */
    Clipboard::getInstance()->prepareContext();
    
    /**
     * Config 
     */
    $GLOBALS['TL_DCA']['tl_module']['config']['onload_callback'][] = array('Clipboard', 'init');
        
    $GLOBALS['TL_DCA']['tl_module']['config']['dataContainer'] = 'Clipboard';
    
    /**
     * List operations 
     */
    // Copy button
    $GLOBALS['TL_DCA']['tl_module']['list']['operations']['cl_copy'] = array
        (
        'label' => &$GLOBALS['TL_LANG']['tl_module']['copy']
    );
    
    $GLOBALS['TL_DCA']['tl_module']['list']['operations']['cl_copy'] = array_merge(
            $GLOBALS['CLIPBOARD']['copy'], $GLOBALS['TL_DCA']['tl_module']['list']['operations']['cl_copy']
    );

    if(Clipboard::getInstance()->cb()->hasFavorite())
    {        
        // -----------------------------------------------------------------------------
        
        $arrPasteInto = array(
            'cl_paste_into' => array(            
                'label'         => &$GLOBALS['TL_LANG']['tl_module']['pasteafter'][0],
                'href'          => 'key=cl_header_pastenew',
                'class'         => 'header_clipboard cl_header_pastenew',
                'attributes'    => 'onclick="Backend.getScrollOffset()" accesskey="p"'            
            )
        );
        
        $GLOBALS['TL_DCA']['tl_module']['list']['global_operations'] = array_merge(
            $arrPasteInto, $GLOBALS['TL_DCA']['tl_module']['list']['global_operations']                
        );
    }
}

?>