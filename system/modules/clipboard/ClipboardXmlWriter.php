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
 * Class ClipboardXmlWriter
 */
class ClipboardXmlWriter extends Backend
{

    /**
     * Current object instance (Singleton)
     * 
     * @var ClipboardXmlWriter
     */
    protected static $_objInstance = NULL;

    /**
     * Contains some helper functions
     * 
     * @var ClipboardHelper
     */
    protected $_objHelper;

    /**
     * Contains specific database request
     * 
     * @var ClipboardDatabase
     */
    protected $_objDatabase;

    /**
     * Variables 
     */
    protected $_strPageTable = 'tl_page';
    protected $_strArticleTable = 'tl_article';
    protected $_strContentTable = 'tl_content';
    protected $_strModuleTable = 'tl_module';

    /**
     * Prevent constructing the object (Singleton)
     */
    protected function __construct()
    {
        parent::__construct();
        $this->_objHelper = ClipboardHelper::getInstance();
        $this->_objDatabase = ClipboardDatabase::getInstance();
    }

    /**
     * Prevent cloning of the object (Singleton)
     */
    final private function __clone(){}

    /**
     * Get instanz of the object (Singelton) 
     *
     * @return ClipboardXmlWriter 
     */
    public static function getInstance()
    {
        if (self::$_objInstance == NULL)
        {
            self::$_objInstance = new ClipboardXmlWriter();
        }
        return self::$_objInstance;
    }
    
    /**
     * Create xml file for the given element and all his childs
     * 
     * @param array $arrSet
     * @param array $arrCbElems
     * @return boolean 
     */
    public function writeXml($arrSet, $arrCbElems)
    {
        $strMd5Checksum = $this->_createChecksum($arrSet);

        if(is_array($arrCbElems))
        {            
            foreach($arrCbElems AS $objCbFile)
            {
                if($objCbFile->getChecksum() == $strMd5Checksum)
                {
                    return FALSE;
                }
            }
        }
  
        $this->_strPageTable = $arrSet['table'];

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
        $objXml->startElement('title');
        $objXml->writeCdata($arrSet['title']);
        $objXml->endElement(); // End title
        $objXml->startElement('attribute');
        $objXml->writeCdata($arrSet['attribute']);
        $objXml->endElement(); // End attribute
        $objXml->startElement('group_count');
        $objXml->writeCdata($arrSet['groupCount']);
        $objXml->endElement(); // End group_count
        $objXml->writeElement('childs', (($arrSet['childs']) ? 1 : 0));
        $objXml->writeElement('table', $arrSet['table']);        
        $objXml->writeElement('checksum', $strMd5Checksum);
        $objXml->writeElement('encryptionKey', md5($GLOBALS['TL_CONFIG']['encryptionKey']));
        $objXml->endElement(); // End metatags

        $objXml->startElement('datacontainer');
        switch ($arrSet['table'])
        {
            case 'tl_page':
                $this->writePage($arrSet['elem_id'], $objXml, $arrSet['childs'], $arrSet['grouped']);
                break;
            case 'tl_article':
                $this->writeArticle($arrSet['elem_id'], $objXml, FALSE, $arrSet['grouped']);
                break;
            case 'tl_content':
                $this->writeContent($arrSet['elem_id'], $objXml, FALSE, $arrSet['grouped']);
                break;
            case 'tl_module':
                $this->writeModule($arrSet['elem_id'], $objXml, FALSE, $arrSet['grouped']);
                break;
        }
        $objXml->endElement(); // End datacontainer

        $objXml->endElement(); // End clipboard

        $strXml = $objXml->outputMemory();

        $objFile = new File($arrSet['path'] . '/' . $arrSet['filename']);
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
     * @param mixed $mixedId
     * @param XMLWriter $objXml
     * @param boolean $boolChilds
     * @param boolean $boolGrouped
     * @return XMLWriter 
     */
    protected function writePage($mixedId, &$objXml, $boolHasChilds, $boolGrouped = FALSE)
    {        
        $arrRows = $this->_objDatabase->getPageObject($mixedId)->fetchAllAssoc();       
        
        $objXml->startElement('page');
        $objXml->writeAttribute('table', $this->_strPageTable);
        if($boolGrouped)
        {
            $objXml->writeAttribute('grouped', TRUE);
        }        
        
        foreach ($arrRows AS $arrRow)
        {        
            $this->writeGivenDbTableRows($this->_strPageTable, array($arrRow), $objXml);

            $objXml->startElement('articles');
            $this->writeArticle($arrRow['id'], $objXml, TRUE);
            $objXml->endElement(); // End articles
        }
        
        if ($boolHasChilds)
        {
            $this->writeSubpages($mixedId, $objXml);
        }

        $objXml->endElement(); // End page
    }

    /**
     * Write subpage informations to xml object
     * 
     * @param type $intId
     * @param XMLWriter $objXml
     */
    protected function writeSubpages($intId, &$objXml)
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
     * @param mixed $mixedId
     * @param XMLWriter $objXml
     * @param boolean $boolIsChild
     * @param boolean $boolGrouped
     */
    protected function writeArticle($mixedId, &$objXml, $boolIsChild, $boolGrouped = FALSE)
    {
        if ($boolIsChild)
        {
            $arrRows = $this->_objDatabase->getArticleObjectFromPid($mixedId)->fetchAllAssoc();
        }
        else
        {
            $arrRows = $this->_objDatabase->getArticleObject($mixedId)->fetchAllAssoc();
        }

        if (count($arrRows) < 1)
        {
            return;
        }

        $objXml->startElement('article');
        $objXml->writeAttribute('table', $this->_strArticleTable);
        if($boolGrouped)
        {
            $objXml->writeAttribute('grouped', TRUE);
        }         
        
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
     * @param integer $mixedId
     * @param XMLWriter $objXml
     * @param boolean $boolIsChild
     * @param boolean $boolGrouped
     */
    protected function writeContent($mixedId, &$objXml, $boolIsChild, $boolGrouped = FALSE)
    {
        if ($boolIsChild)
        {
            $arrRows = $this->_objDatabase->getContentObjectFromPid($mixedId)->fetchAllAssoc();
        }
        else
        {
            $arrRows = $this->_objDatabase->getContentObject($mixedId)->fetchAllAssoc();
        }

        if (count($arrRows) < 1)
        {
            return;
        }

        $objXml->startElement('content');
        $objXml->writeAttribute('table', $this->_strContentTable);
        if($boolGrouped)
        {
            $objXml->writeAttribute('grouped', TRUE);
        }         

        $this->writeGivenDbTableRows($this->_strContentTable, $arrRows, $objXml);

        $objXml->endElement(); // End content
    }

    /**
     * Write module informations to xml object
     * 
     * @param integer $mixedId
     * @param XMLWriter $objXml
     * @param boolean $boolIsChild
     * @param boolean $boolGrouped
     */    
    protected function writeModule($mixedId, &$objXml, $boolIsChild = FALSE, $boolGrouped = FALSE)
    {        
        $arrRows = $this->_objDatabase->getModuleObject($mixedId)->fetchAllAssoc();
        
        $objXml->startElement('module');
        $objXml->writeAttribute('table', $this->_strModuleTable);
        if($boolGrouped)
        {
            $objXml->writeAttribute('grouped', TRUE);
        }         

        $this->writeGivenDbTableRows($this->_strModuleTable, $arrRows, $objXml);

        $objXml->endElement(); // End module
    }

    /**
     * Write the given database rows to the xml object
     * 
     * @param string $strTable
     * @param array $arrRows
     * @param XMLWriter $objXml 
     */
    protected function writeGivenDbTableRows($strTable, $arrRows, &$objXml)
    {
        $arrFieldMeta = $this->_objHelper->getTableMetaFields($strTable);

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


                            if (!isset($field_data))
                            {
                                $objXml->startElement('field');
                                $objXml->writeAttribute("name", $field_key);

                                $objXml->writeAttribute("type", "null");
                                $objXml->text("NULL");

                                $objXml->endElement(); // End field
                            }
                            else if ($field_data != "")
                            {
                                $objXml->startElement('field');
                                $objXml->writeAttribute("name", $field_key);

                                switch (strtolower($arrFieldMeta[$field_key]['type']))
                                {
                                    case 'binary':
                                    case 'varbinary':
                                    case 'blob':
                                    case 'tinyblob':
                                    case 'mediumblob':
                                    case 'longblob':
                                        $objXml->writeAttribute("type", "blob");
                                        $objXml->text("0x" . bin2hex($field_data));
                                        break;

                                    case 'tinyint':
                                    case 'smallint':
                                    case 'mediumint':
                                    case 'int':
                                    case 'integer':
                                    case 'bigint':
                                        $objXml->writeAttribute("type", "int");
                                        $objXml->text($field_data);
                                        break;

                                    case 'float':
                                    case 'double':
                                    case 'real':
                                    case 'decimal':
                                    case 'numeric':
                                        $objXml->writeAttribute("type", "decimal");
                                        $objXml->text($field_data);
                                        break;

                                    case 'date':
                                    case 'datetime':
                                    case 'timestamp':
                                    case 'time':
                                    case 'year':
                                        $objXml->writeAttribute("type", "date");
                                        $objXml->text("'" . $field_data . "'");
                                        break;

                                    case 'char':
                                    case 'varchar':
                                    case 'text':
                                    case 'tinytext':
                                    case 'mediumtext':
                                    case 'longtext':
                                    case 'enum':
                                    case 'set':
                                        $objXml->writeAttribute("type", "text");
                                        $objXml->writeCdata("'" . str_replace($this->_objHelper->arrSearchFor, $this->_objHelper->arrReplaceWith, $field_data) . "'");
                                        break;

                                    default:
                                        $objXml->writeAttribute("type", "default");
                                        $objXml->writeCdata("'" . str_replace($this->_objHelper->arrSearchFor, $this->_objHelper->arrReplaceWith, $field_data) . "'");
                                        break;
                                }
                                $objXml->endElement(); // End field  
                            }

                            break;
                    }
                }

                $objXml->endElement(); // End row            
            }
        }
    }
    
    /**
     * Get checksum as md5 hash from all page, article and content elements 
     * that need to save in xml
     * 
     * @param array $arrSet
     * @return string 
     */
    protected function _createChecksum($arrSet)
    {
        $arrChecksum = array();
        switch ($arrSet['table'])
        {            
            case 'tl_page':
                $arrPages = $this->_objDatabase->getPageObject($arrSet['elem_id'])->fetchAllAssoc();
                if($arrSet['childs'])
                {                    
                    $arrTmp = array($arrPages[0]['id']);
                    $arrChecksum['page'][] = $arrPages[0];
                    for($i = 0; TRUE; $i++)
                    {
                        if(!isset($arrTmp[$i]))
                        {
                            break;
                        }
                        $arrSubPages = $this->_objDatabase->getSubpagesObject($arrTmp[$i])->fetchAllAssoc();
                        foreach($arrSubPages AS $arrSubPage)
                        {
                            $arrTmp[] = $arrSubPage['id'];
                            $arrChecksum['page'][] = $arrSubPage;
                        }
                    }
                }
                else
                {
                    foreach ($arrPages AS $arrPage)
                    {
                        $arrChecksum['page'][] = $arrPage;
                    }
                }
            case 'tl_article':
                if(is_array($arrChecksum['page']))
                {
                    foreach($arrChecksum['page'] AS $arrPage)
                    {
                        $arrArticles = $this->_objDatabase->getArticleObjectFromPid($arrPage['id'])->fetchAllAssoc();
                        foreach($arrArticles AS $arrArticle)
                        {
                            $arrChecksum['article'][] = $arrArticle;
                        }
                    }
                }
                else
                {
                    $arrArticles = $this->_objDatabase->getArticleObject($arrSet['elem_id'])->fetchAllAssoc();
                    foreach($arrArticles AS $arrArticle)
                    {
                        $arrChecksum['article'][] = $arrArticle;
                    }                    
                }
            case 'tl_content':
                if(is_array($arrChecksum['article']))
                {
                    foreach ($arrChecksum['article'] AS $arrArticle)
                    {
                        $arrContents = $this->_objDatabase->getContentObjectFromPid($arrArticle['id'])->fetchAllAssoc();
                        foreach ($arrContents AS $arrContent)
                        {
                            $arrChecksum['content'][] = $arrContent;
                        }
                    }
                }
                else
                {
                    $arrContents = $this->_objDatabase->getContentObject($arrSet['elem_id'])->fetchAllAssoc();
                    foreach ($arrContents AS $arrContent)
                    {
                        $arrChecksum['content'][] = $arrContent;
                    }                    
                }
                break;
                
            case 'tl_module':
                $arrModules = $this->_objDatabase->getModuleObject($arrSet['elem_id'])->fetchAllAssoc();
                foreach ($arrModules AS $arrModule)
                {
                    $arrChecksum['module'][] = $arrModule;
                }
                break;
        }
        return md5(serialize($arrChecksum));
    }
    
    /**
     * Set new value for title in metatags of given file
     * 
     * @param string $strFilePath
     * @param string $strTitle
     * @return boolean 
     */
    public function setNewTitle($strFullFilePath, $strFilePath, $strTitle)
    {
        $objDomDoc = new DOMDocument();
        $objDomDoc->load($strFullFilePath);
        $nodeTitle = $objDomDoc->getElementsByTagName('metatags')->item(0)->getElementsByTagName('title')->item(0);
        $nodeTitle->nodeValue = '';
        $cdata = $objDomDoc->createCDATASection($strTitle);
        $nodeTitle->appendChild($cdata);
        $strDomDoc = $objDomDoc->saveXML();
        
        $objFile = new File($strFilePath);
        if ($objFile->write($strDomDoc))
        {
            $objFile->close;
            return TRUE;
        }
        $objFile->close;
        return FALSE;
    }

}

?>