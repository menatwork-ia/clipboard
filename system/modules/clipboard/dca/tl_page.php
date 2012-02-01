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
$GLOBALS['TL_DCA']['tl_page']['config']['onload_callback'][] = array('clipboard', 'init');

$GLOBALS['TL_DCA']['tl_page']['list']['operations']['cl_copy'] = array
    (
    'label' => &$GLOBALS['TL_LANG']['tl_page']['copy'],
    'href' => 'key=cl_copy',
    'icon' => 'copy.gif',
    'attributes' => 'class="clipboardmenu" onclick="Backend.getScrollOffset();"',
    'button_callback' => array('tl_page', 'copyPage')
);

$GLOBALS['TL_DCA']['tl_page']['list']['operations']['cl_copyChilds'] = array
    (
    'label' => &$GLOBALS['TL_LANG']['tl_page']['copyChilds'],
    'href' => 'key=cl_copy&amp;childs=1',
    'icon' => 'copychilds.gif',
    'attributes' => 'class="cl_paste" onclick="Backend.getScrollOffset();"',
    'button_callback' => array('tl_page_cl', 'copyPageWithSubpages')
);

$GLOBALS['TL_DCA']['tl_page']['list']['operations']['cl_paste_after'] = array
    (
    'label' => &$GLOBALS['TL_LANG']['tl_page']['pasteafter'],
    'href' => '&amp;act=copy&amp;mode=1',
    'icon' => 'pasteafter.gif',
    'attributes' => 'class="cl_paste"',
    'button_callback' => array('tl_page_cl', 'pastPage')
);

$GLOBALS['TL_DCA']['tl_page']['list']['operations']['cl_paste_into'] = array
    (
    'label' => &$GLOBALS['TL_LANG']['tl_page']['pasteinto'],
    'href' => '&amp;act=copy&amp;mode=2',
    'icon' => 'pasteinto.gif',
    'attributes' => 'class="cl_paste"',
    'button_callback' => array('tl_page_cl', 'pastPage')
);

class tl_page_cl extends tl_page
{

    public function pastPage($row, $href, $label, $title, $icon, $attributes, $table)
    {
        if ($GLOBALS['TL_DCA'][$table]['config']['closed'])
        {
            return '';
        }
        $this->import('clipboard');
        $arrFavorite = $this->clipboard->getFavorite($table);
        return (is_array($arrFavorite) ? ($this->User->isAdmin || ($this->User->hasAccess($row['type'], 'alpty') && $this->User->isAllowed(2, $row))) ? '<a href="' . $this->addToUrl($href . '&amp;id=' . $arrFavorite['elem_id'] . '&amp;' . (($arrFavorite['childs'] == 1) ? 'childs=1&amp;' : '') . 'pid=' . $row['id']) . '" title="' . specialchars($title) . '"' . $attributes . '>' . $this->generateImage($icon, $label) . '</a> ' : $this->generateImage(preg_replace('/\.gif$/i', '_.gif', $icon)) . ' '  : '');
    }

    public function copyPageWithSubpages($row, $href, $label, $title, $icon, $attributes, $table)
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