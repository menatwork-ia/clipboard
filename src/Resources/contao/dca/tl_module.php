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
 * Create DCA if clipboard is ready to use 
 */

$clipboard = \Contao\System::getContainer()->get(\MenAtWork\ClipboardBundle\Clipboard::class);


if (TL_MODE == 'BE' && $clipboard->isClipboard('module'))
{
    /**
     * Prepare clipboard contextmenu 
     */
    $clipboard->prepareContext();
    
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

    if($clipboard->cb()->hasFavorite())
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