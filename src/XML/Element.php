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

use MenAtWork\ClipboardBundle\Helper\Base as helperBase;
use MenAtWork\ClipboardBundle\Xml\Reader;
use MenAtWork\ClipboardBundle\Xml\Writer;

/**
 * Class ClipboardXmlElement
 */
class Element
{

    /**
     * Contains some helper functions
     *
     * @var helperBase
     */
    protected $helper;

    /**
     * Contains all function to write xml
     *
     * @var Writer
     */
    protected $xmlWriter;

    /**
     * Contains all functions to read xml
     *
     * @var Reader
     */
    protected $xmlReader;

    /**
     * Contains all file operations
     *
     * @var \Files
     */
    protected $files;

    /**
     * Variables
     */
    protected $title         = null;
    protected $table         = null;
    protected $favorite      = null;
    protected $children      = null;
    protected $group         = null;
    protected $groupCount    = null;
    protected $attribute     = null;
    protected $filename      = null;
    protected $path          = null;
    protected $checksum      = null;
    protected $timeStemp     = null;
    protected $encryptionKey = null;

    /**
     * Construct object
     *
     * @param string $strFileName
     * @param string $strPath
     */
    public function __construct($strFileName, $strPath)
    {
        $this->filename = $strFileName;
        $this->path     = $strPath;
    }

    /**
     * @param \MenAtWork\ClipboardBundle\Helper\Base $helper
     *
     * @return Element
     */
    public function setHelper(\MenAtWork\ClipboardBundle\Helper\Base $helper): Element
    {
        $this->helper = $helper;

        return $this;
    }

    /**
     * @param \MenAtWork\ClipboardBundle\Xml\Writer $xmlWriter
     *
     * @return Element
     */
    public function setXmlWriter(\MenAtWork\ClipboardBundle\Xml\Writer $xmlWriter): Element
    {
        $this->xmlWriter = $xmlWriter;

        return $this;
    }

    /**
     * @param \MenAtWork\ClipboardBundle\Xml\Reader $xmlReader
     *
     * @return Element
     */
    public function setXmlReader(\MenAtWork\ClipboardBundle\Xml\Reader $xmlReader): Element
    {
        $this->xmlReader = $xmlReader;

        return $this;
    }

    /**
     * @param \Files $files
     *
     * @return Element
     */
    public function setFiles(\Files $files): Element
    {
        $this->files = $files;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        if (is_null($this->title)) {
            $this->_setDetailFileInfo();
        }

        return $this->title;
    }

    /**
     * Set title
     *
     * @param string $title
     *
     * @return Element
     */
    public function setTitle($title)
    {
        if (!is_null($this->title) || $this->title != $title) {
            $this->_setNewTitle($title);
            $strNewFileName = $this->_setNewFileName('title', $title);
            $this->files->rename(
                $this->path . '/' . $this->filename, $this->path . '/' . $strNewFileName
            );
            $this->filename = $strNewFileName;
            $this->_setFileInfo();
        }
        $this->title = $title;

        return $this;
    }

    /**
     * Get table
     *
     * @return string
     */
    public function getTable()
    {
        if (is_null($this->table)) {
            $this->_setFileInfo();
        }

        return $this->table;
    }

    /**
     * Set table
     *
     * @param string $table
     *
     * @return Element
     */
    public function setTable($table)
    {
        $this->table = $table;

        return $this;
    }

    /**
     * Get favorite
     *
     * @return boolean
     */
    public function getFavorite()
    {
        if (is_null($this->favorite)) {
            $this->_setFileInfo();
        }

        return $this->favorite;
    }

    /**
     * Set favorite
     *
     * @param boolean $favorite
     *
     * @return Element
     */
    public function setFavorite($favorite)
    {
        if (!is_null($this->favorite) || $this->favorite != $favorite) {
            $strNewFileName = $this->_setNewFileName('favorite', (($favorite) ? 'F' : 'N'));
            $this->files->rename(
                $this->path . '/' . $this->filename, $this->path . '/' . $strNewFileName
            );
            $this->filename = $strNewFileName;
            $this->_setFileInfo();
        }
        $this->favorite = $favorite;

        return $this;
    }

    /**
     * Get childs
     *
     * @return boolean
     */
    public function getChildren()
    {
        if (is_null($this->children)) {
            $this->_setFileInfo();
        }

        return $this->children;
    }

    /**
     * Set childs
     *
     * @param boolean $childs
     *
     * @return Element
     */
    public function setChildren($childs)
    {
        $this->children = $childs;

        return $this;
    }

    /**
     * Get group lable
     *
     * @return string
     */
    public function getGroup()
    {
        if (is_null($this->group)) {
            $this->_setFileInfo();
        }

        return (($this->group) ? $GLOBALS['TL_LANG']['MSC']['clipboardGroup'] : '');
    }

    public function getGroupCount()
    {
        if (is_null($this->groupCount)) {
            $this->_setDetailFileInfo();
        }

        return $this->groupCount;
    }

    /**
     * Get attribute lable
     *
     * @return type
     */
    public function getAttribute()
    {
        if (is_null($this->attribute)) {
            $this->_setDetailFileInfo();
        }

        return $this->attribute;
    }

    /**
     * Get timestemp
     *
     * @return string
     */
    public function getTimeStemp()
    {
        if (is_null($this->timeStemp)) {
            $this->_setFileInfo();
        }

        return $this->timeStemp;
    }

    /**
     * Get encryptionKey
     *
     * @return string
     */
    public function getEncryptionKey()
    {
        if (is_null($this->encryptionKey)) {
            $this->_setDetailFileInfo();
        }

        return $this->encryptionKey;
    }

    /**
     * Get filename
     *
     * @return string
     */
    public function getFileName()
    {
        return $this->filename;
    }

    /**
     * Get path to file. If set param full path whould return with TL_ROOT
     *
     * @return string
     */
    public function getPath($strType = null)
    {
        if ($strType == 'full') {
            return TL_ROOT . '/' . $this->path;
        }

        return $this->path;
    }

    /**
     * Get path and file. If set param full path and file whould return with TL_ROOT
     *
     * @return string
     */
    public function getFilePath($strType = null)
    {
        if ($strType == 'full') {
            return TL_ROOT . '/' . $this->path . '/' . $this->filename;
        }

        return $this->path . '/' . $this->filename;
    }

    /**
     * Get hash
     *
     * @return string
     */
    public function getHash()
    {
        $arrFileName = $this->helper->getArrFromFileName($this->getFilePath());
        unset($arrFileName[2]);

        return crc32(implode(',', $arrFileName));
    }

    /**
     * Get checksum
     *
     * @return string
     */
    public function getChecksum()
    {
        if (is_null($this->checksum)) {
            $this->_setDetailFileInfo();
        }

        return $this->checksum;
    }

    /**
     * Set new filename. Editable is favorite and title
     *
     * @param string $strEditType
     * @param string $strValue
     *
     * @return string
     */
    private function _setNewFileName($strEditType, $strValue)
    {
        $arrFileName = $this->helper->getArrFromFileName($this->getFilePath());
        switch ($strEditType) {
            case 'favorite':
                $arrFileName[2] = $strValue;
                break;
            case 'title':
                $arrFileName[5] = standardize($strValue);
                break;
        }

        return implode(',', $arrFileName) . '.xml';
    }

    /**
     * Set default information from filename
     */
    protected function _setFileInfo()
    {
        $arrFileName     = $this->helper->getArrFromFileName($this->getFilePath());
        $this->table     = 'tl_' . $arrFileName[0];
        $this->timeStemp = $arrFileName[1];
        $this->favorite  = (($arrFileName[2] == 'F') ? 1 : 0);
        $this->children  = (($arrFileName[3] == 'C') ? 1 : 0);
        $this->group     = (($arrFileName[4] == 'G') ? 1 : 0);
    }

    /**
     * Set detailt information from file meta description
     */
    protected function _setDetailFileInfo()
    {
        $arrMetaInformation = $this->xmlReader->getDetailFileInfo($this->getFilePath('full'));
        $tmpTitle           = $arrMetaInformation['title'];
        if (strpos($tmpTitle, '(' . $GLOBALS['TL_LANG']['MSC']['clipboardGroup'] . ')') !== false) {
            $tmpTitle = trim(str_replace('(' . $GLOBALS['TL_LANG']['MSC']['clipboardGroup'] . ')', '', $tmpTitle));
        }

        $this->title         = $tmpTitle;
        $this->groupCount    = $arrMetaInformation['group_count'];
        $this->attribute     = $arrMetaInformation['attribute'];
        $this->checksum      = $arrMetaInformation['checksum'];
        $this->encryptionKey = $arrMetaInformation['encryptionKey'];
    }

    /**
     * Set new title in file header
     */
    protected function _setNewTitle($title)
    {
        $this->xmlWriter->setNewTitle($this->getFilePath('full'), $this->getFilePath(), $title);
    }

}