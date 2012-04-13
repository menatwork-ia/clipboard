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
    final private function __clone(){}

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
        if ($strTemplate == 'be_main' && $this->User->clipboard)
        {
            $objTemplate = new BackendTemplate('be_clipboard');

            $arrClipboard = $this->objDatabase->getCurrentClipboard($this->pageType, $this->User->id)->fetchAllAssoc();

            foreach ($arrClipboard AS $key => $value)
            {
                $arrClipboard[$key]['favorite_href'] = $this->addToUrl('key=cl_favor&amp;cl_id=' . $value['id']);
                $arrClipboard[$key]['delete_href'] = $this->addToUrl('key=cl_delete&amp;cl_id=' . $value['id']);
            }

            $objTemplate->clipboard = $arrClipboard;
            $objTemplate->isContext = $this->objHelper->isContext();
            $objTemplate->action = $this->Environment->request . '&key=cl_edit';

            if(!$this->objHelper->isContext())
            {
                $strContent = preg_replace('/<body.*class="/', "$0clipboard ", $strContent, 1);
            }
            
            $strNewContent = preg_replace('/<div.*id="container".*>/', $objTemplate->parse() . "\n$0", $strContent, 1);
            
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
    public function getTitle($intId)
    {
        switch ($this->pageType)
        {
            case 'page':
            case 'article':
                return call_user_func_array(array($this->objDatabase, 'get' . $this->pageType . 'Object'), array($intId))->title;
                break;
            
            case 'content':
                $strTitel = $this->objHelper->createContentTitle($intId);
                if(!is_null($strTitel))
                {
                    return $strTitel;
                }
                    
            default:
                return $GLOBALS['TL_LANG']['MSC']['noClipboardTitle'];
        }
    }

    /**
     * Copy element to clipboeard and write xml
     */
    public function copy()
    {
        $boolHasChilds = (($this->Input->get('childs') == 1) ? TRUE : FALSE);

        $arrSet = array(
            'user_id' => $this->User->id,
            'childs' => $boolHasChilds,
            'str_table' => 'tl_' . $this->pageType,
            'elem_id' => $this->Input->get('id'),
        );

        $objClipboard = $this->objDatabase->getClipboardEntryFromElemId($this->pageType, $this->User->id, $this->Input->get('id'));

        if ($objClipboard->numRows && $objClipboard->filename != '' && $this->objClipboardXml->fileExists($objClipboard->filename))
        {
            $arrSet['title'] = $objClipboard->title;
            $arrSet['filename'] = $objClipboard->filename;
        }
        else
        {
            $arrSet['title'] = $this->getTitle($this->Input->get('id'));
            $arrSet['filename'] = $this->objClipboardXml->getFileName('tl_' . $this->pageType, $this->getTitle($this->Input->get('id')), FALSE);
        }

        $this->objDatabase->copyToClipboard($arrSet);
        $this->objClipboardXml->writeXml('tl_' . $this->pageType, $this->Input->get('id'), $arrSet['title'], $arrSet['filename'], $boolHasChilds);
    }
    
    /**
     * Paste favorite into
     */
    public function pasteInto()
    {
        $objFavorite = $this->getFavorite('tl_' . $this->pageType);
        $this->objClipboardXml->readXml($objFavorite->filename, 'pasteInto', $this->Input->get('id'));
    }
    
    /**
     * Paste favorite after 
     */
    public function pasteAfter()
    {
        $objFavorite = $this->getFavorite('tl_' . $this->pageType);
        $this->objClipboardXml->readXml($objFavorite->filename, 'pasteAfter', $this->Input->get('id'));
    }
    

    /**
     * Delete the given element and remove xml
     * 
     * @param integer $intId 
     */
    public function delete($intId)
    {
        $objClipboard = $this->objDatabase->getClipboardEntryFromId($intId);
        
        $this->objDatabase->deleteFromClipboard($intId, $this->User->id);

        $this->objClipboardXml->deleteFile($objClipboard->filename);
    }

    /**
     * Make the given element favorit
     * 
     * @param integer $intId 
     */
    public function favor($intId)
    {
        $this->objDatabase->setNewFavorite($intId, $this->pageType, $this->User->id);
    }

    /**
     * Override all given element titles in the clipboard view and 
     * edit and rename xml file
     * 
     * @param array $arrTitles 
     */
    public function edit($arrTitles)
    {
        if (count($arrTitles) > 0)
        {
            foreach ($arrTitles AS $id => $strTitle)
            {                
                $objClipboard = $this->objDatabase->getClipboardEntryFromId($id);

                $strFilename = $this->objClipboardXml->updateFileNameTitle($objClipboard->filename, $strTitle);

                $this->objDatabase->editClipboardElemTitle($strTitle, $id, $this->User->id, $strFilename);

                $this->objClipboardXml->editTitle($objClipboard->filename, $strFilename, $strTitle);
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
     * Return if the current clipboard has a favorite
     * 
     * @return boolean 
     */
    public function hasFavorite()
    {
        $objFavorite = $this->getFavorite('tl_' . $this->pageType);
        
        if($objFavorite->numRows == 0)
        {
            return FALSE;
        }
        return TRUE;
    }

    /**
     * Handle all main operations, clean up the url and redirect to itself 
     */
    public function init()
    {
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
                            
                            case 'cl_header_pastenew':
                            case 'cl_paste_into':                                
                                $this->pasteInto();
                                break;
                            
                            case 'cl_paste_after':
                                $this->pasteAfter();
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

            $arrUnsetKeyParams = array(
                'cl_copy',
                'cl_paste_into',
                'cl_paste_after'
            );

            if (in_array($arrUnsetParams['key'], $arrUnsetKeyParams) && $this->pageType == 'content')
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
