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
 * Class ClipboardXml
 */
class ClipboardXml extends Backend
{

    /**
     * Current object instance (Singleton)
     * @var ClipboardXml
     */
    protected static $_objInstance = NULL;

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
    protected $_arrClipboardElements;

    /**
     * Prevent constructing the object (Singleton)
     */
    protected function __construct()
    {
        parent::__construct();
        $this->import('BackendUser', 'User');
        $this->_objXmlReader = ClipboardXmlReader::getInstance();
        $this->_objXmlWriter = ClipboardXmlWriter::getInstance();
        $this->_objHelper = ClipboardHelper::getInstance();
        $this->_objFiles = Files::getInstance();

        $this->_createClipboardFromFiles();
    }

    /**
     * Prevent cloning of the object (Singleton)
     */
    final private function __clone(){}

    /**
     * Get instanz of the object (Singelton) 
     *
     * @return ClipboardXml 
     */
    public static function getInstance()
    {
        if (self::$_objInstance == NULL)
        {
            self::$_objInstance = new ClipboardXml();
        }
        return self::$_objInstance;
    }

    /**
     * Set the current favorite to the given position in action with the id
     * 
     * @param string $strPastePos
     * @param integer $intId 
     */
    public function read($strPastePos, $intId)
    {
        $this->_objXmlReader->readXml($this->getFavorite(), $strPastePos, $intId);
    }

    /**
     * Unfavor all clipboard elements and write given array to xml file
     * 
     * @param array $arrSet
     */
    public function write($arrSet)
    {
        $objFile = $this->getFavorite();
        $this->unFavorAll();
        $arrSet['filename'] = $this->_getFileName($arrSet);
        $arrSet['path'] = $this->getPath();
        if(!$this->_objXmlWriter->writeXml($arrSet, $this->_arrClipboardElements))
        {
            $objFile->setFavorite(TRUE);
        }
    }

    /**
     * Delete xml file
     * 
     * @param string $strHash 
     */
    public function deleteFile($strHash)
    {
        if (is_object($this->_arrClipboardElements[$strHash]))
        {
            $objFile = $this->_arrClipboardElements[$strHash];
            if ($this->_fileExists($objFile->getFileName()))
            {
                $this->_objFiles->delete($this->getPath() . '/' . $objFile->getFileName());
            }
        }
    }

    /**
     * Edit all given titles
     * 
     * @param array $arrTitles 
     */
    public function editTitle($arrTitles)
    {            
        foreach ($arrTitles AS $hash => $strTitle)
        {
            if (isset($this->_arrClipboardElements[$hash]))
            {
                $this->_arrClipboardElements[$hash]->setTitle($strTitle);
            }
        }
    }

    /**
     * Check if the given file exists and return boolean
     * 
     * @param string $strFileName
     * @return boolean 
     */
    protected function _fileExists($strFileName)
    {
        if (file_exists(TL_ROOT . '/' . $this->getPath() . '/' . $strFileName))
        {
            return TRUE;
        }
        return FALSE;
    }

    /**
     * Return if clipboard has elements or not
     * 
     * @return boolean 
     */
    public function hasElements()
    {
        if (count($this->_arrClipboardElements) > 0)
        {
            return TRUE;
        }
        return FALSE;
    }

    /**
     * Return if clipboard has favorite elements 
     * 
     * return boolean
     */
    public function hasFavorite()
    {
        if (is_object($this->getFavorite()))
        {
            return TRUE;
        }
        return FALSE;
    }

    /**
     * Get favorite
     * 
     * @return ClipboardXmlElement
     */
    public function getFavorite()
    {
        $arrFavorits = array();
        if ($this->hasElements())
        {
            foreach ($this->_arrClipboardElements AS $objFile)
            {
                if ($objFile->getFavorite())
                {
                    $arrFavorits[] =  $objFile;
                }
            }
            
            if(count($arrFavorits) == 1)
            {
                return $arrFavorits[0];
            }
            else
            {
                $objFileNewest = reset($this->_arrClipboardElements);
                foreach ($this->_arrClipboardElements AS $objFile)
                {
                    if($objFileNewest->getTimeStemp() < $objFile->getTimeStemp())
                    {
                        $objFileNewest = $objFile;
                    }
                }
                $this->unFavorAll();
                $objFileNewest->setFavorite(TRUE);                
                return $objFileNewest;
            }
        }
        return FALSE;
    }

    /**
     * Set given file hast to favorite and unfavor all other
     * 
     * @param type $hash 
     */
    public function setFavor($hash)
    {
        $this->unFavorAll();
        $this->_arrClipboardElements[$hash]->setFavorite(TRUE);
    }

    /**
     * Unfavor all clipboard elements 
     */
    public function unFavorAll()
    {
        if ($this->hasElements())
        {
            foreach ($this->_arrClipboardElements AS $objFile)
            {
                $objFile->setFavorite(FALSE);
            }
        }
    }

    /**
     * Get all clipboard elements
     * 
     * @return array
     */
    public function getElements()
    {
        return $this->_arrClipboardElements;
    }

    /**
     * Fill the clipboard from files 
     */
    protected function _createClipboardFromFiles()
    {
        $arrFiles = scan(TL_ROOT . '/' . $this->getPath());
        if (is_array($arrFiles) && count($arrFiles) > 0)
        {
            foreach ($arrFiles AS $strFileName)
            {
                $arrFile = $this->_objHelper->getArrFromFileName($this->getPath() . '/' . $strFileName);
                if ($arrFile[0] != $this->_objHelper->getPageType())
                {
                    continue;
                }

                if ($this->_fileExists($strFileName))
                {
                    $objFile = new ClipboardXmlElement($strFileName, $this->getPath());
                }
                $this->_arrClipboardElements[$objFile->getHash()] = $objFile;
            }
        }
    }

    /**
     * Return path to clipboard files for current user
     * 
     * @return string
     */
    public function getPath()
    {
        $objUserFolder = new Folder($GLOBALS['TL_CONFIG']['uploadPath'] . '/clipboard/' . $this->User->username);
        $this->_protect($objUserFolder->value);
        return $objUserFolder->value;
    }

    /**
     * Protect the folder by adding an .htaccess file
     */
    protected function _protect($strFolder)
    {
        if (!file_exists(TL_ROOT . '/' . $strFolder . '/.htaccess'))
        {
            $objFile = new File($strFolder . '/.htaccess');
            $objFile->write("order deny,allow\ndeny from all");
            $objFile->close();
        }
    }

    /**
     * Get new filename
     * 
     * @param array $arrSet
     * @return string 
     */
    protected function _getFileName($arrSet)
    {
        $arrFileName = array(
            $this->_objHelper->getPageType(),
            time(),
            'F',
            (($arrSet['childs']) ? 'C' : 'NC'),
            (($arrSet['grouped']) ? 'G' : 'NG'),
            standardize($arrSet['title'])
        );

        return implode(',', $arrFileName) . '.xml';
    }
    
}

?>