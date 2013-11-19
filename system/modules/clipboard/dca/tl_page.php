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
if (Clipboard::getInstance()->isClipboard('page'))
{
    /**
     * Prepare clipboard contextmenu 
     */
    Clipboard::getInstance()->prepareContext();    
    
    /**
     * Config 
     */
    $GLOBALS['TL_DCA']['tl_page']['config']['onload_callback'][] = array('Clipboard', 'init');
    
    $GLOBALS['TL_DCA']['tl_page']['config']['dataContainer'] = 'Clipboard';

    /**
     * List operations 
     */
    // Copy button
    $GLOBALS['TL_DCA']['tl_page']['list']['operations']['cl_copy'] = array
        (
        'label' => &$GLOBALS['TL_LANG']['tl_page']['copy']
    );

    $GLOBALS['TL_DCA']['tl_page']['list']['operations']['cl_copy'] = array_merge(
            $GLOBALS['CLIPBOARD']['copy'], $GLOBALS['TL_DCA']['tl_page']['list']['operations']['cl_copy']
    );

    // -----------------------------------------------------------------------------
    // Copy with childs button
    $GLOBALS['TL_DCA']['tl_page']['list']['operations']['cl_copyChilds'] = array
        (
        'label' => &$GLOBALS['TL_LANG']['tl_page']['copyChilds'],
        'button_callback' => array('tl_page_cl', 'cl_copyPageWithSubpages')
    );

    $GLOBALS['TL_DCA']['tl_page']['list']['operations']['cl_copyChilds'] = array_merge(
            $GLOBALS['CLIPBOARD']['copy_childs'], $GLOBALS['TL_DCA']['tl_page']['list']['operations']['cl_copyChilds']
    );

    if(Clipboard::getInstance()->cb()->hasFavorite())
    {    
        // -----------------------------------------------------------------------------
        // Paste after button
        $GLOBALS['TL_DCA']['tl_page']['list']['operations']['cl_paste_after'] = array
            (
            'label' => $GLOBALS['TL_LANG']['tl_page']['pasteafter'],
            'attributes' => 'class="cl_paste"'
        );

        $GLOBALS['TL_DCA']['tl_page']['list']['operations']['cl_paste_after'] = array_merge(
                $GLOBALS['CLIPBOARD']['pasteafter'], $GLOBALS['TL_DCA']['tl_page']['list']['operations']['cl_paste_after']
        );

        // -----------------------------------------------------------------------------
        // Paste into button
        $GLOBALS['TL_DCA']['tl_page']['list']['operations']['cl_paste_into'] = array
            (
            'label' => $GLOBALS['TL_LANG']['tl_page']['pasteinto'],
            'attributes' => 'class="cl_paste"'
        );

        $GLOBALS['TL_DCA']['tl_page']['list']['operations']['cl_paste_into'] = array_merge(
                $GLOBALS['CLIPBOARD']['pasteinto'], $GLOBALS['TL_DCA']['tl_page']['list']['operations']['cl_paste_into']
        );
    }
}

/**
 * Class tl_page_cl
 *
 * Provide miscellaneous methods that are used by the data configuration array.
 * 
 * PHP version 5
 * @copyright  MEN AT WORK 2012
 * @package    clipboard
 * @license    GNU/GPL 2
 * @filesource
 */
class tl_page_cl extends tl_page
{
    /**
     * Return the copy page with subpages button
     * 
     * @param array $row
     * @param string $href
     * @param string $label
     * @param string $title
     * @param string $icon
     * @param string $attributes
     * @param string $table
     * @return string
     */
    public function cl_copyPageWithSubpages($row, $href, $label, $title, $icon, $attributes, $table)
    {
        if ($GLOBALS['TL_DCA'][$table]['config']['closed'])
        {
            return '';
        }

        $objSubpages = $this->Database->prepare("SELECT * FROM `tl_page` WHERE pid=?")
                ->limit(1)
                ->execute($row['id']);

        return ($objSubpages->numRows && ($this->User->isAdmin || ($this->User->hasAccess($row['type'], 'alpty') && $this->User->isAllowed(2, $row)))) ? '<a href="' . $this->addToUrl($href . '&amp;id=' . $row['id']) . '" title="' . specialchars($title) . '"' . $attributes . '>' . $this->generateImage($icon, $label) . '</a> ' : '';
    }

}

?>