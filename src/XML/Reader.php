<?php

/**
 * Contao Open Source CMS
 *
 * @copyright  MEN AT WORK 2013
 * @package    clipboard
 * @license    GNU/LGPL
 * @filesource
 */

namespace MenAtWork\ClipboardBundle\Xml;

use MenAtWork\ClipboardBundle\Helper\Base;
use MenAtWork\ClipboardBundle\Helper\ContaoBridge;
use MenAtWork\ClipboardBundle\Helper\Database;

/**
 * Class ClipboardXmlReader
 */
class Reader
{
    /**
     * @var \MenAtWork\ClipboardBundle\Helper\Base
     */
    private $helper;

    /**
     * @var \MenAtWork\ClipboardBundle\Helper\Database
     */
    private $database;

    /**
     * @var \MenAtWork\ClipboardBundle\Helper\ContaoBridge
     */
    private $contaoBindings;

    /**
     * Encryption key from file
     *
     * @var string
     */
    protected $encryptionKey;

    /**
     * Reader constructor.
     *
     * @param Base         $clipboardHelper
     *
     * @param Database     $clipboardDatabase
     *
     * @param ContaoBridge $contaoBindings
     */
    public function __construct($clipboardHelper, $clipboardDatabase, $contaoBindings)
    {
        $this->helper         = $clipboardHelper;
        $this->database       = $clipboardDatabase;
        $this->contaoBindings = $contaoBindings;
    }

    /**
     * Read xml file and create elements
     *
     * @param ClipboardXmlElement $objFile
     * @param string              $strPastePos
     * @param integer             $intElemId
     */
    public function readXml($objFile, $strPastePos, $intElemId)
    {
        $this->Session->set('clipboardExt', array('readXML' => true));

        try {
            $objXml = new XMLReader();
            $objXml->open($objFile->getFilePath('full'));
            while ($objXml->read()) {
                switch ($objXml->nodeType) {
                    case XMLReader::ELEMENT:
                        switch ($objXml->localName) {
                            case 'encryptionKey':
                                $objXml->read();
                                $this->encryptionKey = $objXml->value;
                                break;

                            case 'page':
                                $this->createPage($objXml, $objXml->getAttribute("table"), $strPastePos, $intElemId,
                                    false, (($objXml->getAttribute("grouped")) ? true : false));
                                break;

                            case 'article':
                                $this->createArticle($objXml, $objXml->getAttribute("table"), $strPastePos, $intElemId,
                                    false, (($objXml->getAttribute("grouped")) ? true : false));
                                break;

                            case 'content':
                                $this->createContent($objXml, $objXml->getAttribute("table"), $strPastePos, $intElemId,
                                    false, (($objXml->getAttribute("grouped")) ? true : false));
                                break;

                            case 'module':
                                $this->createModule($objXml, $objXml->getAttribute("table"), $strPastePos, $intElemId,
                                    true, (($objXml->getAttribute("grouped")) ? true : false));
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
        } catch (Exception $exc) {
            $this->Session->set('clipboardExt', array('readXML' => false));
        }

        $this->Session->set('clipboardExt', array('readXML' => false));
    }

    /**
     * Create page elements
     *
     * @param XMLReader $objXml
     * @param string    $strTable
     * @param string    $strPastePos
     * @param integer   $intElemId
     * @param boolean   $boolIsChild
     * @param boolean   $isGrouped
     */
    public function createPage(&$objXml, $strTable, $strPastePos, $intElemId, $boolIsChild = false, $isGrouped = false)
    {
        $intLastInsertId = 0;

        if ($boolIsChild == true) {
            $intId = $intElemId;
        } else {
            if ($strPastePos == 'pasteAfter') {
                $objElem = $this->database->getPageObject($intElemId);
                $intId   = $objElem->pid;
            }
        }

        while ($objXml->read()) {
            switch ($objXml->nodeType) {
                case XMLReader::ELEMENT:
                    switch ($objXml->localName) {
                        case 'article':
                            $this->createArticle($objXml, $objXml->getAttribute("table"), $strPastePos,
                                $intLastInsertId, true);
                            break;

                        case 'row':
                            $arrSet          = $this->createArrSetForRow($objXml, $intId, $strTable, $strPastePos,
                                $intElemId, $boolIsChild);
                            $objDb           = $this->database->insertInto($strTable, $arrSet);
                            $intLastInsertId = $objDb->insertId;

                            $this->loadDataContainer($strTable);

                            $dataContainer = 'DC_' . $GLOBALS['TL_DCA'][$strTable]['config']['dataContainer'];
                            $dc            = new $dataContainer($strTable);
                            $dc->setNewActiveRecord($this->database->getPageObject($intLastInsertId));
                            $dc->setNewId($intLastInsertId);

                            if ($isGrouped) {
                                $intElemId   = $intLastInsertId;
                                $strPastePos = 'pasteAfter';

                                $this->Input->setGet('act', 'copyAll');
                            } else {
                                $this->Input->setGet('act', 'copy');
                            }

                            if (is_array($GLOBALS['TL_DCA'][$strTable]['config']['oncopy_callback'])) {
                                foreach ($GLOBALS['TL_DCA'][$strTable]['config']['oncopy_callback'] as $callback) {
                                    $this->import($callback[0]);
                                    $this->{$callback[0]}->{$callback[1]}($intLastInsertId, $dc);
                                }
                            }
                            $this->Input->setGet('act', null);


                            $varValue = '';

                            // Check if we have a hook for the alias generating.
                            if (is_array($GLOBALS['TL_HOOKS']['clipboard_alias'])) {
                                foreach ($GLOBALS['TL_HOOKS']['clipboard_alias'] as $callback) {
                                    $this->import($callback[0]);
                                    $varValue = $this->{$callback[0]}->{$callback[1]}($dc, $arrSet, $varValue,
                                        $this->database, $strTable, $intLastInsertId);

                                }
                            }

                            // Trigger the save_callback as fallback.
                            if ($varValue == '' && $varValue != null) {
                                if (is_array($GLOBALS['TL_DCA'][$strTable]['fields']['alias']['save_callback'])) {
                                    foreach ($GLOBALS['TL_DCA'][$strTable]['fields']['alias']['save_callback'] as $callback) {
                                        $this->import($callback[0]);
                                        $varValue = $this->{$callback[0]}->{$callback[1]}($varValue, $dc);
                                    }
                                }
                            }

                            // Only update on empty string or a value.
                            if ($varValue != null) {
                                $this->database->updateAlias($strTable, $varValue, $intLastInsertId);
                            }
                            break;

                        case 'subpage':
                            $this->createPage($objXml, $objXml->getAttribute("table"), $strPastePos, $intLastInsertId,
                                true);
                    }
                    break;
                case XMLReader::END_ELEMENT:
                    switch ($objXml->localName) {
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
     * @param string    $strTable
     * @param string    $strPastePos
     * @param integer   $intElemId
     * @param boolean   $boolIsChild
     * @param boolean   $isGrouped
     */
    public function createArticle(
        &$objXml,
        $strTable,
        $strPastePos,
        $intElemId,
        $boolIsChild = false,
        $isGrouped = false
    ) {
        $intLastInsertId = 0;
        $objDb           = null;

        if ($boolIsChild == true) {
            $intId = $intElemId;
        } else {
            if ($strPastePos == 'pasteAfter') {
                $objElem = $this->database->getContentObject($intElemId);
                $intId   = $objElem->pid;
            }
        }

        while ($objXml->read()) {
            switch ($objXml->nodeType) {
                case XMLReader::ELEMENT:
                    switch ($objXml->localName) {
                        case 'content':
                            $this->createContent($objXml, $objXml->getAttribute("table"), $strPastePos,
                                $intLastInsertId, true);
                            break;

                        case 'row':
                            $objDb           = $this->database->insertInto($strTable,
                                $this->createArrSetForRow($objXml, $intId, $strTable, $strPastePos, $intElemId,
                                    $boolIsChild));
                            $intLastInsertId = $objDb->insertId;

                            if ($isGrouped) {
                                $intElemId   = $intLastInsertId;
                                $strPastePos = 'pasteAfter';
                            }
                            break;
                    }
                    break;
                case XMLReader::END_ELEMENT:
                    switch ($objXml->localName) {
                        case 'article':
                            $this->loadDataContainer($strTable);

                            $dataContainer = 'DC_' . $GLOBALS['TL_DCA'][$strTable]['config']['dataContainer'];
                            $dc            = new $dataContainer($strTable);
                            $dc->setNewActiveRecord($this->database->getArticleObject($intLastInsertId));
                            $dc->setNewId($intLastInsertId);

                            if ($isGrouped) {
                                $this->Input->setGet('act', 'copyAll');
                            } else {
                                $this->Input->setGet('act', 'copy');
                            }

                            if (is_array($GLOBALS['TL_DCA'][$strTable]['config']['oncopy_callback'])) {
                                foreach ($GLOBALS['TL_DCA'][$strTable]['config']['oncopy_callback'] as $callback) {
                                    $this->import($callback[0]);
                                    $this->{$callback[0]}->{$callback[1]}($intLastInsertId, $dc);
                                }
                            }
                            $this->Input->setGet('act', null);

                            $varValue = '';

                            // Trigger the save_callback
                            if (is_array($GLOBALS['TL_DCA'][$strTable]['fields']['alias']['save_callback'])) {
                                foreach ($GLOBALS['TL_DCA'][$strTable]['fields']['alias']['save_callback'] as $callback) {
                                    $this->import($callback[0]);
                                    $varValue = $this->{$callback[0]}->{$callback[1]}($varValue, $dc);
                                }
                            }

                            $this->database->updateAlias($strTable, $varValue, $intLastInsertId);

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
     * @param string    $strTable
     * @param string    $strPastePos
     * @param integer   $intElemId
     * @param boolean   $boolIsChild
     * @param boolean   $isGrouped
     */
    protected function createContent(
        &$objXml,
        $strTable,
        $strPastePos,
        $intElemId,
        $boolIsChild = false,
        $isGrouped = false
    ) {
        $arrIds = array();

        if ($boolIsChild == true) {
            $intId = $intElemId;
        } else {
            if ($strPastePos == 'pasteAfter') {
                $objElem   = $this->database->getContentObject($intElemId);
                $intId     = $objElem->pid;
                $intElemId = $objElem->id;
            }
        }

        while ($objXml->read()) {
            switch ($objXml->nodeType) {
                case XMLReader::ELEMENT:
                    switch ($objXml->localName) {
                        case 'row':
                            $arrSet = $this->createArrSetForRow($objXml, $intId, $strTable, $strPastePos, $intElemId,
                                $boolIsChild);
                            if (array_key_exists('type', $arrSet)) {
                                if ($this->helper->existsContentType($arrSet)) {
                                    if (md5($GLOBALS['TL_CONFIG']['encryptionKey']) != $this->encryptionKey) {
                                        if (!array_key_exists(substr($arrSet['type'], 1, -1),
                                            $GLOBALS['TL_CTE']['includes'])) {
                                            $objDb           = $this->database->insertInto($strTable, $arrSet);
                                            $intLastInsertId = $objDb->insertId;

                                            $this->loadDataContainer($strTable);

                                            $dataContainer = 'DC_' . $GLOBALS['TL_DCA'][$strTable]['config']['dataContainer'];
                                            $dc            = new $dataContainer($strTable);

                                            if ($isGrouped) {
                                                $intElemId   = $intLastInsertId;
                                                $strPastePos = 'pasteAfter';

                                                $this->Input->setGet('act', 'copyAll');
                                            } else {
                                                $this->Input->setGet('act', 'copy');
                                            }

                                            if (is_array($GLOBALS['TL_DCA'][$strTable]['config']['oncopy_callback'])) {
                                                foreach ($GLOBALS['TL_DCA'][$strTable]['config']['oncopy_callback'] as $callback) {
                                                    $this->import($callback[0]);
                                                    $this->{$callback[0]}->{$callback[1]}($intLastInsertId, $dc);
                                                }
                                            }

                                            // HOOK: call the hooks for clipboardCopy
                                            if (isset($GLOBALS['TL_HOOKS']['clipboardCopy']) && is_array($GLOBALS['TL_HOOKS']['clipboardCopy'])) {
                                                foreach ($GLOBALS['TL_HOOKS']['clipboardCopy'] as $arrCallback) {
                                                    $this->import($arrCallback[0]);
                                                    $this->{$arrCallback[0]}->{$arrCallback[1]}($intLastInsertId, $dc,
                                                        $isGrouped);
                                                }
                                            }

                                            $objResult = $this->Database
                                                ->prepare('SELECT pid FROM tl_content WHERE id = ?')
                                                ->execute($intLastInsertId);

                                            $arrIds[$intLastInsertId] = $objResult->pid;

                                            $this->Input->setGet('act', null);

                                        } else {
                                            $strMessage = $GLOBALS['TL_LANG']['MSC']['clContentPasteInfo'][0];
                                            $this->helper->writeCustomerInfoMessage($strMessage);
                                        }
                                    } else {
                                        $objDb           = $this->database->insertInto($strTable, $arrSet);
                                        $intLastInsertId = $objDb->insertId;

                                        $this->loadDataContainer($strTable);

                                        $dataContainer = 'DC_' . $GLOBALS['TL_DCA'][$strTable]['config']['dataContainer'];
                                        $dc            = new $dataContainer($strTable);

                                        if ($isGrouped) {
                                            $intElemId   = $intLastInsertId;
                                            $strPastePos = 'pasteAfter';

                                            $this->Input->setGet('act', 'copyAll');
                                        } else {
                                            $this->Input->setGet('act', 'copy');
                                        }

                                        if (is_array($GLOBALS['TL_DCA'][$strTable]['config']['oncopy_callback'])) {
                                            foreach ($GLOBALS['TL_DCA'][$strTable]['config']['oncopy_callback'] as $callback) {
                                                $this->import($callback[0]);
                                                $this->{$callback[0]}->{$callback[1]}($intLastInsertId, $dc);
                                            }
                                        }

                                        // HOOK: call the hooks for clipboardCopy
                                        if (isset($GLOBALS['TL_HOOKS']['clipboardCopy']) && is_array($GLOBALS['TL_HOOKS']['clipboardCopy'])) {
                                            foreach ($GLOBALS['TL_HOOKS']['clipboardCopy'] as $arrCallback) {
                                                $this->import($arrCallback[0]);
                                                $this->{$arrCallback[0]}->{$arrCallback[1]}($intLastInsertId, $dc,
                                                    $isGrouped);
                                            }
                                        }

                                        $objResult = $this->Database
                                            ->prepare('SELECT pid FROM tl_content WHERE id = ?')
                                            ->execute($intLastInsertId);

                                        $arrIds[$intLastInsertId] = $objResult->pid;

                                        $this->Input->setGet('act', null);
                                    }
                                } else {
                                    $strMessage = $GLOBALS['TL_LANG']['MSC']['clContentPasteInfo'][1];
                                    $this->helper->writeCustomerInfoMessage($strMessage);
                                }
                            }
                    }
                    break;
                case XMLReader::END_ELEMENT:
                    switch ($objXml->localName) {
                        case 'content':

                            if ($isGrouped && count($arrIds) > 0) {
                                // HOOK: call the hooks for clipboardCopyAll
                                if (isset($GLOBALS['TL_HOOKS']['clipboardCopyAll']) && is_array($GLOBALS['TL_HOOKS']['clipboardCopyAll'])) {
                                    foreach ($GLOBALS['TL_HOOKS']['clipboardCopyAll'] as $arrCallback) {
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
     * @param string    $strTable
     * @param string    $strPastePos
     * @param integer   $intElemId
     * @param boolean   $boolIsChild
     * @param boolean   $isGrouped
     */
    protected function createModule(&$objXml, $strTable, $strPastePos, $intElemId, $boolIsChild, $isGrouped = false)
    {
        $arrIds = array();

        if ($boolIsChild == true) {
            $intId = $intElemId;
        }

        while ($objXml->read()) {
            switch ($objXml->nodeType) {
                case XMLReader::ELEMENT:
                    switch ($objXml->localName) {
                        case 'row':
                            $arrSet = $this->createArrSetForRow($objXml, $intId, $strTable, $strPastePos, $intElemId,
                                $boolIsChild);
                            if (array_key_exists('type', $arrSet)) {
                                if ($this->helper->existsModuleType($arrSet)) {
                                    $objDb           = $this->database->insertInto($strTable, $arrSet);
                                    $intLastInsertId = $objDb->insertId;

                                    $this->loadDataContainer($strTable);

                                    $dataContainer = 'DC_' . $GLOBALS['TL_DCA'][$strTable]['config']['dataContainer'];
                                    $dc            = new $dataContainer($strTable);

                                    if ($isGrouped) {
                                        $intElemId   = $intLastInsertId;
                                        $strPastePos = 'pasteAfter';

                                        $this->Input->setGet('act', 'copyAll');
                                    } else {
                                        $this->Input->setGet('act', 'copy');
                                    }

                                    if (is_array($GLOBALS['TL_DCA'][$strTable]['config']['oncopy_callback'])) {
                                        foreach ($GLOBALS['TL_DCA'][$strTable]['config']['oncopy_callback'] as $callback) {
                                            $this->import($callback[0]);
                                            $this->{$callback[0]}->{$callback[1]}($intLastInsertId, $dc);
                                        }
                                    }

                                    // HOOK: call the hooks for clipboardCopy
                                    if (isset($GLOBALS['TL_HOOKS']['clipboardCopy']) && is_array($GLOBALS['TL_HOOKS']['clipboardCopy'])) {
                                        foreach ($GLOBALS['TL_HOOKS']['clipboardCopy'] as $arrCallback) {
                                            $this->import($arrCallback[0]);
                                            $this->{$arrCallback[0]}->{$arrCallback[1]}($intLastInsertId, $dc,
                                                $isGrouped);
                                        }
                                    }

                                    $objResult = $this->Database
                                        ->prepare('SELECT pid FROM tl_module WHERE id = ?')
                                        ->execute($intLastInsertId);

                                    $arrIds[$intLastInsertId] = $objResult->pid;

                                    $this->Input->setGet('act', null);
                                } else {
                                    $strMessage = sprintf($GLOBALS['TL_LANG']['MSC']['clModulePasteInfo'],
                                        $arrSet['type']);
                                    $this->helper->writeCustomerInfoMessage($strMessage);
                                }
                            }
                    }
                    break;
                case XMLReader::END_ELEMENT:
                    switch ($objXml->localName) {
                        case 'module':

                            if ($isGrouped && count($arrIds) > 0) {
                                // HOOK: call the hooks for clipboardCopyAll
                                if (isset($GLOBALS['TL_HOOKS']['clipboardCopyAll']) && is_array($GLOBALS['TL_HOOKS']['clipboardCopyAll'])) {
                                    foreach ($GLOBALS['TL_HOOKS']['clipboardCopyAll'] as $arrCallback) {
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
     * @param integer   $intId
     * @param string    $strTable
     * @param string    $strPastePos
     * @param integer   $intElemId
     * @param bool      $boolIsChild
     *
     * @return array
     */
    protected function createArrSetForRow(&$objXml, $intId, $strTable, $strPastePos, $intElemId, $boolIsChild = false)
    {
        $arrFields    = $this->helper->getFields($strTable);
        $arrSet       = array();
        $strFieldType = '';
        $strFieldName = '';

        while ($objXml->read()) {
            switch ($objXml->nodeType) {
                case XMLReader::CDATA:
                case XMLReader::TEXT:
                    if (in_array($strFieldName, $arrFields)) {
                        switch ($strFieldName) {
                            case 'pid':
                            case 'id':
                                break;

                            case 'sorting':
                                if ($boolIsChild == true) {
                                    $arrSet['pid'] = $intId;
                                } else {
                                    $arrSorting        = $this->helper->getNewPosition($strTable, $intElemId,
                                        $strPastePos);
                                    $arrSet['pid']     = $arrSorting['pid'];
                                    $arrSet['sorting'] = $arrSorting['sorting'];
                                    break;
                                }

                            default:
                                switch ($strFieldType) {
                                    case 'default':
                                        $strValue              = str_replace($this->helper->arrReplaceWith,
                                            $this->helper->arrSearchFor, $objXml->value);
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
                    if ($objXml->localName == 'field') {
                        $strFieldName = $objXml->getAttribute("name");
                        $strFieldType = $objXml->getAttribute("type");
                    }
                    break;

                case XMLReader::END_ELEMENT:
                    if ($objXml->localName == 'row') {
                        if ($strTable == 'tl_module') {
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
     *
     * @return array
     */
    public function getDetailFileInfo($strFilePath)
    {
        $arrMetaInformation = array();

        $objDomDoc = new DOMDocument();
        $objDomDoc->load($strFilePath);
        $objMetaTags   = $objDomDoc->getElementsByTagName('metatags')->item(0);
        $objMetaChilds = $objMetaTags->childNodes;

        for ($i = 0; $i < $objMetaChilds->length; $i++) {
            $strNodeName                      = $objMetaChilds->item($i)->nodeName;
            $arrMetaInformation[$strNodeName] = $objMetaChilds->item($i)->nodeValue;
        }

        return $arrMetaInformation;
    }

}

?>