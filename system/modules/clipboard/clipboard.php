<?php

if (!defined('TL_ROOT'))
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
 * Class clipboard 
 */
class clipboard extends Backend
{

    protected $strTable = 'tl_clipboard';
    protected $strTemplate = 'be_clipboard';
    protected $objClipboard;
    protected $pageType;

    /**
     * Initialize the object
     */
    protected function __construct()
    {
        parent::__construct();
        $this->import('BackendUser', 'User');
        $this->objClipboard = $this->Database
                ->prepare("SELECT * FROM `" . $this->strTable . "` WHERE `str_table` = %s AND `user_id` = ?")
                ->execute('tl_' . $this->pageType, $this->User->id);

        if ($this->Input->get('table') == 'tl_content')
        {
            $this->pageType = 'content';
        }
        else
        {
            $this->pageType = $this->Input->get('do');
            ;
        }
    }

    /**
     * Add the Clipboard to the backend template
     * HOOK: $GLOBALS['TL_HOOKS']['outputBackendTemplate']
     * 
     * @param type $strContent
     * @param type $strTemplate
     * @return string 
     */
    public function outputBackendTemplate($strContent, $strTemplate)
    {

        if ($strTemplate == 'be_main' && $this->Database->tableExists($this->strTable))
        {
            $arrContent = array(strstr($strContent, '<div id="container">', TRUE));

            $objTemplate = new BackendTemplate($this->strTemplate);

            $clipboard = $this->Database
                            ->prepare("SELECT * FROM `" . $this->strTable . "` WHERE `str_table` = %s AND `user_id` = ?")
                            ->execute('tl_' . $this->pageType, $this->User->id)->fetchAllAssoc();

            foreach ($clipboard AS $k => $v)
            {
                $clipboard[$k]['favorite_href'] = $this->addToUrl('key=cl_favor&amp;cl_id=' . $v['id']);
                $clipboard[$k]['delete_href'] = $this->addToUrl('key=cl_delete&amp;cl_id=' . $v['id']);
            }

            $objTemplate->clipboard = $clipboard;
            $objTemplate->action = $this->Environment->request . '&key=cl_edit';
            $arrContent[] = $objTemplate->parse();

            $arrContent[] = strstr($strContent, '<div id="container">');

            $strNewContent = "";
            foreach ($arrContent AS $content)
            {
                $newContent .= $content;
            }

            $strContent = $newContent;
        }

        return $strContent;
    }

    /**
     * Delete the entry with the given id
     * 
     * @param integer $intId 
     */
    public function delete($intId)
    {
        $this->Database
                ->prepare("DELETE FROM `" . $this->strTable . "` WHERE `id` = ? AND `user_id` = ?")
                ->execute($intId, $this->User->id);
    }

    /**
     * Make the given id favorit
     * 
     * @param integer $intId 
     */
    public function favor($intId)
    {
        $strTable = 'tl_' . $this->pageType;
        $this->Database
                ->prepare("UPDATE `" . $this->strTable . "` SET favorite = 0 WHERE str_table = ? AND `user_id` = ?")
                ->execute($strTable, $this->User->id);
        $this->Database
                ->prepare("UPDATE `" . $this->strTable . "` SET favorite = 1 WHERE id  = ? AND `user_id` = ?")
                ->execute($intId, $this->User->id);
    }

    /**
     * Return the current favorit element
     * 
     * @param string $strTable
     * @return array 
     */
    public function getFavorite($strTable)
    {
        $objDb = $this->Database
                ->prepare("SELECT * FROM " . $this->strTable . " WHERE str_table = ? AND favorite = 1 AND `user_id` = ?")
                ->limit(1)
                ->execute($strTable, $this->User->id);
        return $objDb;
    }

    /**
     * Override all given element titles in the clipboard view
     * 
     * @param array $arrTitles 
     */
    public function edit($arrTitles)
    {
        if (count($arrTitles) > 0)
        {
            foreach ($arrTitles AS $id => $strTitle)
            {
                $this->Database
                        ->prepare("UPDATE `" . $this->strTable . "` SET title = ? WHERE id = ? AND `user_id` = ?")
                        ->execute($strTitle, $id, $this->User->id);
            }
        }
    }

    /**
     * Copy element to clipboard 
     */
    public function copy()
    {
        $strTable = 'tl_' . $this->pageType;
        $strElemId = $this->Input->get('id');
        $strTitle = $this->getTitleForId($strElemId, $this->pageType);

        $childs = 0;
        if ($this->Input->get('childs') == 1)
        {
            $childs = 1;
        }
        $arrSet = array(
            'user_id' => $this->User->id,
            'childs' => $childs,
            'str_table' => $strTable,
            'title' => $strTitle,
            'elem_id' => $strElemId,
        );
        $this->Database
                ->prepare("UPDATE `" . $this->strTable . "` SET favorite = 0 WHERE str_table = ? AND `user_id` = ?")
                ->execute($strTable, $this->User->id);
        $this->Database
                ->prepare("INSERT INTO `" . $this->strTable . "` %s ON DUPLICATE KEY UPDATE favorite = 1")
                ->set($arrSet)
                ->execute();
    }

    /**
     * Return the title for the given id
     * 
     * @param integer $id
     * @param string $do
     * @return string 
     */
    public function getTitleForId($id, $do)
    {
        switch ($do)
        {
            case 'page':
            case 'article':
                $objResult = $this->Database
                        ->prepare("SELECT title FROM `tl_" . $do . "` WHERE id = ?")
                        ->execute($id);
                return $objResult->title;
            default:
                return $GLOBALS['TL_LANG']['MSC']['noClipboardTitle'];
        }
    }

    /**
     * Return bool true if the clipboard is active and have entries for active page and user
     * 
     * @return boolean 
     */
    public static function isClipboard()
    {
        if (Input::getInstance()->get('table') == 'tl_content')
        {
            $pageType = Input::getInstance()->get('table');
        }
        else
        {
            $pageType = 'tl_' . Input::getInstance()->get('do');
        }

        $objClipboard = Database::getInstance()
                ->prepare("SELECT * FROM `tl_clipboard` WHERE `str_table` = %s AND `user_id` = ?")
                ->execute($pageType, BackendUser::getInstance()->id);

        $arrClipboard = $objClipboard->fetchAllAssoc();

        if (count($arrClipboard) > 0)
        {
            return TRUE;
        }

        return FALSE;
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
    public function getPasteButton($row, $href, $label, $title, $icon, $attributes, $table)
    {
        return ($this->getFavorite($table)->numRows ? ($this->User->isAdmin || ($this->User->hasAccess($row['type'], 'alpty') && $this->User->isAllowed(2, $row))) ? '<a href="' . $this->addToUrl($href . '&amp;id=' . $this->getFavorite($table)->elem_id . '&amp;' . (($this->getFavorite($table)->childs == 1) ? 'childs=1&amp;' : '') . 'pid=' . $row['id']) . '" title="' . specialchars($title) . '"' . $attributes . '>' . $this->generateImage($icon, $label) . '</a> ' : $this->generateImage(preg_replace('/\.gif$/i', '_.gif', $icon)) . ' '  : '');
    }

    /**
     * Return some independently buttons
     * HOOK: $GLOBALS['TL_HOOKS']['independentlyButtons']
     * 
     * @param object $dc
     * @param array $row
     * @param string $table
     * @param boolean $cr
     * @param array $arrClipboard
     * @param childs $childs
     * @return string
     */
    public function independentlyButtons(DataContainer $dc, $row, $table, $cr, $arrClipboard = false, $childs)
    {
        if ($dc->table == 'tl_article' && $table == 'tl_page')
        {
            if ($this->pageType == 'content')
            {
                $label = $title = vsprintf($GLOBALS['TL_LANG'][$dc->table]['pasteafter'][1], array($this->getFavorite($dc->table)->elem_id));
            }
            else
            {
                $label = $title = vsprintf($GLOBALS['TL_LANG'][$dc->table]['pasteinto'][1], array($this->getFavorite($dc->table)->elem_id));
            }

            $return = $this->getPasteButton(
                    $row, $GLOBALS['CLIPBOARD']['pasteinto']['href'], $label, $title, $GLOBALS['CLIPBOARD']['pasteinto']['icon'], $GLOBALS['CLIPBOARD']['pasteinto']['attributes'], $dc->table
            );

            return $return;
        }
    }

    /**
     * Return button conainer as array
     * HOOK: $GLOBALS['TL_HOOKS']['independentlyTlContentHeaderButtons']
     * 
     * @param DataContainer $dc
     * @param DB_Mysql_Result $objParent
     * @param array $arrButton
     * @param string $ptable
     * @param string $table
     * @return array
     */
    public function independentlyTlContentHeaderButtons(DataContainer $dc, DB_Mysql_Result $objParent, $arrButton, $ptable, $table)
    {
        $arrNewButtons = array();

        foreach ($arrButton AS $key => $button)
        {
            if ($key == 'close')
            {
                $arrParent = $objParent->fetchAllAssoc();
                $row = $arrParent[0];
                $row['type'] = 'root';
                
                $label = $title = $GLOBALS['TL_LANG'][$dc->table]['cl_pastenew'][0];
                
                $arrNewButtons[] = $this->getPasteButton(
                        $row, $GLOBALS['CLIPBOARD']['pasteinto']['href'], $label, $title, $GLOBALS['CLIPBOARD']['pasteinto']['icon'], $GLOBALS['CLIPBOARD']['pasteinto']['attributes'], $dc->table
                );
            }
            $arrNewButtons[$key] = $button;
        }

        return $arrNewButtons;
    }

    /**
     * Handle all main operations, clean up the url and redirect to itself 
     */
    public function init()
    {
        $boolCl = FALSE;
        $key = $this->Input->get('key');
        if (strlen($key))
        {
            if (stristr($key, 'cl_'))
            {
                $boolCl = TRUE;
            }
        }
        if (is_array($_GET) && $this->Database->tableExists($this->strTable) && $boolCl)
        {
            $arrGetParams = array_keys($_GET);
            $arrUnsetParams = array();
            $intId = $this->Input->get('cl_id');
            foreach ($arrGetParams AS $strGetParam)
            {
                $strGetValue = $this->Input->get($strGetParam);
                switch ($strGetParam)
                {
                    case 'key':
                        switch ($strGetValue)
                        {
                            case 'cl_favor':
                                if (strlen($intId))
                                {
                                    $this->favor($intId);
                                }
                                break;
                            case 'cl_delete':
                                if (strlen($intId))
                                {
                                    $this->delete($intId);
                                }
                                break;
                            case 'cl_edit':
                                $arrTitles = $this->Input->post('title');
                                if (is_array($arrTitles))
                                {
                                    $this->edit($arrTitles);
                                }
                                break;
                            case 'cl_copy':
                                $this->copy();
                                break;
                        }
                        $arrUnsetParams[$strGetParam] = $strGetValue;
                        break;
                    case 'childs':
                    case 'act':
                    case 'mode':
                    case 'cl_id':
                        $arrUnsetParams[$strGetParam] = $strGetValue;
                        break;
                }
            }

            foreach ($arrUnsetParams AS $k => $v)
            {
                $this->Input->setGet($k, NULL);
                $this->Environment->request = str_replace("&$k=$v", '', $this->Environment->request);
                $this->Environment->queryString = str_replace("&$k=$v", '', $this->Environment->queryString);
                $this->Environment->requestUri = str_replace("&$k=$v", '', $this->Environment->requestUri);
            }

            if ($arrUnsetParams['key'] == 'cl_copy' && $this->pageType == 'content')
            {
                $objArticle = $this->Database
                        ->prepare("
                            SELECT a.* 
                            FROM `tl_article` AS a
                            LEFT JOIN `tl_content` AS c
                            ON c.pid = a.id
                            WHERE c.id = ?")
                        ->limit(1)
                        ->execute($this->Input->get('id'));

                $strRequestWithoutId = str_replace(
                        substr($this->Environment->request, strpos($this->Environment->request, '&id')), '', $this->Environment->request
                );

                $this->redirect($strRequestWithoutId . '&id=' . $objArticle->id);
            }

            $this->redirect($this->Environment->request);
        }
    }

}

?>