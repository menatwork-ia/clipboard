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
    protected static $_objInstance = NULL;

    /**
     * Contains some helper functions
     * 
     * @var object 
     */
    protected $_objHelper;

    /**
     * Contains all xml specific functions and all informations to the 
     * clipboard elements 
     * 
     * @var ClipboardXml
     */
    protected $_objCbXml;

    /**
     * Contains specific database request
     * 
     * @var ClipboardDatabase
     */
    protected $_objDatabase;

    /**
     * Prevent constructing the object (Singleton)
     */
    protected function __construct()
    {
        parent::__construct();
        $this->import('BackendUser', 'User');
        $this->_objHelper = ClipboardHelper::getInstance();
        $this->_objCbXml = ClipboardXml::getInstance();
        $this->_objDatabase = ClipboardDatabase::getInstance();
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
        if (self::$_objInstance == NULL)
        {
            self::$_objInstance = new Clipboard();
        }
        return self::$_objInstance;
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

            $arrClipboard = $this->cb()->getElements();

            $objTemplate->clipboard = $arrClipboard;
            $objTemplate->isContext = $this->_objHelper->isContext();
            $objTemplate->action = $this->Environment->request . '&key=cl_edit';

            if (!$this->_objHelper->isContext())
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
     * Return boolean if the clipboard is for given dca and user allowed
     * 
     * @param string $dca
     * @return boolean 
     */
    public function isClipboard($dca = NULL)
    {
        $arrAllowedLocations = $GLOBALS['CLIPBOARD']['locations'];

        if ($dca == NULL || !isset($GLOBALS['CLIPBOARD']['locations']) || !$this->User->clipboard)
        {
            return FALSE;
        }

        if (in_array($dca, $arrAllowedLocations))
        {
            if (TL_MODE == 'BE' && in_array($this->_objHelper->getPageType(), $arrAllowedLocations))
            {
                return TRUE;
            }
        }
        return FALSE;
    }

    /**
     * Prepare context if is set or disable context
     */
    public function prepareContext()
    {
        if (!$this->_objHelper->isContext())
        {
            foreach ($GLOBALS['CLIPBOARD'] AS $key => $value)
            {
                if (array_key_exists('attributes', $GLOBALS['CLIPBOARD'][$key]))
                {
                    $GLOBALS['CLIPBOARD'][$key]['attributes'] = 'onclick="Backend.getScrollOffset();"';
                }
            }
        }
    }

    /**
     * Get clipboard container object
     * 
     * @return ClipboardXml
     */
    public function cb()
    {
        return $this->_objCbXml;
    }

    /**
     * Paste favorite into
     */
    public function pasteInto()
    {
        $this->cb()->read('pasteInto', $this->Input->get('id'));
    }

    /**
     * Paste favorite after 
     */
    public function pasteAfter()
    {
        $this->cb()->read('pasteAfter', $this->Input->get('id'));
    }

    /**
     * Delete the given element
     * 
     * @param string $hash
     */
    public function delete($hash)
    {
        $this->cb()->deleteFile($hash);
    }

    /**
     * Make the given element favorit
     * 
     * @param string $hash 
     */
    public function favor($hash)
    {
        $this->cb()->setFavor($hash);
    }

    /**
     * Rename all given clipboard titles
     * 
     * @param array $arrTitles 
     */
    public function edit($arrTitles)
    {
        $this->cb()->editTitle($arrTitles);
    }

    /**
     * Return the title for the given id
     * 
     * @param integer $intId
     * @param boolean $boolChilds
     * @return string 
     */
    public function getTitle($intId, $boolChilds)
    {
        switch ($this->_objHelper->getPageType())
        {
            case 'page':
                $objElem = $this->_objDatabase->getPageObject($intId);
                return (($boolChilds) ? $objElem->title . ' ' . $GLOBALS['TL_LANG']['MSC']['titleChild'] : $objElem->title);
                break;
            case 'article':
                return call_user_func_array(array($this->_objDatabase, 'get' . $this->_objHelper->getPageType() . 'Object'), array($intId))->title;
                break;

            case 'content':
                $strTitel = $this->_objHelper->createContentTitle($intId);
                if (!is_null($strTitel))
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
        $boolHasChilds = (($this->Input->get('childs') == 1) ? 1 : 0);

        $arrSet = array(
            'user_id' => $this->User->id,
            'childs' => $boolHasChilds,
            'table' => $this->_objHelper->getDbPageType(),
            'elem_id' => $this->Input->get('id'),
            'title' => $this->getTitle($this->Input->get('id'), $boolHasChilds)
        );

        $this->cb()->write($arrSet);
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

            if (in_array($arrUnsetParams['key'], $arrUnsetKeyParams) && $this->_objHelper->getPageType() == 'content')
            {
                $objArticle = $this->_objDatabase->getArticleObjectFromContentId($this->Input->get('id'));

                $strRequestWithoutId = str_replace(
                        substr($this->Environment->request, strpos($this->Environment->request, '&id')), '', $this->Environment->request
                );

                $this->redirect($strRequestWithoutId . '&id=' . $objArticle->id);
            }

            $this->redirect($this->Environment->request);
        }
    }

}
