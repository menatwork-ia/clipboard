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
 * Config 
 */
$GLOBALS['TL_DCA']['tl_content']['config']['onload_callback'][] = array('clipboard', 'init');
if (clipboard::isClipboard())
{
    $GLOBALS['TL_DCA']['tl_content']['config']['dataContainer'] = 'Clipboard';
}

/**
 * List operations 
 */
// Copy button
// Copy button
$GLOBALS['TL_DCA']['tl_content']['list']['operations']['cl_copy'] = array
    (
    'label' => &$GLOBALS['TL_LANG']['tl_content']['copy'],
    'button_callback' => array('tl_content_cl', 'copyContent')
);

$GLOBALS['TL_DCA']['tl_content']['list']['operations']['cl_copy'] = array_merge(
        $GLOBALS['CLIPBOARD']['copy'], $GLOBALS['TL_DCA']['tl_content']['list']['operations']['cl_copy']
);


// -----------------------------------------------------------------------------
// Paste after button
$GLOBALS['TL_DCA']['tl_content']['list']['operations']['cl_paste_after'] = array
    (
    'label' => $GLOBALS['TL_LANG']['tl_content']['cl_pasteafter'],
    'attributes' => 'class="cl_paste"',
    'button_callback' => array('tl_content_cl', 'cl_pasteContent')
);

$GLOBALS['TL_DCA']['tl_content']['list']['operations']['cl_paste_after'] = array_merge(
        $GLOBALS['CLIPBOARD']['pasteafter'], $GLOBALS['TL_DCA']['tl_content']['list']['operations']['cl_paste_after']
);

/**
 * Class tl_content
 *
 * Provide miscellaneous methods that are used by the data configuration array.
 * 
 * PHP version 5
 * @copyright  MEN AT WORK 2012
 * @package    clipboard
 * @license    GNU/GPL 2
 * @filesource
 */
class tl_content_cl extends tl_content
{

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
    public function cl_pasteContent($row, $href, $label, $title, $icon, $attributes, $table)
    {
        if ($GLOBALS['TL_DCA'][$table]['config']['closed'])
        {
            return '';
        }
        $this->import('clipboard');
        return $this->clipboard->getPasteButton($row, $href, $label, $title, $icon, $attributes, $table);
    }

    /**
     * Return the copy page button
     * @param array
     * @param string
     * @param string
     * @param string
     * @param string
     * @param string
     * @param string
     * @return string
     */
    public function copyContent($row, $href, $label, $title, $icon, $attributes, $table)
    {
        if ($GLOBALS['TL_DCA'][$table]['config']['closed'])
        {
            return '';
        }

        return ($this->User->isAdmin || ($this->User->hasAccess($row['type'], 'alpty') && $this->User->isAllowed(2, $row))) ? '<a href="' . $this->addToUrl($href . '&amp;id=' . $row['id']) . '" title="' . specialchars($title) . '"' . $attributes . '>' . $this->generateImage($icon, $label) . '</a> ' : $this->generateImage(preg_replace('/\.gif$/i', '_.gif', $icon)) . ' ';
    }

}

?>