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
     * @param mixed $mixedId
     * @return DB_Mysql_Result
     */
    public function getPageObject($mixedId)
    {        
        if(is_array($mixedId))
        {
            $strQuery = "SELECT * FROM `tl_page` WHERE id IN (" . implode(', ', $mixedId) . ") ORDER BY sorting";
            
            $objDb = $this->Database
                ->prepare($strQuery)
                ->executeUncached();   
        }
        else
        {
            $strQuery = "SELECT * FROM `tl_page` WHERE id = ?";
            
            $objDb = $this->Database
                ->prepare($strQuery)
                ->limit(1)
                ->executeUncached($mixedId);            
        }               

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
                ->prepare("SELECT * FROM `tl_page` WHERE pid = ? ORDER BY sorting")
                ->executeUncached($intId);

        return $objDb;
    }

    /**
     * Return article object from given id
     * 
     * @param mixed $mixedId
     * @return DB_Mysql_Result
     */
    public function getArticleObject($mixedId)
    {        
        if(is_array($mixedId))
        {
            $strQuery = "SELECT * FROM `tl_article` WHERE id IN (" . implode(', ', $mixedId) . ") ORDER BY sorting";
            
            $objDb = $this->Database
                ->prepare($strQuery)
                ->executeUncached();   
        }
        else
        {
            $strQuery = "SELECT * FROM `tl_article` WHERE id = ?";
            
            $objDb = $this->Database
                ->prepare($strQuery)
                ->limit(1)
                ->executeUncached($mixedId);            
        }               

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
                ->prepare("SELECT * FROM `tl_article` WHERE pid = ? ORDER BY sorting")
                ->executeUncached($intId);

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
                ->executeUncached($intId);

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
                ->prepare("SELECT * FROM `tl_content` WHERE pid = ? ORDER BY sorting")
                ->executeUncached($intId);

        return $objDb;
    }

    /**
     * Return content object from given id
     * 
     * @param integer $mixedId
     * @return DB_Mysql_Result
     */
    public function getContentObject($mixedId)
    {
        if(is_array($mixedId))
        {
            $strQuery = "SELECT * FROM `tl_content` WHERE id IN (" . implode(', ', $mixedId) . ") ORDER BY sorting";
            
            $objDb = $this->Database
                ->prepare($strQuery)
                ->executeUncached();   
        }
        else
        {
            $strQuery = "SELECT * FROM `tl_content` WHERE id = ?";
            
            $objDb = $this->Database
                ->prepare($strQuery)
                ->limit(1)
                ->executeUncached($mixedId);            
        }               

        return $objDb;
    }
    
    /**
     * Return theme object from given child module id
     * 
     * @param integer $intId
     * @return DB_Mysql_Result
     */
    public function getThemeObjectFromModuleId($intId)
    {
        $objDb = $this->Database
                ->prepare("SELECT t.* 
                    FROM `tl_theme` AS t
                    LEFT JOIN `tl_module` AS m
                    ON m.pid = t.id
                    WHERE m.id = ?")
                ->limit(1)
                ->executeUncached($intId);

        return $objDb;
    }    
    
    /**
     * Return module object from given id
     * 
     * @param integer $mixedId
     * @return DB_Mysql_Result
     */    
    public function getModuleObject($mixedId)
    {
        if(is_array($mixedId))
        {
            $strQuery = "SELECT * FROM `tl_module` WHERE id IN (" . implode(', ', $mixedId) . ")";
            
            $objDb = $this->Database
                ->prepare($strQuery)
                ->executeUncached();   
        }
        else
        {
            $strQuery = "SELECT * FROM `tl_module` WHERE id = ?";
            
            $objDb = $this->Database
                ->prepare($strQuery)
                ->limit(1)
                ->executeUncached($mixedId);            
        }               

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
    
    /**
     * Update alias
     * 
     * @param string $strTable
     * @param integer $intAlias
     * @param integer $intId 
     */
    public function updateAlias($strTable, $intAlias, $intId)
    {
        $this->Database
                ->prepare("UPDATE " . $strTable . " SET alias = ? WHERE id = ?")
                ->execute($intAlias, $intId);
    }    

}

?>