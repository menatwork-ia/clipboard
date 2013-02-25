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
$this->loadLanguageFile('tl_article');

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
        
    $GLOBALS['TL_DCA']['tl_content']['config']['dataContainer'] = 'Clipboard';
    
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
            'label' => array($GLOBALS['TL_LANG']['tl_article']['pasteafter'][0],$GLOBALS['TL_LANG']['tl_content']['pasteafter'][1]),
            'attributes' => 'class="cl_paste"'
        );

        $GLOBALS['TL_DCA']['tl_content']['list']['operations']['cl_paste_after'] = array_merge(
                $GLOBALS['CLIPBOARD']['pasteafter'], $GLOBALS['TL_DCA']['tl_content']['list']['operations']['cl_paste_after']
        );
        
        $arrPasteInto = array(
            'cl_paste_into' => array(            
                'label'         => &$GLOBALS['TL_LANG']['tl_content']['pasteafter'][0],
                'href'          => 'key=cl_header_pastenew',
                'class'         => 'header_clipboard cl_header_pastenew',
                'attributes'    => 'onclick="Backend.getScrollOffset()" accesskey="p"'            
            )
        );
        
        $GLOBALS['TL_DCA']['tl_content']['list']['global_operations'] = array_merge(
            $arrPasteInto, $GLOBALS['TL_DCA']['tl_content']['list']['global_operations']                
        );
    }
}

?>