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
 * Class Clipboard
 */
class Clipboard extends Backend
{

    /**
     * Current object instance (Singleton)
     * 
     * @var Clipboard
     */
    protected static $objInstance = NULL;

    /**
     * Necessary objects
     * 
     * @var object 
     */
    protected $objHelper;
    protected $objDatabase;
    protected $objClipboardXml;

    /**
     * Prevent constructing the object (Singleton)
     */
    protected function __construct()
    {
        parent::__construct();

        $this->import('BackendUser', 'User');

        $this->objHelper = ClipboardHelper::getInstance();
        $this->objDatabase = ClipboardDatabase::getInstance();
        $this->objClipboardXml = ClipboardXml::getInstance();

        if ($this->Input->get('table') == 'tl_content')
        {
            $this->pageType = 'content';
        }
        else
        {
            $this->pageType = $this->Input->get('do');
        }
    }

    /**
     * Prevent cloning of the object (Singleton)
     */
    final private function __clone()
    {
        
    }

    /**
     * Get instanz of the object (Singelton) 
     *
     * @return Clipboard 
     */
    public static function getInstance()
    {
        if (self::$objInstance == NULL)
        {
            self::$objInstance = new Clipboard();
        }
        return self::$objInstance;
    }

    /**
     * Add the Clipboard to the backend template
     * 
     * HOOK: $GLOBALS['TL_HOOKS']['outputBackendTemplate']
     * 
     * @param string $strContent
     * @param string $strTemplate
     * @return string 
     */
    public function outputBackendTemplate($strContent, $strTemplate)
    {
        if ($strTemplate == 'be_main')
        {
            $objTemplate = new BackendTemplate('be_clipboard');

            $arrClipboard = $this->objDatabase->getCurrentClipboard($this->pageType, $this->User->id)->fetchAllAssoc();

            foreach ($arrClipboard AS $key => $value)
            {
                $arrClipboard[$key]['favorite_href'] = $this->addToUrl('key=cl_favor&amp;cl_id=' . $value['id']);
                $arrClipboard[$key]['delete_href'] = $this->addToUrl('key=cl_delete&amp;cl_id=' . $value['id']);
            }

            $objTemplate->clipboard = $arrClipboard;
            $objTemplate->action = $this->Environment->request . '&key=cl_edit';

            $strNewContent = preg_replace("/<div.*id=\"container\".*>/", $objTemplate->parse() . "\n$0", $strContent, 1);

            if ($strNewContent == "")
            {
                return $strContent;
            }
            else
            {
                $strContent = $strNewContent;
            }
        }

        return $strContent;
    }

    /**
     * Return the current favorite as object
     * 
     * @param type $strTable
     * @return DB_Mysql_Result 
     */
    public function getFavorite($strTable)
    {
        return $this->objDatabase->getFavorite($strTable, $this->User->id);
    }

    /**
     * Return the title for the given id
     * 
     * @param integer $id
     * @param string $do
     * @return string 
     */
    public function getTitleForId($intId)
    {
        switch ($this->pageType)
        {
            case 'page':
            case 'article':
                return call_user_func_array(array($this->objDatabase, 'get' . $this->pageType . 'Object'), array($intId))->title;
            default:
                return $GLOBALS['TL_LANG']['MSC']['noClipboardTitle'];
        }
    }

    /**
     * Copy element to clipboard 
     */
    public function copy()
    {
        $arrSet = array(
            'user_id' => $this->User->id,
            'childs' => $childs = (($this->Input->get('childs') == 1) ? 1 : 0),
            'str_table' => 'tl_' . $this->pageType,
            'title' => $this->getTitleForId($this->Input->get('id')),
            'elem_id' => $this->Input->get('id'),
        );

        //FB::log($this->objClipboardXml->writeXml($strTable, $strElemId, $strTitle));        
        $this->objDatabase->copyToClipboard($arrSet);
    }

    /**
     * Delete the entry with the given id
     * 
     * @param integer $intId 
     */
    public function delete($intId)
    {
        $this->objDatabase->deleteFromClipboard($intId, $this->User->id);
    }

    /**
     * Make the given id favorit
     * 
     * @param integer $intId 
     */
    public function favor($intId)
    {
        $this->objDatabase->setNewFavorite($intId, $this->pageType, $this->User->id);
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
                $this->objDatabase->editClipboardEntry($strTitle, $id, $this->User->id);
            }
        }
    }    

    /**
     * Return bool true if the clipboard is active and have entries for active page and user
     * 
     * @return boolean 
     */
    public function isClipboard()
    {
        if (count($this->objDatabase->getCurrentClipboard($this->pageType, $this->User->id)->fetchAllAssoc()) > 0)
        {
            return TRUE;
        }

        return FALSE;
    }

    /**
     * Handle all main operations, clean up the url and redirect to itself 
     */
    public function init()
    {
        // Handle the set get params
        if (stristr($this->Input->get('key'), 'cl_'))
        {
            $arrUnsetParams = array();
            foreach (array_keys($_GET) AS $strGetParam)
            {
                switch ($strGetParam)
                {
                    case 'key':
                        switch ($this->Input->get($strGetParam))
                        {
                            // Set new favorite
                            case 'cl_favor':
                                if (strlen($this->Input->get('cl_id')))
                                {
                                    $this->favor($this->Input->get('cl_id'));
                                }
                                break;

                            // Delete an element
                            case 'cl_delete':
                                if (strlen($this->Input->get('cl_id')))
                                {
                                    $this->delete($this->Input->get('cl_id'));
                                }
                                break;

                            // Edit Element
                            case 'cl_edit':
                                $arrTitles = $this->Input->post('title');
                                if (is_array($arrTitles))
                                {
                                    $this->edit($arrTitles);
                                }
                                break;

                            // Create new entry
                            case 'cl_copy':
                                $this->copy();
                                break;
                        }
                        $arrUnsetParams[$strGetParam] = $this->Input->get($strGetParam);
                        break;
                    case 'childs':
                    case 'act':
                    case 'mode':
                    case 'cl_id':
                        $arrUnsetParams[$strGetParam] = $this->Input->get($strGetParam);
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
                $objArticle = $this->objDatabase->getArticleObjectFromContentId($this->Input->get('id'));

                $strRequestWithoutId = str_replace(
                        substr($this->Environment->request, strpos($this->Environment->request, '&id')), '', $this->Environment->request
                );

                $this->redirect($strRequestWithoutId . '&id=' . $objArticle->id);
            }

            $this->redirect($this->Environment->request);
        }
    }

}
