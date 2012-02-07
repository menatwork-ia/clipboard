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
    protected static $objInstance;
    protected $objBeUser;
    protected $objClUserFolder;
    protected $objDatabase;    
    protected $objFiles;

    /**
     * Prevent constructing the object (Singleton)
     */
    protected function __construct()
    {
        $this->objBeUser = BackendUser::getInstance();
        $this->objDatabase = ClipboardDatabase::getInstance();
        //$this->objFiles = Fil

        $dir = 'tl_files' . '/' . 'clipboard' . '/' . $this->objBeUser->username;
        $this->objClUserFolder = $this->createFolderStructure($dir);
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
        if (!is_object(self::$objInstance))
        {
            self::$objInstance = new ClipboardXml();
        }
        return self::$objInstance;
    }

    private function createFolderStructure($strFolder)
    {
        $folder = new Folder($strFolder);
        return $folder;
    }

    public function writeXml($strTable, $intId, $strTitle)
    {
        // Build XML --------------------------------------------------------
        // XML Config
        $objXml = new XmlWriter();
        $objXml->openMemory();
        $objXml->setIndent(true);
        $objXml->setIndentString("\t");

        // XML Start
        $objXml->startDocument('1.0', 'UTF-8');
        $objXml->startElement('clipboard');

        // Meta
        $objXml->startElement('metatags');

        $time = time();

        $objXml->writeElement('create_unix', $time);
        $objXml->writeElement('create_date', date('Y-m-d', $time));
        $objXml->writeElement('create_time', date('H:i', $time));
        $objXml->writeElement('main_language', $arrFile['language']);

        $objXml->endElement(); // End metatags

        switch ($strTable)
        {
            case 'tl_page':
                $objXml = $this->writeTlPage($intId, $objXml);
                break;
            case 'tl_article':
                $objXml = $this->writeTlArticle($intId, $objXml);
                break;
            case 'tl_content':
                $objXml = $this->writeTlContent($intId, $objXml);
                break;
        }
        
        $objXml->endElement();
        
        $strXml = $objXml->outputMemory();
        
        $filename = substr($strTable, 3) . '_' . strtolower($strTitle) . '.xml';
        $objFile = new File($this->objClUserFolder->value . '/' . $filename);
        $write = $objFile->write($strXml);
        if($write)
        {
            $objFile->close;
            return TRUE;
        }
        return FALSE;
    }

    protected function writeTlPage($intId, $objXml)
    {
        $arrPage = $this->objDatabase->getPageObject($intId)->fetchAllAssoc();
        
        if(count($arrPage) > 0)
        {            
            foreach($arrPage AS $page)
            {
                // Start writing tl_page
                $objXml->startElement('tl_page');
                
                foreach($page AS $field => $value)
                {
                    // start writing fields
                    $objXml->startElement('field');
                    $objXml->writeAttribute("name", $field);
                    $objXml->writeElement('value', $value);
                    $objXml->endElement();                
                }
                
                $objXml = $this->writeTlArticle($intId, $objXml, TRUE);
                
                $objXml->endElement();
            }
        }
        
        return $objXml;
        
    }

    protected function writeTlArticle($intId, $objXml, $isChild = FALSE)
    {
        if($isChild)
        {
            $arrArticle = $this->objDatabase->getArticleObjectFromPid($intId)->fetchAllAssoc();            
        }
        else
        {
            $arrArticle = $this->objDatabase->getArticleObject($intId)->fetchAllAssoc();
        }
    }

    protected function writeTlContent($intId, $objXml, $isChild = FALSE)
    {
        
    }

}