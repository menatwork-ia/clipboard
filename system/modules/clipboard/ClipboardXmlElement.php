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
 * Class ClipboardXmlElement
 */
class ClipboardXmlElement
{

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
    protected $_filename = NULL;
    protected $_path = NULL;

    /**
     * Construct object
     * 
     * @param string $strFileName
     * @param string $strPath 
     */
    public function __construct($strFileName, $strPath)
    {
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
            $this->_setFileInfo();
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
        if (!is_null($this->_title) && $this->_title != $title)
        {
            $strNewFileName = $this->_setNewFileName('title', $title);
            $this->_objFiles->rename(
                    $this->_path . '/' . $this->_filename, $this->_path . '/' . $strNewFileName
            );
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
        if (!is_null($this->_favorite) && $this->_favorite != $favorite)
        {
            $strNewFileName = $this->_setNewFileName('favorite', (($favorite) ? 'F' : 'N'));
            $this->_objFiles->rename(
                    $this->_path . '/' . $this->_filename, $this->_path . '/' . $strNewFileName
            );
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
        $arrFileName = explode('_', $this->_filename);
        unset($arrFileName[2]);
        return crc32(implode('_', $arrFileName));
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
        $arrFileName = explode('_', $this->_filename);
        switch ($strEditType)
        {
            case 'favorite':
                $arrFileName[2] = $strValue;
                break;
            case 'title':
                $arrFileName[4] = base64_encode($strValue);
                break;
        }

        return implode('_', $arrFileName);
    }

    /**
     * Set default information from filename 
     */
    protected function _setFileInfo()
    {
        $arrFileName = explode('_', $this->_filename);
        $this->_table = 'tl_' . $arrFileName[0];
        $this->_favorite = (($arrFileName[2] == 'F') ? 1 : 0);
        $this->_childs = (($arrFileName[3] == 'C') ? 1 : 0);
        $this->_title = base64_decode($arrFileName[4]);
    }

}

?>