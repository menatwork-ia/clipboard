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
if (Clipboard::getInstance()->isClipboard('article'))
{
    /**
     * Prepare clipboard contextmenu 
     */
    Clipboard::getInstance()->prepareContext();
    
    /**
     * Config 
     */
    $GLOBALS['TL_DCA']['tl_article']['config']['onload_callback'][] = array('clipboard', 'init');

    $GLOBALS['TL_DCA']['tl_article']['config']['dataContainer'] = 'Clipboard';

    /**
     * List operations 
     */
    // Copy button
    $GLOBALS['TL_DCA']['tl_article']['list']['operations']['cl_copy'] = array
        (
        'label' => &$GLOBALS['TL_LANG']['tl_article']['copy'],
        'attributes' => 'class="clipboardmenu" onclick="Backend.getScrollOffset();"',
    );

    $GLOBALS['TL_DCA']['tl_article']['list']['operations']['cl_copy'] = array_merge(
            $GLOBALS['CLIPBOARD']['copy'], $GLOBALS['TL_DCA']['tl_article']['list']['operations']['cl_copy']
    );

    if(Clipboard::getInstance()->cb()->hasFavorite())
    {
        // -----------------------------------------------------------------------------
        // Paste after button    
        $GLOBALS['TL_DCA']['tl_article']['list']['operations']['cl_paste_after'] = array
            (
            'label' => &$GLOBALS['TL_LANG']['tl_article']['pasteafter'],
            'attributes' => 'class="cl_paste"'
        );

        $GLOBALS['TL_DCA']['tl_article']['list']['operations']['cl_paste_after'] = array_merge(
                $GLOBALS['CLIPBOARD']['pasteafter'], $GLOBALS['TL_DCA']['tl_article']['list']['operations']['cl_paste_after']
        );
    }
}

?>