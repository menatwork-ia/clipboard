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
if (ClipboardHelper::getInstance()->isClipboardReadyToUse('page'))
{
    /**
     * Config 
     */
    $GLOBALS['TL_DCA']['tl_page']['config']['onload_callback'][] = array('Clipboard', 'init');

    /**
     * List operations 
     */
    // Copy button
    $GLOBALS['TL_DCA']['tl_page']['list']['operations']['cl_copy'] = array
        (
        'label' => &$GLOBALS['TL_LANG']['tl_page']['copy'],
        'button_callback' => array('tl_page', 'copyPage')
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

    // -----------------------------------------------------------------------------
    // Paste after button
    $GLOBALS['TL_DCA']['tl_page']['list']['operations']['cl_paste_after'] = array
        (
        'label' => $GLOBALS['TL_LANG']['tl_page']['pasteafter'],
        'attributes' => 'class="cl_paste"',
//        'button_callback' => array('tl_page_cl', 'cl_pastePage')
    );

    $GLOBALS['TL_DCA']['tl_page']['list']['operations']['cl_paste_after'] = array_merge(
            $GLOBALS['CLIPBOARD']['pasteafter'], $GLOBALS['TL_DCA']['tl_page']['list']['operations']['cl_paste_after']
    );

    // -----------------------------------------------------------------------------
    // Paste into button
    $GLOBALS['TL_DCA']['tl_page']['list']['operations']['cl_paste_into'] = array
        (
        'label' => $GLOBALS['TL_LANG']['tl_page']['pasteinto'],
        'attributes' => 'class="cl_paste"',
//        'button_callback' => array('tl_page_cl', 'cl_pastePage')
    );

    $GLOBALS['TL_DCA']['tl_page']['list']['operations']['cl_paste_into'] = array_merge(
            $GLOBALS['CLIPBOARD']['pasteinto'], $GLOBALS['TL_DCA']['tl_page']['list']['operations']['cl_paste_into']
    );
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
     * Initialize the object
     */
    public function __construct()
    {
        parent::__construct();
        $this->import('ClipboardHelper');
    }

    /**
     * Return the paste button
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
    public function cl_pastePage($row, $href, $label, $title, $icon, $attributes, $table)
    {
        if ($GLOBALS['TL_DCA'][$table]['config']['closed'])
        {
            return '';
        }
        return $this->ClipboardHelper->getPasteButton($row, $href, $label, $title, $icon, $attributes, $table);
    }

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

        $objSubpages = $this->Database->prepare("SELECT * FROM tl_page WHERE pid=?")
                ->limit(1)
                ->execute($row['id']);

        return ($objSubpages->numRows && ($this->User->isAdmin || ($this->User->hasAccess($row['type'], 'alpty') && $this->User->isAllowed(2, $row)))) ? '<a href="' . $this->addToUrl($href . '&amp;id=' . $row['id']) . '" title="' . specialchars($title) . '"' . $attributes . '>' . $this->generateImage($icon, $label) . '</a> ' : '';
    }

}

?>