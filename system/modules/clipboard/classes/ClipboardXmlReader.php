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
 * Class ClipboardXmlReader
 */
class ClipboardXmlReader extends Backend
{

    /**
     * Current object instance (Singleton)
     * @var ClipboardXmlReader
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
     * Encryption key from file
     * 
     * @var type 
     */
    protected $_strEncryptionKey;

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
     * @return ClipboardXmlReader 
     */
    public static function getInstance()
    {
        if (self::$_objInstance == NULL)
        {
            self::$_objInstance = new ClipboardXmlReader();
        }
        return self::$_objInstance;
    }

    /**
     * Read xml file and create elements
     * 
     * @param ClipboardXmlElement $objFile
     * @param string $strPastePos
     * @param integer $intElemId 
     */
    public function readXml($objFile, $strPastePos, $intElemId)
    {
        $this->Session->set('clipboardExt', array('readXML' => TRUE));
        
        try
        {                
            $objXml = new XMLReader();
            $objXml->open($objFile->getFilePath('full'));
            while ($objXml->read())
            {
                switch ($objXml->nodeType)
                {
                    case XMLReader::ELEMENT:
                        switch ($objXml->localName)
                        {
                            case 'encryptionKey':
                                $objXml->read();
                                $this->_strEncryptionKey = $objXml->value;
                                break;

                            case 'page':
                                $this->createPage($objXml, $objXml->getAttribute("table"), $strPastePos, $intElemId, FALSE, (($objXml->getAttribute("grouped")) ? TRUE : FALSE));
                                break;

                            case 'article':
                                $this->createArticle($objXml, $objXml->getAttribute("table"), $strPastePos, $intElemId, FALSE, (($objXml->getAttribute("grouped")) ? TRUE : FALSE));
                                break;

                            case 'content':
                                $this->createContent($objXml, $objXml->getAttribute("table"), $strPastePos, $intElemId, FALSE, (($objXml->getAttribute("grouped")) ? TRUE : FALSE));                                                                
                                break;

                            case 'module':
                                $this->createModule($objXml, $objXml->getAttribute("table"), $strPastePos, $intElemId, TRUE, (($objXml->getAttribute("grouped")) ? TRUE : FALSE));
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
        catch (Exception $exc)
        {            
            $this->Session->set('clipboardExt', array('readXML' => FALSE));
        }
        
        $this->Session->set('clipboardExt', array('readXML' => FALSE));
    }

    /**
     * Create page elements
     * 
     * @param XMLReader $objXml
     * @param string $strTable
     * @param string $strPastePos
     * @param integer $intElemId     
     * @param boolean $boolIsChild
     * @param boolean $isGrouped
     */
    public function createPage(&$objXml, $strTable, $strPastePos, $intElemId, $boolIsChild = FALSE, $isGrouped = FALSE)
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
                            $arrSet = $this->createArrSetForRow($objXml, $intId, $strTable, $strPastePos, $intElemId, $boolIsChild);
                            $objDb = $this->_objDatabase->insertInto($strTable, $arrSet);
                            $intLastInsertId = $objDb->insertId;

                            $this->loadDataContainer($strTable);

                            $dataContainer = 'DC_' . $GLOBALS['TL_DCA'][$strTable]['config']['dataContainer'];
                            $dc = new $dataContainer($strTable);
                            $dc->setNewActiveRecord($this->_objDatabase->getPageObject($intLastInsertId));
                            $dc->setNewId($intLastInsertId);

                            if($isGrouped)
                            { 
                                $intElemId = $intLastInsertId;
                                $strPastePos = 'pasteAfter';

                                $this->Input->setGet('act', 'copyAll');
                            }
                            else
                            {
                                $this->Input->setGet('act', 'copy');
                            }

                            if (is_array($GLOBALS['TL_DCA'][$strTable]['config']['oncopy_callback']))
                            {
                                    foreach ($GLOBALS['TL_DCA'][$strTable]['config']['oncopy_callback'] as $callback)
                                    {
                                        // Use the Method from DC_Table.php Line 999 ff
                                        if (is_array($callback))
                                        {
                                            $this->import($callback[0]);
                                            $this->{$callback[0]}->{$callback[1]}($intLastInsertId, $dc);
                                        }
                                        elseif (is_callable($callback))
                                        {
                                            $callback($intLastInsertId, $dc);
                                        }
                                    }
                            }
                            $this->Input->setGet('act', NULL);


                            $varValue = '';

                            // Check if we have a hook for the alias generating.
                            if (is_array($GLOBALS['TL_HOOKS']['clipboard_alias']))
                            {
                                foreach ($GLOBALS['TL_HOOKS']['clipboard_alias'] as $callback)
                                {
                                    $this->import($callback[0]);
                                    $varValue = $this->{$callback[0]}->{$callback[1]}($dc, $arrSet, $varValue, $this->_objDatabase, $strTable, $intLastInsertId);

                                }
                            }

                            // Trigger the save_callback as fallback.
                            if($varValue == '' && $varValue != null)
                            {
                                if (is_array($GLOBALS['TL_DCA'][$strTable]['fields']['alias']['save_callback']))
                                {
                                    foreach ($GLOBALS['TL_DCA'][$strTable]['fields']['alias']['save_callback'] as $callback)
                                    {
                                        $this->import($callback[0]);
                                        $varValue = $this->{$callback[0]}->{$callback[1]}($varValue, $dc);
                                    }
                                }
                            }

                            // Only update on empty string or a value.
                            if($varValue != null)
                            {
                                $this->_objDatabase->updateAlias($strTable, $varValue, $intLastInsertId);
                            }
                            break;

                        case 'subpage':
                            $this->createPage($objXml, $objXml->getAttribute("table"), $strPastePos, $intLastInsertId, TRUE);
                    }
                    break;
                case XMLReader::END_ELEMENT:
                    switch ($objXml->localName)
                    {
                        case 'page':
                        case 'subpage':
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
     * @param boolean $boolIsChild
     * @param boolean $isGrouped
     */
    public function createArticle(&$objXml, $strTable, $strPastePos, $intElemId, $boolIsChild = FALSE, $isGrouped = FALSE)
    {
        $intLastInsertId = 0;
        $objDb = NULL;

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
                            
                            if($isGrouped)
                            { 
                                $intElemId = $intLastInsertId;
                                $strPastePos = 'pasteAfter';
                            }                            
                            break;
                    }
                    break;
                case XMLReader::END_ELEMENT:
                    switch ($objXml->localName)
                    {
                        case 'article':
                            $this->loadDataContainer($strTable);

                            $dataContainer = 'DC_' . $GLOBALS['TL_DCA'][$strTable]['config']['dataContainer'];
                            $dc = new $dataContainer($strTable);
                            $dc->setNewActiveRecord($this->_objDatabase->getArticleObject($intLastInsertId));
                            $dc->setNewId($intLastInsertId);
                            
                            if($isGrouped)
                            {
                                $this->Input->setGet('act', 'copyAll');
                            }
                            else
                            {
                                $this->Input->setGet('act', 'copy');
                            }
                            
                            if (is_array($GLOBALS['TL_DCA'][$strTable]['config']['oncopy_callback']))
                            {
                                    foreach ($GLOBALS['TL_DCA'][$strTable]['config']['oncopy_callback'] as $callback)
                                    {
                                            $this->import($callback[0]);
                                            $this->{$callback[0]}->{$callback[1]}($intLastInsertId, $dc);
                                    }
                            }
                            $this->Input->setGet('act', NULL);
                            
                            $varValue = '';
                            
                            // Trigger the save_callback
                            if (is_array($GLOBALS['TL_DCA'][$strTable]['fields']['alias']['save_callback']))
                            {
                                foreach ($GLOBALS['TL_DCA'][$strTable]['fields']['alias']['save_callback'] as $callback)
                                {
                                    $this->import($callback[0]);
                                    $varValue = $this->{$callback[0]}->{$callback[1]}($varValue, $dc);
                                }
                            }

                            $this->_objDatabase->updateAlias($strTable, $varValue, $intLastInsertId);
                            
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
     * @param boolean $boolIsChild
     * @param boolean $isGrouped
     */
    protected function createContent(&$objXml, $strTable, $strPastePos, $intElemId, $boolIsChild = FALSE, $isGrouped = FALSE)
    {
        $arrIds = array();
        
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
                $intElemId = $objElem->id;
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
                            $arrSet = $this->createArrSetForRow($objXml, $intId, $strTable, $strPastePos, $intElemId, $boolIsChild);
                            if (array_key_exists('type', $arrSet))
                            {
                                if ($this->_objHelper->existsContentType($arrSet))
                                {
                                    if (md5($GLOBALS['TL_CONFIG']['encryptionKey']) != $this->_strEncryptionKey)
                                    {
                                        if (!array_key_exists(substr($arrSet['type'], 1, -1), $GLOBALS['TL_CTE']['includes']))
                                        {
                                            $objDb = $this->_objDatabase->insertInto($strTable, $arrSet);
                                            $intLastInsertId = $objDb->insertId;

                                            $this->loadDataContainer($strTable);

                                            $dataContainer = 'DC_' . $GLOBALS['TL_DCA'][$strTable]['config']['dataContainer'];
                                            $dc = new $dataContainer($strTable);

                                            if($isGrouped)
                                            { 
                                                $intElemId = $intLastInsertId;
                                                $strPastePos = 'pasteAfter';

                                                $this->Input->setGet('act', 'copyAll');
                                            }
                                            else
                                            {
                                                $this->Input->setGet('act', 'copy');
                                            }

                                            if (is_array($GLOBALS['TL_DCA'][$strTable]['config']['oncopy_callback']))
                                            {
                                                    foreach ($GLOBALS['TL_DCA'][$strTable]['config']['oncopy_callback'] as $callback)
                                                    {
                                                            $this->import($callback[0]);
                                                            $this->{$callback[0]}->{$callback[1]}($intLastInsertId, $dc);
                                                    }
                                            }
                                            
                                            // HOOK: call the hooks for clipboardCopy
                                            if (isset($GLOBALS['TL_HOOKS']['clipboardCopy']) && is_array($GLOBALS['TL_HOOKS']['clipboardCopy']))
                                            {
                                                foreach ($GLOBALS['TL_HOOKS']['clipboardCopy'] as $arrCallback)
                                                {
                                                    $this->import($arrCallback[0]);                        
                                                    $this->{$arrCallback[0]}->{$arrCallback[1]}($intLastInsertId, $dc, $isGrouped);
                                                }
                                            }
                                            
                                            $objResult = $this->Database
                                                    ->prepare('SELECT pid FROM tl_content WHERE id = ?')
                                                    ->execute($intLastInsertId);
                                                                                                
                                            $arrIds[$intLastInsertId] = $objResult->pid;
                                            
                                            $this->Input->setGet('act', NULL);                                           
                                            
                                        }
                                        else
                                        {
                                            $strMessage = $GLOBALS['TL_LANG']['MSC']['clContentPasteInfo'][0];
                                            $this->_objHelper->writeCustomerInfoMessage($strMessage);
                                        }
                                    }
                                    else
                                    {
                                        $objDb = $this->_objDatabase->insertInto($strTable, $arrSet);
                                        $intLastInsertId = $objDb->insertId;
                                        
                                        $this->loadDataContainer($strTable);
                                        
                                        $dataContainer = 'DC_' . $GLOBALS['TL_DCA'][$strTable]['config']['dataContainer'];
                                        $dc = new $dataContainer($strTable);

                                        if($isGrouped)
                                        { 
                                            $intElemId = $intLastInsertId;
                                            $strPastePos = 'pasteAfter';
                                            
                                            $this->Input->setGet('act', 'copyAll');
                                        }
                                        else
                                        {
                                            $this->Input->setGet('act', 'copy');
                                        }
                                        
                                        if (is_array($GLOBALS['TL_DCA'][$strTable]['config']['oncopy_callback']))
                                        {
                                                foreach ($GLOBALS['TL_DCA'][$strTable]['config']['oncopy_callback'] as $callback)
                                                {
                                                        $this->import($callback[0]);
                                                        $this->{$callback[0]}->{$callback[1]}($intLastInsertId, $dc);
                                                }
                                        }
                                        
                                        // HOOK: call the hooks for clipboardCopy
                                        if (isset($GLOBALS['TL_HOOKS']['clipboardCopy']) && is_array($GLOBALS['TL_HOOKS']['clipboardCopy']))
                                        {
                                            foreach ($GLOBALS['TL_HOOKS']['clipboardCopy'] as $arrCallback)
                                            {
                                                $this->import($arrCallback[0]);                        
                                                $this->{$arrCallback[0]}->{$arrCallback[1]}($intLastInsertId, $dc, $isGrouped);
                                            }
                                        }
                                        
                                        $objResult = $this->Database
                                                ->prepare('SELECT pid FROM tl_content WHERE id = ?')
                                                ->execute($intLastInsertId);

                                        $arrIds[$intLastInsertId] = $objResult->pid;
                                        
                                        $this->Input->setGet('act', NULL);
                                    }
                                }
                                else
                                {
                                    $strMessage = $GLOBALS['TL_LANG']['MSC']['clContentPasteInfo'][1];
                                    $this->_objHelper->writeCustomerInfoMessage($strMessage);
                                }
                            }
                    }
                    break;
                case XMLReader::END_ELEMENT:
                    switch ($objXml->localName)
                    {
                        case 'content':
                            
                           if($isGrouped && count($arrIds) > 0)
                           {                               
                               // HOOK: call the hooks for clipboardCopyAll
                                if (isset($GLOBALS['TL_HOOKS']['clipboardCopyAll']) && is_array($GLOBALS['TL_HOOKS']['clipboardCopyAll']))
                                {
                                    foreach ($GLOBALS['TL_HOOKS']['clipboardCopyAll'] as $arrCallback)
                                    {
                                        $this->import($arrCallback[0]);                        
                                        $this->{$arrCallback[0]}->{$arrCallback[1]}($arrIds);
                                    }
                                }
                           }
                            
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
     * @param boolean $boolIsChild
     * @param boolean $isGrouped
     */
    protected function createModule(&$objXml, $strTable, $strPastePos, $intElemId, $boolIsChild, $isGrouped = FALSE)
    {
        $arrIds = array();
        
        if ($boolIsChild == TRUE)
        {
            $intId = $intElemId;
        }        

        while ($objXml->read())
        {
            switch ($objXml->nodeType)
            {
                case XMLReader::ELEMENT:
                    switch ($objXml->localName)
                    {
                        case 'row':
                            $arrSet = $this->createArrSetForRow($objXml, $intId, $strTable, $strPastePos, $intElemId, $boolIsChild);
                            if (array_key_exists('type', $arrSet))
                            {
                                if ($this->_objHelper->existsModuleType($arrSet))
                                {
                                    $objDb = $this->_objDatabase->insertInto($strTable, $arrSet);
                                    $intLastInsertId = $objDb->insertId;

                                    $this->loadDataContainer($strTable);

                                    $dataContainer = 'DC_' . $GLOBALS['TL_DCA'][$strTable]['config']['dataContainer'];
                                    $dc = new $dataContainer($strTable);

                                    if($isGrouped)
                                    { 
                                        $intElemId = $intLastInsertId;
                                        $strPastePos = 'pasteAfter';

                                        $this->Input->setGet('act', 'copyAll');
                                    }
                                    else
                                    {
                                        $this->Input->setGet('act', 'copy');
                                    }

                                    if (is_array($GLOBALS['TL_DCA'][$strTable]['config']['oncopy_callback']))
                                    {
                                            foreach ($GLOBALS['TL_DCA'][$strTable]['config']['oncopy_callback'] as $callback)
                                            {
                                                    $this->import($callback[0]);
                                                    $this->{$callback[0]}->{$callback[1]}($intLastInsertId, $dc);
                                            }
                                    }

                                    // HOOK: call the hooks for clipboardCopy
                                    if (isset($GLOBALS['TL_HOOKS']['clipboardCopy']) && is_array($GLOBALS['TL_HOOKS']['clipboardCopy']))
                                    {
                                        foreach ($GLOBALS['TL_HOOKS']['clipboardCopy'] as $arrCallback)
                                        {
                                            $this->import($arrCallback[0]);                        
                                            $this->{$arrCallback[0]}->{$arrCallback[1]}($intLastInsertId, $dc, $isGrouped);
                                        }
                                    }

                                    $objResult = $this->Database
                                            ->prepare('SELECT pid FROM tl_module WHERE id = ?')
                                            ->execute($intLastInsertId);

                                    $arrIds[$intLastInsertId] = $objResult->pid;

                                    $this->Input->setGet('act', NULL);
                                }
                                else
                                {
                                    $strMessage = sprintf($GLOBALS['TL_LANG']['MSC']['clModulePasteInfo'], $arrSet['type']);
                                    $this->_objHelper->writeCustomerInfoMessage($strMessage);
                                }
                            }
                    }
                    break;
                case XMLReader::END_ELEMENT:
                    switch ($objXml->localName)
                    {
                        case 'module':
                            
                           if($isGrouped && count($arrIds) > 0)
                           {                               
                               // HOOK: call the hooks for clipboardCopyAll
                                if (isset($GLOBALS['TL_HOOKS']['clipboardCopyAll']) && is_array($GLOBALS['TL_HOOKS']['clipboardCopyAll']))
                                {
                                    foreach ($GLOBALS['TL_HOOKS']['clipboardCopyAll'] as $arrCallback)
                                    {
                                        $this->import($arrCallback[0]);                        
                                        $this->{$arrCallback[0]}->{$arrCallback[1]}($arrIds);
                                    }
                                }
                           }
                            
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
    protected function createArrSetForRow(&$objXml, $intId, $strTable, $strPastePos, $intElemId, $boolIsChild = FALSE)
    {
        $arrFields = $this->_objHelper->getFields($strTable);
        $arrSet = array();
        $strFieldType = '';
        $strFieldName = '';

        while ($objXml->read())
        {
            switch ($objXml->nodeType)
            {
                case XMLReader::CDATA:
                case XMLReader::TEXT:
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
                                    $arrSorting = $this->_objHelper->getNewPosition($strTable, $intElemId, $strPastePos);
                                    $arrSet['pid'] = $arrSorting['pid'];
                                    $arrSet['sorting'] = $arrSorting['sorting'];
                                    break;
                                }

                            default:
                                switch ($strFieldType)
                                {
                                    case 'default':
                                        $strValue = str_replace($this->_objHelper->arrReplaceWith, $this->_objHelper->arrSearchFor, $objXml->value);
                                        $arrSet[$strFieldName] = $strValue;
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
                        if($strTable == 'tl_module')
                        {
                            $arrSet['pid'] = $intId;
                        }
                        
                        return $arrSet;
                    }
                    break;
            }
        }
    }

    /**
     * Return all metainformation for all xml files from current user
     * 
     * @param string $strDo
     * @return array
     */
    public function getDetailFileInfo($strFilePath)
    {
        $arrMetaInformation = array();
        
        $objDomDoc = new DOMDocument();
        $objDomDoc->load($strFilePath);
        $objMetaTags = $objDomDoc->getElementsByTagName('metatags')->item(0);            
        $objMetaChilds = $objMetaTags->childNodes;

        for($i = 0; $i < $objMetaChilds->length; $i++)
        {
            $strNodeName = $objMetaChilds->item($i)->nodeName;
            $arrMetaInformation[$strNodeName] = $objMetaChilds->item($i)->nodeValue;
        }
        return $arrMetaInformation;
    }    

}

?>
