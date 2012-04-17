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
 * Class ClipboardDatabase
 */
class ClipboardDatabase extends Backend
{

    /**
     * Current object instance (Singleton)
     * 
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
    final private function __clone(){}

    /**
     * Get instanz of the object (Singelton) 
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
     * Get all fiels from given table
     * 
     * @param string $strTable
     * @return DB_Mysql_Result
     */
    public function getFields($strTable)
    {
        return $this->Database->listFields($strTable);
    }

    /**
     * Return page object from given id
     * 
     * @param integer $intId
     * @return DB_Mysql_Result
     */
    public function getPageObject($intId)
    {
        $objDb = $this->Database
                ->prepare("SELECT * FROM `tl_page` WHERE id = ?")
                ->limit(1)
                ->execute($intId);

        return $objDb;
    }

    /**
     * Return all subpages as object from given id
     * 
     * @param integer $intId
     * @return DB_Mysql_Result
     */
    public function getSubpagesObject($intId)
    {
        $objDb = $this->Database
                ->prepare("SELECT * FROM `tl_page` WHERE pid = ?")
                ->execute($intId);

        return $objDb;
    }

    /**
     * Return article object from given id
     * 
     * @param integer $intId
     * @return DB_Mysql_Result
     */
    public function getArticleObject($intId)
    {
        $objDb = $this->Database
                ->prepare("SELECT * FROM `tl_article` WHERE id = ?")
                ->limit(1)
                ->execute($intId);

        return $objDb;
    }

    /**
     * Return article object from given pid
     * 
     * @param integer $intId
     * @return DB_Mysql_Result
     */
    public function getArticleObjectFromPid($intId)
    {
        $objDb = $this->Database
                ->prepare("SELECT * FROM `tl_article` WHERE pid = ?")
                ->execute($intId);

        return $objDb;
    }

    /**
     * Return article object from given child content id
     * 
     * @param integer $intId
     * @return DB_Mysql_Result
     */
    public function getArticleObjectFromContentId($intId)
    {
        $objDb = $this->Database
                ->prepare("SELECT a.* 
                    FROM `tl_article` AS a
                    LEFT JOIN `tl_content` AS c
                    ON c.pid = a.id
                    WHERE c.id = ?")
                ->limit(1)
                ->execute($intId);

        return $objDb;
    }

    /**
     * Return content object from given pid
     * 
     * @param integer $intId
     * @return DB_Mysql_Result
     */
    public function getContentObjectFromPid($intId)
    {
        $objDb = $this->Database
                ->prepare("SELECT * FROM `tl_content` WHERE pid = ?")
                ->execute($intId);

        return $objDb;
    }

    /**
     * Return content object from given id
     * 
     * @param integer $intId
     * @return DB_Mysql_Result
     */
    public function getContentObject($intId)
    {
        $objDb = $this->Database
                ->prepare("SELECT * FROM `tl_content` WHERE id = ?")
                ->limit(1)
                ->executeUncached($intId);

        return $objDb;
    }

    /**
     * Return object from given table and his id
     * 
     * @param string $strTable
     * @param integer $intId
     * @return DB_Mysql_Result
     */
    public function getDynamicObject($strTable, $intId)
    {
        $objDb = $this->Database
                ->prepare("SELECT * FROM `$strTable` WHERE id = ?")
                ->limit(1)
                ->executeUncached($intId);

        return $objDb;
    }

    /**
     * Insert array set to given table
     * 
     * @param string $strTable
     * @param array $arrSet
     * @return DB_Mysql_Result
     */
    public function insertInto($strTable, $arrSet)
    {
        $query = vsprintf(
            "INSERT IGNORE INTO `%s` (`%s`) VALUES \n(%s)", array(
                $strTable,
                implode('`, `', array_keys($arrSet)),
                implode(',', $arrSet)
            )
        );

        $objDb = $this->Database->query($query);

        return $objDb;
    }

    /**
     * Get the min sorting from given pid
     * 
     * @param string $strTable
     * @param integer $intId
     * @return DB_Mysql_Result
     */
    public function getSorting($strTable, $intId)
    {
        $objDb = $this->Database
                ->prepare("SELECT MIN(sorting) AS sorting FROM " . $strTable . " WHERE pid=?")
                ->executeUncached($intId);

        return $objDb;
    }

    /**
     * Get the next sorting from given id
     * 
     * @param string $strTable
     * @param integer $intId
     * @param integer $intSorting
     * @return DB_Mysql_Result 
     */
    public function getNextSorting($strTable, $intId, $intSorting)
    {
        $objDb = $this->Database
                ->prepare("SELECT MIN(sorting) AS sorting FROM " . $strTable . " WHERE pid = ? AND sorting > ?")
                ->executeUncached($intId, $intSorting);

        return $objDb;
    }

    /**
     * Get elements sorting from given pid ordert by sorting
     * 
     * @param string $strTable
     * @param interer $intId
     * @return DB_Mysql_Result
     */
    public function getSortingElem($strTable, $intId)
    {
        $objDb = $this->Database
                ->prepare("SELECT id, sorting FROM " . $strTable . " WHERE pid = ? ORDER BY sorting")
                ->executeUncached($intId);

        return $objDb;
    }

    /**
     * Update sorting
     * 
     * @param string $strTable
     * @param integer $intSorting
     * @param integer $intId 
     */
    public function updateSorting($strTable, $intSorting, $intId)
    {
        $this->Database
                ->prepare("UPDATE " . $strTable . " SET sorting = ? WHERE id = ?")
                ->execute($intSorting, $intId);
    }

}

?>