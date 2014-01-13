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
 * Class ClipboardHelper
 */
class ClipboardHelper extends Backend
{

    /**
     * Current object instance (Singleton)
     * @var ClipboardHelper
     */
    protected static $_objInstance = NULL;

    /**
     * Contains specific database request
     * 
     * @var ClipboardDatabase
     */
    protected $_objDatabase;

    /**
     * Contains some string functions
     * 
     * @var String
     */
    protected $_objString;

    /**
     * Pagetype
     * 
     * @var string
     */
    protected $_strPageType;

    /**
     * Search for special chars
     * 
     * @var array 
     */
    public $arrSearchFor = array(
        "\\",
        "'"
    );

    /**
     * Replace special chars with
     * 
     * @var array 
     */
    public $arrReplaceWith = array(
        "\\\\",
        "\\'"
    );

    /**
     * Prevent constructing the object (Singleton)
     */
    protected function __construct()
    {
        parent::__construct();
        $this->import('BackendUser', 'User');
        $this->_objDatabase = ClipboardDatabase::getInstance();
        $this->_objString = String::getInstance();
        $this->_setPageType();
        
    }

    /**
     * Prevent cloning of the object (Singleton)
     */
    final private function __clone(){}

    /**
     * Get instanz of the object (Singelton) 
     *
     * @return ClipboardHelper 
     */
    public static function getInstance()
    {
        if (self::$_objInstance == NULL)
        {
            self::$_objInstance = new ClipboardHelper();
        }
        return self::$_objInstance;
    }

    /**
     * Get the current pagetype
     * 
     * @return string
     */
    public function getPageType()
    {
        return $this->_strpageType;
    }

    /**
     * Get the page type as database name
     * 
     * @return string
     */
    public function getDbPageType()
    {
        return 'tl_' . $this->_strpageType;
    }

    /**
     * Set the current page type 
     */
    protected function _setPageType()
    {
        if ($this->Input->get('table') == 'tl_content')
        {
            $this->_strpageType = 'content';
        }
        elseif ($this->Input->get('table') == 'tl_module')
        {
            $this->_strpageType = 'module';
        }
        else
        {
            $this->_strpageType = $this->Input->get('do');
        }
    }

    /**
     * Return if context checkbox is true or false
     * 
     * @return boolean
     */
    public function isContext()
    {
        return !$this->User->clipboard_context;
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
        $boolFavorit = Clipboard::getInstance()->cb()->hasFavorite();

        if ($boolFavorit)
        {
            $return = '';
            if ($this->User->isAdmin || ($this->User->hasAccess($row['type'], 'alpty') && $this->User->isAllowed(2, $row)))
            {
                // Create link
                $return .= vsprintf('<a href="%s" title="%s" %s>%s</a>', array(
                    // Create URL
                    $this->addToUrl(
                            vsprintf('%s&amp;id=%s', array(
                                $href,
                                $row['id']
                                    )
                            )
                    ),
                    specialchars($title),
                    $attributes,
                    // Create linkimage
                    $this->generateImage($icon, $label)
                        )
                );
            }
            else
            {
                // Create image
                $return .= $this->generateImage(preg_replace('/\.gif$/i', '_.gif', $icon)) . ' ';
            }
            return $return;
        }
        else
        {
            return '';
        }
    }

    /**
     * Return clipboard button
     * 
     * HOOK: $GLOBALS['TL_HOOKS']['clipboardButtons']
     * 
     * @param object $dc
     * @param array $row
     * @param string $table
     * @param boolean $cr
     * @param array $arrClipboard
     * @param childs $childs
     * @return string
     */
    public function clipboardButtons(DataContainer $dc, $row, $table, $cr, $arrClipboard = false, $childs)
    {
        if(!$this->Input->get('act'))
        {
            $objFavorit = Clipboard::getInstance()->cb()->getFavorite();

            if ($dc->table == 'tl_article' && $table == 'tl_page')
            {
                // Create button title and lable
                $label = $title = vsprintf($GLOBALS['TL_LANG'][$dc->table]['pasteinto'][1], array(
                    $row['id']
                        )
                );

                // Create Paste Button
                $return = $this->getPasteButton(
                        $row, $GLOBALS['CLIPBOARD']['pasteinto']['href'], $label, $title, $GLOBALS['CLIPBOARD']['pasteinto']['icon'], $GLOBALS['CLIPBOARD']['pasteinto']['attributes'], $dc->table
                );

                return $return;
            }
        }
    }
    
    public function clipboardActSelectButtons(DataContainer $dc)
    {        
        return '<input id="cl_multiCopy" class="tl_submit" type="submit" value="' . $GLOBALS['TL_LANG']['MSC']['groupSelected'] . '" name="cl_group">';
    }
    
    /**
     * Write a message to the customer in TL_INFO and the log
     * 
     * @param string $strMessage
     */
    public function writeCustomerInfoMessage($strMessage)
    {
        $this->log($strMessage, __FUNCTION__, TL_GENERAL);
        if(version_compare(VERSION, '2.11', '>='))
        {
            $this->addInfoMessage($strMessage);
        }
        else
        {
            $strType = 'TL_INFO';
            if (!is_array($_SESSION[$strType]))
            {
                $_SESSION[$strType] = array();
            }

            $_SESSION[$strType][] = $strMessage;            
        }
    }
    
    /**
     * Create title for content element and return it as string. If no title 
     * exists return content element as object.
     * 
     * @param integer $intId
     * @param boolean $booClGroup
     * @return DB_Mysql_Result|string
     */
    public function createContentTitle($intId, $booClGroup)
    {
        $objContent = $this->_objDatabase->getContentObject($intId);
        $strHeadline = $this->_getHeadlineValue($objContent);      
        
        $arrTitle = array();
        
        switch ($objContent->type)
        {
            case 'headline':
            case 'gallery':
            case 'downloads':
                if($strHeadline) $arrTitle['title'] = $strHeadline;
                break;
                
            case 'text':
                if($strHeadline) $arrTitle['title'] = $strHeadline;
                elseif($objContent->text) $arrTitle['title'] = $objContent->text;
                break;
                
            case 'html':
                if($objContent->html) $arrTitle['title'] = $objContent->html;             
                break;
                
            case 'list':
                $arrList = deserialize($objContent->listitems);
                if($strHeadline) $arrTitle['title'] = $strHeadline;
                elseif($arrList[0]) $arrTitle['title'] = $arrList[0];
                break;
                
            case 'table':                
                $arrTable = deserialize($objContent->tableitems);
                if($strHeadline) $arrTitle['title'] = $strHeadline;
                elseif($arrTable[0][0]) $arrTitle['title'] = $arrTable[0][0];
                break;
                
            case 'accordion':
                if($objContent->mooHeadline) $arrTitle['title'] = $objContent->mooHeadline;
                break;
                
            case 'code':
                if($strHeadline) $arrTitle['title'] = $strHeadline;
                elseif($objContent->code) $arrTitle['title'] = $objContent->code;
                break;
                
            case 'hyperlink':
            case 'download':
                if($strHeadline) $arrTitle['title'] = $strHeadline;
                elseif($objContent->linkTitle) $arrTitle['title'] = $objContent->linkTitle;
                break;
                
            case 'toplink':
                if($objContent->linkTitle) $arrTitle['title'] = $objContent->linkTitle;
                break;
                
            case 'image':
                if($strHeadline) $arrTitle['title'] = $strHeadline;
                elseif($objContent->caption) $arrTitle['title'] = $objContent->caption;
                elseif($objContent->alt) $arrTitle['title'] = $objContent->alt;
                break;               
                
            default:

                // HOOK: call the hooks for clipboardContentTitle
                if (isset($GLOBALS['TL_HOOKS']['clipboardContentTitle']) && is_array($GLOBALS['TL_HOOKS']['clipboardContentTitle']))
                {
                    foreach ($GLOBALS['TL_HOOKS']['clipboardContentTitle'] as $arrCallback)
                    {
                        $this->import($arrCallback[0]);                        
                        $strTmpTitle = $this->$arrCallback[0]->$arrCallback[1]($this, $strHeadline, $objContent, $booClGroup);
                        if($strTmpTitle !== false && !is_null($strTmpTitle)) 
                        {
                            $arrTitle['title'] = $strTmpTitle;
                            break;
                        }
                    }
                }
                
                break;
        }
        
        if(!$arrTitle['title']) return $objContent;
        
        $arrTitle['attribute'] = $GLOBALS['TL_LANG']['CTE'][$objContent->type][0];
        
        return $arrTitle;
    }
    
    protected function _getHeadlineValue($objElem)
    {
        $arrHeadline = deserialize($objElem->headline, true);
        if ($arrHeadline['value'])
        {
            return $arrHeadline['value'];
        }
        return false;
    }

    /**
     * Get field array with all fields from given array
     * 
     * @param string $strTable
     * @return array 
     */
    public function getFields($strTable)
    {
        $arrTableMetaFields = $this->getTableMetaFields($strTable);
        $arrFields = array();
        foreach ($arrTableMetaFields AS $key => $value)
        {
            $arrFields[] = $key;
        }
        return $arrFields;
    }

    /**
     * Write the field information from the given table string to an array and return it
     * 
     * @param string $strTable
     * @return array 
     */
    public function getTableMetaFields($strTable)
    {
        $fields = $this->_objDatabase->getFields($strTable);

        $arrFieldMeta = array();

        foreach ($fields as $value)
        {
            if ($value["type"] == "index")
            {
                continue;
            }

            $arrFieldMeta[$value["name"]] = $value;
        }

        return $arrFieldMeta;
    }

    /**
     * Get pid and new sorting for new element
     * 
     * @param string $strTable
     * @param int $intPid
     * @param string $strPastePos
     * @return array
     */
    public function getNewPosition($strTable, $intPid, $strPastePos)
    {
        // Insert the current record at the beginning when inserting into the parent record
        if ($strPastePos == 'pasteInto')
        {
            $newPid = $intPid;
            $objSorting = $this->_objDatabase->getSorting($strTable, $intPid);

            // Select sorting value of the first record
            if ($objSorting->numRows)
            {
                $intCurSorting = $objSorting->sorting;

                // Resort if the new sorting value is not an integer or smaller than 1
                if (($intCurSorting % 2) != 0 || $intCurSorting < 1)
                {
                    $objNewSorting = $this->_objDatabase->getSortingElem($strTable, $intPid);

                    $count = 2;
                    $newSorting = 128;

                    while ($objNewSorting->next())
                    {
                        $this->_objDatabase->updateSorting($strTable, ($count++ * 128), $objNewSorting->id);
                    }
                }

                // Else new sorting = (current sorting / 2)
                else
                    $newSorting = ($intCurSorting / 2);
            }

            // Else new sorting = 128
            else
                $newSorting = 128;
        }
        // Else insert the current record after the parent record
        elseif ($strPastePos == 'pasteAfter' && $intPid > 0)
        {
            $objSorting = $this->_objDatabase->getDynamicObject($strTable, $intPid);

            // Set parent ID of the current record as new parent ID
            if ($objSorting->numRows)
            {
                $newPid = $objSorting->pid;
                $intCurSorting = $objSorting->sorting;

                // Do not proceed without a parent ID
                if (is_numeric($newPid))
                {
                    $objNextSorting = $this->_objDatabase->getNextSorting($strTable, $newPid, $intCurSorting);

                    // Select sorting value of the next record
                    if ($objNextSorting->sorting !== null)
                    {
                        $intNextSorting = $objNextSorting->sorting;

                        // Resort if the new sorting value is no integer or bigger than a MySQL integer
                        if ((($intCurSorting + $intNextSorting) % 2) != 0 || $intNextSorting >= 4294967295)
                        {
                            $count = 1;

                            $objNewSorting = $this->_objDatabase->getSortingElem($strTable, $newPid);

                            while ($objNewSorting->next())
                            {
                                $this->_objDatabase->updateSorting($strTable, ($count++ * 128), $objNewSorting->id);

                                if ($objNewSorting->sorting == $intCurSorting)
                                {
                                    $newSorting = ($count++ * 128);
                                }
                            }
                        }

                        // Else new sorting = (current sorting + next sorting) / 2
                        else
                            $newSorting = (($intCurSorting + $intNextSorting) / 2);
                    }

                    // Else new sorting = (current sorting + 128)
                    else
                        $newSorting = ($intCurSorting + 128);
                }
            }

            // Use the given parent ID as parent ID
            else
            {
                $newPid = $intPid;
                $newSorting = 128;
            }
        }

        return array('pid' => intval($newPid), 'sorting' => intval($newSorting));
    }

    /**
     * Check if the content type exists in this system and return true or false 
     * 
     * @param array $arrSet
     * @return boolean 
     */
    public function existsContentType($arrSet)
    {
        foreach ($GLOBALS['TL_CTE'] AS $group => $arrCElems)
        {
            foreach ($arrCElems AS $strCType => $strCDesc)
            {
                if (substr($arrSet['type'], 1, -1) == $strCType)
                {
                    return TRUE;
                }
            }
        }
        return FALSE;
    }
    
    /**
     * Check if the module type exists in this system and return true or false 
     * 
     * @param array $arrSet
     * @return boolean 
     */    
    public function existsModuleType($arrSet)
    {
        foreach ($GLOBALS['FE_MOD'] AS $group => $arrMElems)
        {
            foreach ($arrMElems AS $strMType => $strMDesc)
            {
                if (substr($arrSet['type'], 1, -1) == $strMType)
                {
                    return TRUE;
                }
            }
        }
        return FALSE;
    }
    
    /**
     * Return given file from path without file extension as array
     * 
     * @param string $strFilePath
     * @return array
     */
    public function getArrFromFileName($strFilePath)
    {
        $this->loadLanguageFile('default');
        $arrFileInfo = pathinfo($strFilePath);
        
        $arrFileName = explode(
            ',', 
            $arrFileInfo['filename']
        );
        
        // Add groupflag for older xml filenames
        if(!in_array($arrFileName[4], array('G', 'NG')))
        {
            $arrFileName[5] = $arrFileName[4];
            $arrFileName[4] = ((stristr($arrFileName[5], standardize($GLOBALS['TL_LANG']['MSC']['clipboardGroup'])) === FALSE) ? 'NG' : 'G');
        }
        
        return $arrFileName;
    }

}

?>