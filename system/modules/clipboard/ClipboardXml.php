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
        return 'tl_files' . '/' . 'clipboard' . '/' . $this->_objBeUser->username;
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
     * Edit the title tag in the given file
     * 
     * @param string $strFilename
     * @param string $strTitle
     * @return boolean 
     */
    public function editTitle($strFilename, $strTitle)
    {
        if ($this->fileExists($strFilename))
        {
            $objDomDoc = new DOMDocument();
            $objDomDoc->load(TL_ROOT . '/' . $this->getFolderPath() . '/' . $strFilename);
            $nodeTitle = $objDomDoc->getElementsByTagName('metatags')->item(0)->getElementsByTagName('title')->item(0);
            $nodeTitle->nodeValue = $strTitle;
            $strDomDoc = $objDomDoc->saveXML();

            $objUserFolder = new Folder($this->getFolderPath());
            $objFile = new File($objUserFolder->value . '/' . $strFilename);
            $write = $objFile->write($strDomDoc);
            if ($write)
            {
                $objFile->close;
                return TRUE;
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

}