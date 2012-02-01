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
class clipboard extends Backend
{

    private $strTable = 'tl_clipboard';
    private $strTemplate = 'be_clipboard';

    /**
     * Load database object
     */
    protected function __construct()
    {
        parent::__construct();
        $this->import('BackendUser', 'User');
    }

    /**
     * Add the Clipboard to the backend template
     * 
     * @param type $strContent
     * @param type $strTemplate
     * @return type 
     */
    public function outputBackendTemplate($strContent, $strTemplate)
    {

        if ($strTemplate == 'be_main' && $this->Database->tableExists($this->strTable))
        {
            $arrContent = array(strstr($strContent, '<div id="container">', TRUE));

            $objTemplate = new BackendTemplate($this->strTemplate);
            $objClipboard = $this->Database
                    ->prepare("SELECT * FROM `" . $this->strTable . "` WHERE `str_table` = %s AND `user_id` = ?")
                    ->execute('tl_' . $this->Input->get('do'), $this->User->id);
            $clipboard = $objClipboard->fetchAllAssoc();

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

    public function delete($intId)
    {
        $this->Database
                ->prepare("DELETE FROM `" . $this->strTable . "` WHERE `id` = ? AND `user_id` = ?")
                ->execute($intId, $this->User->id);
    }

    public function favor($intId)
    {
        $strTable = 'tl_' . $this->Input->get('do');
        $this->Database
                ->prepare("UPDATE `" . $this->strTable . "` SET favorite = 0 WHERE str_table = ? AND `user_id` = ?")
                ->execute($strTable, $this->User->id);
        $this->Database
                ->prepare("UPDATE `" . $this->strTable . "` SET favorite = 1 WHERE id  = ? AND `user_id` = ?")
                ->execute($intId, $this->User->id);
    }

    public function getFavorite($strTable)
    {
        $objDb = $this->Database
                ->prepare("SELECT * FROM " . $this->strTable . " WHERE str_table = ? AND favorite = 1 AND `user_id` = ?")
                ->execute($strTable, $this->User->id);
        return $objDb->fetchAssoc();
    }

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

    public function copy()
    {
        $strTable = 'tl_' . $this->Input->get('do');
        $strElemId = $this->Input->get('id');        
        $strTitle = $this->getTitleForId($strElemId, $this->Input->get('do'));
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
    
    public function getTitleForId($id, $do)
    {
        switch ($do)
        {
            case 'page':                
                $objResult = $this->Database
                    ->prepare("SELECT title FROM `tl_" . $do . "` WHERE id = ?")
                    ->execute($id);
                return $objResult->title;
            default:
                return $GLOBALS['TL_LANG']['MSC']['noClipboardTitle'];
        }
    }

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
        }
    }

}

?>