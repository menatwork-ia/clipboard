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
 * @license    GNU/GPL 2
 * @filesource
 */

/**
 * Create DCA if clipboard is ready to use 
 */
if (ClipboardHelper::getInstance()->isClipboardReadyToUse('article'))
{
    /**
     * Config 
     */
    $GLOBALS['TL_DCA']['tl_article']['config']['onload_callback'][] = array('clipboard', 'init');

    if (Clipboard::getInstance()->isClipboard())
    {
        $GLOBALS['TL_DCA']['tl_article']['config']['dataContainer'] = 'Clipboard';
    }

    /**
     * List operations 
     */
    // Copy button
    $GLOBALS['TL_DCA']['tl_article']['list']['operations']['cl_copy'] = array
        (
        'label' => &$GLOBALS['TL_LANG']['tl_article']['copy'],
        'attributes' => 'class="clipboardmenu" onclick="Backend.getScrollOffset();"',
        'button_callback' => array('tl_article_cl', 'copyArticle')
    );

    $GLOBALS['TL_DCA']['tl_article']['list']['operations']['cl_copy'] = array_merge(
            $GLOBALS['CLIPBOARD']['copy'], $GLOBALS['TL_DCA']['tl_article']['list']['operations']['cl_copy']
    );

    // -----------------------------------------------------------------------------
    // Paste after button
    $GLOBALS['TL_DCA']['tl_article']['list']['operations']['cl_paste_after'] = array
        (
        'label' => &$GLOBALS['TL_LANG']['tl_article']['pasteafter'],
        'href' => '&amp;act=copy&amp;mode=1',
        'icon' => 'pasteafter.gif',
        'attributes' => 'class="cl_paste"',
        'button_callback' => array('tl_article_cl', 'cl_pasteArticle')
    );
}

/**
 * Class tl_article_cl
 *
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * PHP version 5
 * @copyright  MEN AT WORK 2012
 * @package    clipboard
 * @license    GNU/GPL 2
 * @filesource
 */
class tl_article_cl extends tl_article
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
    public function cl_pasteArticle($row, $href, $label, $title, $icon, $attributes, $table)
    {
        if ($GLOBALS['TL_DCA'][$table]['config']['closed'])
        {
            return '';
        }       
        return $this->ClipboardHelper->getPasteButton($row, $href, $label, $title, $icon, $attributes, $table);
    }

}

?>