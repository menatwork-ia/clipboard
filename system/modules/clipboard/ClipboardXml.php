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
 * Class ClipboardXml 
 */
class ClipboardXml extends Backend
{

    /**
     * Current object instance (Singleton)
     * @var ClipboardXml
     */
    protected static $_objInstance;

    /**
     * Objects
     */
    protected $_objBeUser;
    protected $_objDatabase;
    protected $_objFiles;

    /**
     * Variables 
     */
    protected $_strPageTable = 'tl_page';
    protected $_strArticleTable = 'tl_article';
    protected $_strContentTable = 'tl_content';

    /**
     * Prevent constructing the object (Singleton)
     */
    protected function __construct()
    {
        $this->_objBeUser = BackendUser::getInstance();
        $this->_objDatabase = ClipboardDatabase::getInstance();
        $this->_objFiles = Files::getInstance();
    }

    /**
     * Prevent cloning of the object (Singleton)
     */
    final private function __clone()
    {
        
    }

    /**
     * @return ClipboardXml
     */
    public static function getInstance()
    {
        if (!is_object(self::$_objInstance))
        {
            self::$_objInstance = new ClipboardXml();
        }
        return self::$_objInstance;
    }

    //--------------------------------------------------------------------------
    // File and Folder Methods
    //--------------------------------------------------------------------------

    /**
     * Return path to xml file for current backend user
     * 
     * @return string
     */
    public function getFolderPath()
    {
        return $GLOBALS['TL_CONFIG']['uploadPath'] . '/' . 'clipboard' . '/' . $this->_objBeUser->username;
    }

    /**
     * Create filename and return it. You can allow to overwrite the file by
     * setting the third parameter to TRUE
     * 
     * @param string $strPageType
     * @param string $strTitle
     * @param boolean $strOverwrite
     * @return string 
     */
    public function getFileName($strPageType, $strTitle, $boolOverwrite = FALSE)
    {
        $strFilename = substr($strPageType, 3) . '_' . time() . '_' . standardize(strtolower($strTitle)) . '.xml';
        if ($this->fileExists($strFilename) && $boolOverwrite)
        {
            return $this->doNotOverwrite($strFilename);
        }

        return $strFilename;
    }

    /**
     * Replace title from given filename with new given title
     * 
     * @param string $strOldFilename
     * @param string $strNewTitle
     * @return string 
     */
    public function updateFileNameTitle($strOldFilename, $strNewTitle)
    {
        $arrOldFileName = explode('_', $strOldFilename);
        $arrNewFileName = array(
            $arrOldFileName[0],
            $arrOldFileName[1],
            standardize(strtolower($strNewTitle))
        );

        return implode('_', $arrNewFileName) . '.xml';
    }

    /**
     * Check if the given file exists and return TRUE or FALSE
     * 
     * @param string $strFilename
     * @return boolean 
     */
    public function fileExists($strFilename)
    {
        if (file_exists(TL_ROOT . '/' . $this->getFolderPath() . '/' . $strFilename))
        {
            return TRUE;
        }
        else
        {
            return FALSE;
        }
    }

    /**
     * Delete the given file
     * 
     * @param string $strFilename 
     */
    public function deleteFile($strFilename)
    {
        if ($this->fileExists($strFilename))
        {
            $this->_objFiles->delete($this->getFolderPath() . '/' . $strFilename);
        }
    }

    /**
     * Edit the title tag in the given file and rename 
     * 
     * @param string $strFilename
     * @param string $strTitle
     * @return boolean 
     */
    public function editTitle($strOldFilename, $strNewFilename, $strTitle)
    {
        if ($this->fileExists($strOldFilename))
        {
            $objDomDoc = new DOMDocument();
            $objDomDoc->load(TL_ROOT . '/' . $this->getFolderPath() . '/' . $strOldFilename);
            $nodeTitle = $objDomDoc->getElementsByTagName('metatags')->item(0)->getElementsByTagName('title')->item(0);
            $nodeTitle->nodeValue = $strTitle;
            $strDomDoc = $objDomDoc->saveXML();

            $objUserFolder = new Folder($this->getFolderPath());
            $objFile = new File($objUserFolder->value . '/' . $strOldFilename);
            $write = $objFile->write($strDomDoc);
            if ($write)
            {
                $objFile->close;
                return $this->_objFiles->rename($objUserFolder->value . '/' . $strOldFilename, $objUserFolder->value . '/' . $strNewFilename);
            }
        }
        return FALSE;
    }

    /**
     * Check all filenames and return new with suffix if exists
     * 
     * @param string $strFilename
     * @return string 
     */
    private function doNotOverwrite($strFilename)
    {
        $arrInfo = pathinfo(TL_ROOT . '/' . $this->getFolderPath() . '/' . $strFilename);

        $offset = 1;
        $arrAll = scan(TL_ROOT . '/' . $this->getFolderPath());
        $arrFiles = preg_grep('/^' . preg_quote($arrInfo['filename'], '/') . '.*\.' . preg_quote($arrInfo['extension'], '/') . '/', $arrAll);

        foreach ($arrFiles as $strFile)
        {
            if (preg_match('/__[0-9]+\.' . preg_quote($arrInfo['extension'], '/') . '$/', $strFile))
            {
                $strFile = str_replace('.' . $arrInfo['extension'], '', $strFile);
                $intValue = intval(substr($strFile, (strrpos($strFile, '_') + 1)));
                $offset = max($offset, $intValue);
            }
        }

        return $arrInfo['filename'] . '__' . ++$offset . '.' . $arrInfo['extension'];
    }
    
    /**
     * Return all metainformation for all xml files from current user
     * 
     * @return array
     */
    public function getAllFileMetaInformation()
    {
        $arrAll = scan(TL_ROOT . '/' . $this->getFolderPath());
        
        $arrMetaTags = array();
        if(is_array($arrAll) && count($arrAll) > 0)
        {            
            foreach($arrAll AS $strFilename)
            {
                $arrSet = array('filename' => $strFilename);

                $objDomDoc = new DOMDocument();
                $objDomDoc->load(TL_ROOT . '/' . $this->getFolderPath() . '/' . $strFilename);
                $objMetaTags = $objDomDoc->getElementsByTagName('metatags')->item(0);            
                $objMetaChilds = $objMetaTags->childNodes;

                for($i = 0; $i < $objMetaChilds->length; $i++)
                {
                    $strNodeName = $objMetaChilds->item($i)->nodeName;
                    switch ($strNodeName)
                    {
                        case 'title':
                            $arrSet[$strNodeName] = $objMetaChilds->item($i)->nodeValue;
                            break;

                        case 'childs':
                            $arrSet[$strNodeName] = $objMetaChilds->item($i)->nodeValue;
                            break;

                        case 'str_table':
                            $arrSet[$strNodeName] = $objMetaChilds->item($i)->nodeValue;
                            break;
                    }
                }
                $arrMetaTags[] = $arrSet;
            }
        }
        return $arrMetaTags;
    }

    //--------------------------------------------------------------------------
    // Write to xml
    //--------------------------------------------------------------------------    

    /**
     * 
     * 
     * @param string $strTable
     * @param integer $intId
     * @param string $strTitle
     * @return boolean 
     */

    /**
     * Create xml file for the given element and all his childs
     * 
     * @param string $strTable
     * @param integer $intId
     * @param string $strTitle
     * @param string $strFilename
     * @param boolean $boolHasChilds
     * @return booleana 
     */
    public function writeXml($strTable, $intId, $strTitle, $strFilename, $boolHasChilds = FALSE)
    {
        // Create XML File
        $objXml = new XMLWriter();
        $objXml->openMemory();
        $objXml->setIndent(TRUE);
        $objXml->setIndentString("\t");

        // XML Start
        $objXml->startDocument('1.0', 'UTF-8');
        $objXml->startElement('clipboard');

        // Write meta (header)        
        $objXml->startElement('metatags');
        $objXml->writeElement('create_unix', time());
        $objXml->writeElement('create_date', date('Y-m-d', time()));
        $objXml->writeElement('create_time', date('H:i', time()));
        $objXml->writeElement('title', $strTitle);
        $objXml->writeElement('childs', (($boolHasChilds) ? 1 : 0));
        $objXml->writeElement('str_table', $strTable);
        $objXml->endElement(); // End metatags

        $objXml->startElement('datacontainer');
        switch ($strTable)
        {
            case 'tl_page':
                $this->writePage($intId, $objXml, $boolHasChilds);
                break;
            case 'tl_article':
                $this->writeArticle($intId, $objXml, FALSE);
                break;
            case 'tl_content':
                $this->writeContent($intId, $objXml, FALSE);
                break;
        }
        $objXml->endElement(); // End datacontainer

        $objXml->endElement(); // End clipboard

        $strXml = $objXml->outputMemory();

        $objUserFolder = new Folder($this->getFolderPath());
        $objFile = new File($objUserFolder->value . '/' . $strFilename);
        $write = $objFile->write($strXml);
        if ($write)
        {
            $objFile->close;
            return TRUE;
        }
        return FALSE;
    }

    /**
     * Write page informations to xml object
     * 
     * @param integer $intId
     * @param XMLWriter $objXml
     * @param bool $boolChilds
     * @return XMLWriter 
     */
    protected function writePage($intId, $objXml, $boolHasChilds)
    {
        $objXml->startElement('page');
        $objXml->writeAttribute('table', $this->_strPageTable);

        $arrPageRows = $this->_objDatabase->getPageObject($intId)->fetchAllAssoc();

        $this->writeGivenDbTableRows($this->_strPageTable, $arrPageRows, $objXml);

        $objXml->startElement('articles');
        $this->writeArticle($intId, $objXml, TRUE);
        $objXml->endElement(); // End articles

        if ($boolHasChilds)
        {
            $this->writeSubpages($intId, $objXml);
        }

        $objXml->endElement(); // End page
    }

    /**
     * Write subpage informations to xml object
     * 
     * @param type $intId
     * @param XMLWriter $objXml
     */
    protected function writeSubpages($intId, $objXml)
    {
        $arrPageRows = $this->_objDatabase->getSubpagesObject($intId)->fetchAllAssoc();

        if (count($arrPageRows) > 0)
        {
            $objXml->startElement('subpage');
            $objXml->writeAttribute('table', $this->_strPageTable);

            foreach ($arrPageRows AS $arrRow)
            {
                $this->writeGivenDbTableRows($this->_strPageTable, array($arrRow), $objXml);

                $objXml->startElement('articles');
                $this->writeArticle($arrRow['id'], $objXml, TRUE);
                $objXml->endElement(); // End articles

                $this->writeSubpages($arrRow['id'], $objXml);
            }
            $objXml->endElement(); // End subpage
        }
    }

    /**
     * Write article informations to xml object
     * 
     * @param integer $intId
     * @param XMLWriter $objXml
     * @param boolean $boolIsChild
     */
    protected function writeArticle($intId, $objXml, $boolIsChild)
    {
        if ($boolIsChild)
        {
            $arrRows = $this->_objDatabase->getArticleObjectFromPid($intId)->fetchAllAssoc();
        }
        else
        {
            $arrRows = $this->_objDatabase->getArticleObject($intId)->fetchAllAssoc();
        }

        if (count($arrRows) < 1)
        {
            return;
        }

        $objXml->startElement('article');
        $objXml->writeAttribute('table', $this->_strArticleTable);

        foreach ($arrRows AS $arrRow)
        {
            $this->writeGivenDbTableRows($this->_strArticleTable, array($arrRow), $objXml);

            $this->writeContent($arrRow['id'], $objXml, TRUE);
        }

        $objXml->endElement(); // End article
    }

    /**
     * Write content informations to xml object
     * 
     * @param integer $intId
     * @param XMLWriter $objXml
     * @param boolean $boolIsChild 
     */
    protected function writeContent($intId, $objXml, $boolIsChild)
    {
        if ($boolIsChild)
        {
            $arrRows = $this->_objDatabase->getContentObjectFromPid($intId)->fetchAllAssoc();
        }
        else
        {
            $arrRows = $this->_objDatabase->getContentObject($intId)->fetchAllAssoc();
        }

        if (count($arrRows) < 1)
        {
            return;
        }

        $objXml->startElement('content');
        $objXml->writeAttribute('table', $this->_strContentTable);

        $this->writeGivenDbTableRows($this->_strContentTable, $arrRows, $objXml);

        $objXml->endElement(); // End content
    }

    /**
     * Write the given database rows to the xml object
     * 
     * @param string $strTable
     * @param array $arrRows
     * @param XMLWriter $objXml 
     */
    protected function writeGivenDbTableRows($strTable, $arrRows, $objXml)
    {
        $arrFieldMeta = $this->getTableMetaFields($strTable);

        if (count($arrRows) > 0)
        {
            foreach ($arrRows AS $row)
            {
                $objXml->startElement('row');

                foreach ($row as $field_key => $field_data)
                {
                    switch ($field_key)
                    {
                        case 'id':
                        case 'pid':
                            break;
                        default:
                            $objXml->startElement('field');
                            $objXml->writeAttribute("name", $field_key);

                            if (!isset($field_data))
                            {
                                $objXml->writeAttribute("type", "null");
                                $objXml->writeCdata("NULL");
                            }
                            else if ($field_data != "")
                            {
                                switch (strtolower($arrFieldMeta[$field_key]['type']))
                                {
                                    case 'blob':
                                    case 'tinyblob':
                                    case 'mediumblob':
                                    case 'longblob':
                                        $objXml->writeAttribute("type", "blob");
                                        $objXml->writeCdata("0x" . bin2hex($field_data));
                                        break;

                                    case 'smallint':
                                    case 'int':
                                        $objXml->writeAttribute("type", "int");
                                        $objXml->writeCdata($field_data);
                                        break;

                                    case 'text':
                                    case 'mediumtext':
                                        if (strpos($field_data, "'") != false)
                                        {
                                            $objXml->writeAttribute("type", "text");
                                            $objXml->writeCdata("0x" . bin2hex($field_data));
                                            break;
                                        }
                                    default:
                                        $objXml->writeAttribute("type", "default");
                                        $objXml->writeCdata("'" . str_replace(array("\\", "'", "\r", "\n"), array("\\\\", "\\'", "\\r", "\\n"), $field_data) . "'");
                                        break;
                                }
                            }
                            else
                            {
                                $objXml->writeAttribute("type", "empty");
                                $objXml->writeCdata("''");
                            }

                            $objXml->endElement(); // End field                            
                            break;
                    }
                }

                $objXml->endElement(); // End row            
            }
        }
    }

    //--------------------------------------------------------------------------
    // Table field information
    //--------------------------------------------------------------------------    

    /**
     * Write the field information from the given table string to an array and
     * return it
     * 
     * @param string $strTable
     * @return array 
     */
    protected function getTableMetaFields($strTable)
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
     * Get field array with all fields from given array
     * 
     * @param string $strTable
     * @return array 
     */
    protected function getFields($strTable)
    {
        $arrTableMetaFields = $this->getTableMetaFields($strTable);
        $arrFields = array();
        foreach ($arrTableMetaFields AS $key => $value)
        {
            $arrFields[] = $key;
        }
        return $arrFields;
    }

    //--------------------------------------------------------------------------
    // Read xml and write it into database
    //--------------------------------------------------------------------------

    /**
     * Read xml file and create elements
     * 
     * @param string $strFilename
     * @param string $strPastePos
     * @param integer $intElemId 
     */
    public function readXml($strFilename, $strPastePos, $intElemId)
    {
        if ($this->fileExists($strFilename))
        {
            $objXml = new XMLReader();
            $objXml->open(TL_ROOT . '/' . $this->getFolderPath() . '/' . $strFilename);
            while ($objXml->read())
            {
                switch ($objXml->nodeType)
                {
                    case XMLReader::ELEMENT:
                        switch ($objXml->localName)
                        {
                            case 'page':
                                $this->createPage($objXml, $objXml->getAttribute("table"), $strPastePos, $intElemId);
                                break;
                        
                            case 'article':
                                $this->createArticle($objXml, $objXml->getAttribute("table"), $strPastePos, $intElemId);
                                break;
                            
                            case 'content':
                                $this->createContent($objXml, $objXml->getAttribute("table"), $strPastePos, $intElemId);
                                break;
                            
                            default:
                                break;
                        }
                        break;
                    default:
                        break;
                }
            }
            $objXml->close();
        }
    }

    /**
     * Create page elements
     * 
     * @param XMLReader $objXml
     * @param string $strTable
     * @param string $strPastePos
     * @param integer $intElemId
     * @param bool $boolIsChild
     */    
    public function createPage($objXml, $strTable, $strPastePos, $intElemId, $boolIsChild = FALSE)
    {
        $intLastInsertId = 0;
        
        if ($boolIsChild == TRUE)
        {
            $intId = $intElemId;
        }
        else
        {
            if ($strPastePos == 'pasteAfter')
            {
                $objElem = $this->_objDatabase->getPageObject($intElemId);
                $intId = $objElem->pid;
            }
        }        

        while ($objXml->read())
        {
            switch ($objXml->nodeType)
            {
                case XMLReader::ELEMENT:
                    switch ($objXml->localName)
                    {
                        case 'article':
                            $this->createArticle($objXml, $objXml->getAttribute("table"), $strPastePos, $intLastInsertId, TRUE);
                            break;
                        
                        case 'row':
                            $objDb = $this->_objDatabase->insertInto($strTable, $this->createArrSetForRow($objXml, $intId, $strTable, $strPastePos, $intElemId, $boolIsChild));
                            $intLastInsertId = $objDb->insertId;
                            break;
                        
                        case 'subpage':
                            $this->createPage($objXml, $objXml->getAttribute("table"), $strPastePos, $intLastInsertId, TRUE);
                    }
                    break;
                case XMLReader::END_ELEMENT:
                    switch ($objXml->localName)
                    {
                        case 'page':
                            return;
                            break;
                    }
                    break;
            }
        }        
    }


    /**
     * Create article elements
     * 
     * @param XMLReader $objXml
     * @param string $strTable
     * @param string $strPastePos
     * @param integer $intElemId
     * @param bool $boolIsChild
     */
    public function createArticle($objXml, $strTable, $strPastePos, $intElemId, $boolIsChild = FALSE)
    {
        $intLastInsertId = 0;

        if ($boolIsChild == TRUE)
        {
            $intId = $intElemId;
        }
        else
        {
            if ($strPastePos == 'pasteAfter')
            {
                $objElem = $this->_objDatabase->getContentObject($intElemId);
                $intId = $objElem->pid;
            }
        }

        while ($objXml->read())
        {
            switch ($objXml->nodeType)
            {
                case XMLReader::ELEMENT:
                    switch ($objXml->localName)
                    {
                        case 'content':
                            $this->createContent($objXml, $objXml->getAttribute("table"), $strPastePos, $intLastInsertId, TRUE);
                            break;
                        
                        case 'row':
                            $objDb = $this->_objDatabase->insertInto($strTable, $this->createArrSetForRow($objXml, $intId, $strTable, $strPastePos, $intElemId, $boolIsChild));
                            $intLastInsertId = $objDb->insertId;
                            break;
                    }
                    break;
                case XMLReader::END_ELEMENT:
                    switch ($objXml->localName)
                    {
                        case 'article':
                            return;
                            break;
                    }
                    break;
            }
        }
    }

    /**
     * Create Content elements
     * 
     * @param XMLReader $objXml
     * @param string $strTable
     * @param string $strPastePos
     * @param integer $intElemId
     * @param bool $boolIsChild
     */
    protected function createContent($objXml, $strTable, $strPastePos, $intElemId, $boolIsChild = FALSE)
    {
        if ($boolIsChild == TRUE)
        {
            $intId = $intElemId;
        }
        else
        {
            if ($strPastePos == 'pasteAfter')
            {
                $objElem = $this->_objDatabase->getContentObject($intElemId);
                $intId = $objElem->pid;
            }
        }

        while ($objXml->read())
        {
            switch ($objXml->nodeType)
            {
                case XMLReader::ELEMENT:
                    switch ($objXml->localName)
                    {
                        case 'row':
                            $this->_objDatabase->insertInto($strTable, $this->createArrSetForRow($objXml, $intId, $strTable, $strPastePos, $intElemId, $boolIsChild));
                            break;
                    }
                    break;
                case XMLReader::END_ELEMENT:
                    switch ($objXml->localName)
                    {
                        case 'content':
                            return;
                            break;
                    }
                    break;
            }
        }
    }

    /**
     * Create array set for insert query
     * 
     * @param XMLReader $objXml
     * @param integer $intId
     * @param string $strTable
     * @param string $strPastePos
     * @param integer $intElemId
     * @param bool $boolIsChild
     * @return array
     */
    protected function createArrSetForRow($objXml, $intId, $strTable, $strPastePos, $intElemId, $boolIsChild = FALSE)
    {
        $arrFields = $this->getFields($strTable);
        $arrSet = array();
        $strFieldType = '';
        $strFieldName = '';

        while ($objXml->read())
        {
            switch ($objXml->nodeType)
            {
                case XMLReader::CDATA:
                    if (in_array($strFieldName, $arrFields))
                    {
                        switch ($strFieldName)
                        {
                            case 'pid':
                            case 'id':
                                break;

                            case 'sorting':
                                if ($boolIsChild == TRUE)
                                {
                                    $arrSet['pid'] = $intId;
                                }
                                else
                                {
                                    $arrSorting = $this->getNewPosition($strTable, $intElemId, $strPastePos);
                                    $arrSet['pid'] = $arrSorting['pid'];
                                    $arrSet['sorting'] = $arrSorting['sorting'];
                                    break;
                                }

                            default:
                                switch ($strFieldType)
                                {
                                    case 'empty':
                                        $arrSet[$strFieldName] = '';
                                        break;
                                    
                                    case 'default':
                                        $strValue = str_replace(array("\\\\", "\\'", "\\r", "\\n"), array("\\", "'", "\r", "\n"), $objXml->value);
                                        $arrSet[$strFieldName] = substr($strValue, 1, (strlen($strValue) - 2));
                                        break;
                                    
                                    default:
                                        $arrSet[$strFieldName] = $objXml->value;
                                        break;
                                }
                                break;
                        }
                    }
                case XMLReader::ELEMENT:
                    if ($objXml->localName == 'field')
                    {
                        $strFieldName = $objXml->getAttribute("name");
                        $strFieldType = $objXml->getAttribute("type");
                    }
                    break;
                    
                case XMLReader::END_ELEMENT:
                    if ($objXml->localName == 'row')
                    {
                        return $arrSet;
                    }
                    break;
            }
        }
    }

    /**
     * Get pid and new sorting for new element
     * 
     * @param string $strTable
     * @param int $intPid
     * @param string $strPastePos
     * @return array
     */
    protected function getNewPosition($strTable, $intPid, $strPastePos)
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

}