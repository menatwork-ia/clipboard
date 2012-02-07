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
 * Class ClipboardDatabase
 */
class ClipboardDatabase extends Backend
{

    /**
     * Current object instance (Singleton)
     * @var ClipboardDatabase
     */
    protected static $objInstance;

    /**
     * Prevent constructing the object (Singleton)
     */
    protected function __construct()
    {
        parent::__construct();
    }

    /**
     * Prevent cloning of the object (Singleton)
     */
    final private function __clone()
    {
        
    }

    /**
     *
     * @return ClipboardDatabase 
     */
    public static function getInstance()
    {
        if (!is_object(self::$objInstance))
        {
            self::$objInstance = new ClipboardDatabase();
        }
        return self::$objInstance;
    }

    /**
     * Return page object from id
     * 
     * @param integer $intId
     * @return DB_Mysql_Result
     */
    public function getPageObject($intId)
    {
        $objDb = $this->Database
                ->prepare("
                    SELECT *
                    FROM `tl_page`
                    WHERE id = ?")
                ->execute($intId);

        return $objDb;
    }
    
    /**
     * Return article object from id
     * 
     * @param integer $intId
     * @return DB_Mysql_Result
     */    
    public function getArticleObject($intId)
    {
        $objDb = $this->Database
                ->prepare("
                    SELECT *
                    FROM `tl_article`
                    WHERE id = ?")
                ->execute($intId);

        return $objDb;        
    }
    
    /**
     * Return article object from pid
     * 
     * @param integer $intId
     * @return DB_Mysql_Result
     */    
    public function getArticleObjectFromPid($intId)
    {
        $objDb = $this->Database
                ->prepare("
                    SELECT *
                    FROM `tl_article`
                    WHERE pid = ?")
                ->execute($intId);

        return $objDb;        
    }

}

?>