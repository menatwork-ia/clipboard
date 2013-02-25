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
     * Contains some string functions
     * 
     * @var String
     */
    protected $_objString;    

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
        $this->_objString = String::getInstance();
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
        $this->Session->set('clipboardExt', array('readXML' => FALSE));
        
        if ($strTemplate == 'be_main' && $this->User->clipboard && $this->cb()->hasElements())
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

            return preg_replace('/<div.*id="container".*>/', $objTemplate->parse() . "\n$0", $strContent, 1);
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
     * @param mixed $mixedId
     * @param boolean $boolChilds
     * @return string 
     */
    public function getTitle($mixedId)
    {
        $arrTitle = array();
        
        $booClGroup = FALSE;
        
        if(is_array($mixedId))
        {
            $booClGroup = TRUE;
        }
        
        switch ($this->_objHelper->getPageType())
        {
            case 'page':              
                if($booClGroup)
                {
                    $mixedId = $mixedId[0];
                }
                $objElem = $this->_objDatabase->getPageObject($mixedId);
                $arrTitle = array('title' => $objElem->title);
                break;
            
            case 'article':
                $arrTitle = array('title' => call_user_func_array(array($this->_objDatabase, 'get' . $this->_objHelper->getPageType() . 'Object'), array($mixedId))->title);
                break;

            case 'content':
                if(!$booClGroup)
                {                
                    $mixedTitle = $this->_objHelper->createContentTitle($mixedId, $booClGroup);
                    if (!is_object($mixedTitle) && is_array($mixedTitle))
                    {
                        $arrTitle = $mixedTitle;
                    }
                    else
                    {     
                        $arrTitle = array(
                            'title' => $GLOBALS['TL_LANG']['MSC']['noClipboardTitle'],
                            'attribute' => $GLOBALS['TL_LANG']['CTE'][$mixedTitle->type][0]
                        );
                    }
                }
                else
                {
                    $strTitle = '';
                    foreach($mixedId AS $intId)
                    {
                        $mixedTitle = $this->_objHelper->createContentTitle($intId, $booClGroup);                        
                        if (!is_object($mixedTitle) && is_array($mixedTitle))
                        {
                            $strTitle = $mixedTitle['title'];
                            break;
                        }
                    }
                    
                    if(strlen($strTitle) > 0)
                    {
                        $arrTitle = array('title' => $strTitle);
                    }
                    else
                    {
                        $arrTitle = array('title' => $GLOBALS['TL_LANG']['MSC']['noClipboardTitle']);
                    }
                }
                break;
            case 'module':
                $objElem = $this->_objDatabase->getModuleObject($mixedId);
                $arrTitle = array('title' => $objElem->name);
                break;                
                
            default:
                $arrTitle = array('title' => $GLOBALS['TL_LANG']['MSC']['noClipboardTitle']);
        }
        
        $arrTitle['title'] = $this->_objString->substr($arrTitle['title'], '24');
        
        return $arrTitle;
    }

    /**
     * Copy element to clipboard and write xml
     */
    public function copy($booClGroup = FALSE, $ids = array())
    {
        $arrSet = array(
            'user_id' => $this->User->id,
            'table' => $this->_objHelper->getDbPageType()            
        );
        
        if($booClGroup == TRUE && count($ids) > 1)
        {
            $arrSet['childs']   = 0;
            $arrSet['elem_id']  = $ids;
            $arrSet['grouped']    = TRUE;
            $arrSet['groupCount'] = count($ids);
            $arrSet = array_merge($arrSet,$this->getTitle($ids));
        }
        else
        {
            if(count($ids) == 1)
            {
                $intId = $ids[0];
            }
            else
            {
                $intId = $this->Input->get('id');
            }
            
            $arrSet['childs']   = (($this->Input->get('childs') == 1) ? 1 : 0);
            $arrSet['elem_id']  = $intId;
            $arrSet['grouped']    = FALSE;
            $arrSet['groupCount'] = 0;
            $arrSet = array_merge($arrSet, $this->getTitle($intId));
        }
        
        if(!$arrSet['attribute']) $arrSet['attribute'] = '';

        $this->cb()->write($arrSet);
    }

    /**
     * Handle all main operations, clean up the url and redirect to itself 
     */
    public function init()
    {
        $arrSession = $this->Session->get('clipboardExt');
        
        if($arrSession['readXML'])
        {
            return;
        }
        
        if (stristr($this->Input->get('key'), 'cl_') || $this->Input->post('FORM_SUBMIT') == 'tl_select' && isset($_POST['cl_group']))
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
                    case 'act':
                        if($this->Input->get('key') != 'cl_delete')
                        {                        
                            // Copy multi edit elements to clipboard
                            $ids = deserialize($this->Input->post('IDS'));

                            if (!is_array($ids) || empty($ids))
                            {
                                $this->reload();
                            }

                            $this->copy(TRUE, $ids);
                            $arrUnsetParams[$strGetParam] = $this->Input->get($strGetParam);
                        }
                        break;
                    case 'childs':
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
            else if(in_array($arrUnsetParams['key'], $arrUnsetKeyParams) && $this->_objHelper->getPageType() == 'module')
            {
                $objTheme = $this->_objDatabase->getThemeObjectFromModuleId($this->Input->get('id'));
                
                $strRequestWithoutId = str_replace(
                        substr($this->Environment->request, strpos($this->Environment->request, '&id')), '', $this->Environment->request
                );

                $this->redirect($strRequestWithoutId . '&id=' . $objTheme->id);
            }
            
            $this->redirect($this->Environment->request);
        }
    }

}
