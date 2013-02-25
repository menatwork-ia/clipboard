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
 * Class ClipboardXmlElement
 */
class ClipboardXmlElement
{

    /**
     * Contains some helper functions
     * 
     * @var object 
     */
    protected $_objHelper;    
    
    /**
     * Contains all function to write xml
     * 
     * @var ClipboardXmlWriter
     */
    protected $_objXmlWriter;    
    
    /**
     * Contains all functions to read xml
     * 
     * @var ClipboardXmlReader
     */
    protected $_objXmlReader;    
    
    /**
     * Contains all file operations
     * 
     * @var Files
     */
    protected $_objFiles;

    /**
     * Variables 
     */
    protected $_title = NULL;
    protected $_table = NULL;
    protected $_favorite = NULL;
    protected $_childs = NULL;
    protected $_group = NULL;
    protected $_groupCount = NULL;
    protected $_attribute = NULL;
    protected $_filename = NULL;
    protected $_path = NULL;
    protected $_checksum = NULL;
    protected $_timeStemp = NULL;
    protected $_encryptionKey = NULL;

    /**
     * Construct object
     * 
     * @param string $strFileName
     * @param string $strPath 
     */
    public function __construct($strFileName, $strPath)
    {
        $this->_objHelper = ClipboardHelper::getInstance();
        $this->_objXmlWriter = ClipboardXmlWriter::getInstance();
        $this->_objXmlReader = ClipboardXmlReader::getInstance();
        $this->_objFiles = Files::getInstance();

        $this->_filename = $strFileName;
        $this->_path = $strPath;
    }

    /**
     * Get title
     * 
     * @return string 
     */
    public function getTitle()
    {
        if (is_null($this->_title))
        {
            $this->_setDetailFileInfo();
        }
        return $this->_title;
    }

    /**
     * Set title
     * 
     * @param string $title
     * @return ClipboardXmlElement 
     */
    public function setTitle($title)
    {
        if (!is_null($this->_title) || $this->_title != $title)
        {
            $this->_setNewTitle($title);
            $strNewFileName = $this->_setNewFileName('title', $title);
            $this->_objFiles->rename(
                    $this->_path . '/' . $this->_filename, $this->_path . '/' . $strNewFileName
            );
            $this->_filename = $strNewFileName;
            $this->_setFileInfo();            
        }
        $this->_title = $title;
        return $this;
    }

    /**
     * Get table
     * 
     * @return string 
     */
    public function getTable()
    {
        if (is_null($this->_table))
        {
            $this->_setFileInfo();
        }
        return $this->_table;
    }

    /**
     * Set table
     * 
     * @param string $table
     * @return ClipboardXmlElement 
     */
    public function setTable($table)
    {
        $this->_table = $table;
        return $this;
    }

    /**
     * Get favorite
     * 
     * @return boolean 
     */
    public function getFavorite()
    {
        if (is_null($this->_favorite))
        {
            $this->_setFileInfo();
        }
        return $this->_favorite;
    }

    /**
     * Set favorite
     * 
     * @param boolean $favorite
     * @return ClipboardXmlElement 
     */
    public function setFavorite($favorite)
    {
        if (!is_null($this->_favorite) || $this->_favorite != $favorite)
        {
            $strNewFileName = $this->_setNewFileName('favorite', (($favorite) ? 'F' : 'N'));
            $this->_objFiles->rename(
                    $this->_path . '/' . $this->_filename, $this->_path . '/' . $strNewFileName
            );
            $this->_filename = $strNewFileName;
            $this->_setFileInfo();
        }
        $this->_favorite = $favorite;
        return $this;
    }

    /**
     * Get childs
     * 
     * @return boolean
     */
    public function getChilds()
    {
        if (is_null($this->_childs))
        {
            $this->_setFileInfo();
        }
        return $this->_childs;
    }

    /**
     * Set childs
     * 
     * @param boolean $childs
     * @return ClipboardXmlElement 
     */
    public function setChilds($childs)
    {
        $this->_childs = $childs;
        return $this;
    }
    
    /**
     * Get group lable
     * 
     * @return string
     */    
    public function getGroup()
    {
        if (is_null($this->_group))
        {
            $this->_setFileInfo();
        }
        return (($this->_group) ? $GLOBALS['TL_LANG']['MSC']['clipboardGroup'] : '');
    }
    
    public function getGroupCount()
    {
        if (is_null($this->_groupCount))
        {
            $this->_setDetailFileInfo();
        }
        return $this->_groupCount;
    }
    
    /**
     * Get attribute lable
     * 
     * @return type
     */
    public function getAttribute()
    {
        if (is_null($this->_attribute))
        {
            $this->_setDetailFileInfo();
        }
        return $this->_attribute;
    }

    /**
     * Get timestemp
     * 
     * @return string
     */
    public function getTimeStemp()
    {
        if (is_null($this->_timeStemp))
        {
            $this->_setFileInfo();
        }
        return $this->_timeStemp;
    }
    
    /**
     * Get encryptionKey
     * 
     * @return string
     */
    public function getEncryptionKey()
    {
        if (is_null($this->_encryptionKey))
        {
            $this->_setDetailFileInfo();
        }
        return $this->_encryptionKey;
    }
        
    /**
     * Get filename
     * 
     * @return string 
     */
    public function getFileName()
    {
        return $this->_filename;
    }

    /**
     * Get path to file. If set param full path whould return with TL_ROOT
     * 
     * @return string 
     */
    public function getPath($strType = NULL)
    {
        if ($strType == 'full')
        {
            return TL_ROOT . '/' . $this->_path;
        }
        return $this->_path;
    }

    /**
     * Get path and file. If set param full path and file whould return with TL_ROOT
     * 
     * @return string 
     */
    public function getFilePath($strType = NULL)
    {
        if ($strType == 'full')
        {
            return TL_ROOT . '/' . $this->_path . '/' . $this->_filename;
        }
        return $this->_path . '/' . $this->_filename;
    }

    /**
     * Get hash
     * 
     * @return string
     */
    public function getHash()
    {
        $arrFileName = $this->_objHelper->getArrFromFileName($this->getFilePath());
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
        if (is_null($this->_checksum))
        {
            $this->_setDetailFileInfo();
        }
        return $this->_checksum;
    }

    /**
     * Set new filename. Editable is favorite and title
     * 
     * @param string $strEditType
     * @param string $strValue
     * @return string 
     */
    private function _setNewFileName($strEditType, $strValue)
    {        
        $arrFileName = $this->_objHelper->getArrFromFileName($this->getFilePath());
        switch ($strEditType)
        {
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
        $arrFileName = $this->_objHelper->getArrFromFileName($this->getFilePath());
        $this->_table = 'tl_' . $arrFileName[0];
        $this->_timeStemp = $arrFileName[1];
        $this->_favorite = (($arrFileName[2] == 'F') ? 1 : 0);
        $this->_childs = (($arrFileName[3] == 'C') ? 1 : 0);
        $this->_group = (($arrFileName[4] == 'G') ? 1 : 0);
    }
    
    /**
     * Set detailt information from file meta description 
     */
    protected function _setDetailFileInfo()
    {
        $arrMetaInformation = $this->_objXmlReader->getDetailFileInfo($this->getFilePath('full'));
        $tmpTitle = $arrMetaInformation['title'];
        if(strpos($tmpTitle, '(' . $GLOBALS['TL_LANG']['MSC']['clipboardGroup'] . ')') !== false) $tmpTitle = trim(str_replace('(' . $GLOBALS['TL_LANG']['MSC']['clipboardGroup'] . ')', '', $tmpTitle));
        
        $this->_title = $tmpTitle;
        $this->_groupCount = $arrMetaInformation['group_count'];
        $this->_attribute = $arrMetaInformation['attribute'];
        $this->_checksum = $arrMetaInformation['checksum'];
        $this->_encryptionKey = $arrMetaInformation['encryptionKey'];
    }
    
    /**
     * Set new title in file header
     */
    protected function _setNewTitle($title)
    {
        $this->_objXmlWriter->setNewTitle($this->getFilePath('full'), $this->getFilePath(), $title);
    }

}

?>