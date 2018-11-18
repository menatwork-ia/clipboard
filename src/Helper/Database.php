<?php

/**
 * Contao Open Source CMS
 *
 * @copyright  MEN AT WORK 2018
 * @package    clipboard
 * @author     Stefan Heimes <stefan_heimes@hotmail.com>
 * @license    GNU/LGPL
 */

namespace MenAtWork\ClipboardBundle\Helper;

/**
 * Class ClipboardDatabase
 */
class Database
{
    /**
     * The contao database.
     *
     * @var \Contao\Database
     */
    private $database;

    /**
     * Database constructor.
     *
     * @param ContaoBridge $contaoBridge
     */
    public function __construct($contaoBridge)
    {
        $this->database = $contaoBridge->getDatabase();
    }

    /**
     * Get all fields from given table
     *
     * @param string $tableName The name of the table.
     *
     * @return array The field list for the table.
     */
    public function getFields($tableName)
    {
        return $this->database->listFields($tableName);
    }

    /**
     * Return page object from given id
     *
     * @param mixed $id
     *
     * @return \Contao\Database\Result
     */
    public function getPageObject($id)
    {
        if (is_array($id)) {
            $strQuery = sprintf(
                "SELECT * FROM `tl_page` WHERE id IN (%s) ORDER BY sorting",
                implode(', ', $id)
            );
            $objDb    = $this->database
                ->prepare($strQuery)
                ->execute();
        } else {
            $strQuery = "SELECT * FROM `tl_page` WHERE id = ?";
            $objDb    = $this->database
                ->prepare($strQuery)
                ->limit(1)
                ->execute($id);
        }

        return $objDb;
    }

    /**
     * Return all subpages as object from given id
     *
     * @param int $id
     *
     * @return \Contao\Database\Result
     */
    public function getSubpagesObject($id)
    {
        $objDb = $this->database
            ->prepare("SELECT * FROM `tl_page` WHERE pid = ? ORDER BY sorting")
            ->execute($id);

        return $objDb;
    }

    /**
     * Return article object from given id
     *
     * @param mixed $id
     *
     * @return \Contao\Database\Result
     */
    public function getArticleObject($id)
    {
        if (is_array($id)) {
            $strQuery = sprintf(
                "SELECT * FROM `tl_article` WHERE id IN (%s) ORDER BY sorting",
                implode(', ', $id)
            );
            $objDb    = $this->database
                ->prepare($strQuery)
                ->execute();
        } else {
            $strQuery = "SELECT * FROM `tl_article` WHERE id = ?";
            $objDb    = $this->database
                ->prepare($strQuery)
                ->limit(1)
                ->execute($id);
        }

        return $objDb;
    }

    /**
     * Return article object from given pid
     *
     * @param int $id
     *
     * @return \Contao\Database\Result
     */
    public function getArticleObjectFromPid($id)
    {
        $objDb = $this->database
            ->prepare("SELECT * FROM `tl_article` WHERE pid = ? ORDER BY sorting")
            ->execute($id);

        return $objDb;
    }

    /**
     * Return article object from given child content id
     *
     * @param int $id
     *
     * @return \Contao\Database\Result
     */
    public function getArticleObjectFromContentId($id)
    {
        $objDb = $this->database
            ->prepare("SELECT a.* 
                    FROM `tl_article` AS a
                    LEFT JOIN `tl_content` AS c
                    ON c.pid = a.id
                    WHERE c.id = ?")
            ->limit(1)
            ->execute($id);

        return $objDb;
    }

    /**
     * Return content object from given pid
     *
     * @param int $id
     *
     * @return \Contao\Database\Result
     */
    public function getContentObjectFromPid($id)
    {
        $objDb = $this->database
            ->prepare("SELECT * FROM `tl_content` WHERE pid = ? ORDER BY sorting")
            ->execute($id);

        return $objDb;
    }

    /**
     * Return content object from given id
     *
     * @param int $id
     *
     * @return \Contao\Database\Result
     */
    public function getContentObject($id)
    {
        if (is_array($id)) {
            $strQuery = sprintf(
                "SELECT * FROM `tl_content` WHERE id IN (%s) ORDER BY sorting",
                implode(', ', $id)
            );
            $objDb    = $this->database
                ->prepare($strQuery)
                ->execute();
        } else {
            $strQuery = "SELECT * FROM `tl_content` WHERE id = ?";
            $objDb    = $this->database
                ->prepare($strQuery)
                ->limit(1)
                ->execute($id);
        }

        return $objDb;
    }

    /**
     * Return theme object from given child module id
     *
     * @param int $id
     *
     * @return \Contao\Database\Result
     */
    public function getThemeObjectFromModuleId($id)
    {
        $objDb = $this->database
            ->prepare("SELECT t.* 
                    FROM `tl_theme` AS t
                    LEFT JOIN `tl_module` AS m
                    ON m.pid = t.id
                    WHERE m.id = ?")
            ->limit(1)
            ->execute($id);

        return $objDb;
    }

    /**
     * Return module object from given id
     *
     * @param int $id
     *
     * @return \Contao\Database\Result
     */
    public function getModuleObject($id)
    {
        if (is_array($id)) {
            $strQuery = sprintf(
                "SELECT * FROM `tl_module` WHERE id IN (%s)",
                implode(', ', $id)
            );
            $objDb    = $this->database
                ->prepare($strQuery)
                ->execute();
        } else {
            $strQuery = "SELECT * FROM `tl_module` WHERE id = ?";
            $objDb    = $this->database
                ->prepare($strQuery)
                ->limit(1)
                ->execute($id);
        }

        return $objDb;
    }

    /**
     * Return object from given table and his id
     *
     * @param string $tableName
     *
     * @param int    $id
     *
     * @return \Contao\Database\Result
     */
    public function getDynamicObject($tableName, $id)
    {
        $objDb = $this->database
            ->prepare("SELECT * FROM `$tableName` WHERE id = ?")
            ->limit(1)
            ->execute($id);

        return $objDb;
    }

    /**
     * Insert array set to given table
     *
     * @param string $tableName
     *
     * @param array  $set
     *
     * @return \Contao\Database\Result|object
     */
    public function insertInto($tableName, $set)
    {
        $query = sprintf(
            "INSERT IGNORE INTO `%s` (`%s`) VALUES (%s)",
            $tableName,
            implode('`, `', array_keys($set)),
            implode(',', $set)
        );
        $objDb = $this->database->query($query);

        return $objDb;
    }

    /**
     * Get the min sorting from given pid
     *
     * @param string $tableName
     *
     * @param int    $id
     *
     * @return \Contao\Database\Result
     */
    public function getSorting($tableName, $id)
    {
        $objDb = $this->database
            ->prepare("SELECT MIN(sorting) AS sorting FROM " . $tableName . " WHERE pid=?")
            ->execute($id);

        return $objDb;
    }

    /**
     * Get the next sorting from given id
     *
     * @param string $tableName
     *
     * @param int    $id
     *
     * @param int    $sorting
     *
     * @return \Contao\Database\Result
     */
    public function getNextSorting($tableName, $id, $sorting)
    {
        $sql   = sprintf(
            "SELECT MIN(sorting) AS sorting FROM %s WHERE pid = ? AND sorting > ?",
            $tableName
        );
        $objDb = $this->database
            ->prepare($sql)
            ->execute($id, $sorting);

        return $objDb;
    }

    /**
     * Get elements sorting from given pid ordered by sorting
     *
     * @param string $tableName
     *
     * @param int    $id
     *
     * @return \Contao\Database\Result
     */
    public function getSortingElem($tableName, $id)
    {
        $sql   = sprintf(
            "SELECT id, sorting FROM %s WHERE pid = ? ORDER BY sorting",
            $tableName
        );
        $objDb = $this->database
            ->prepare($sql)
            ->execute($id);

        return $objDb;
    }

    /**
     * Update sorting
     *
     * @param string $tableName
     *
     * @param int    $sorting
     *
     * @param int    $id
     *
     * @return void
     */
    public function updateSorting($tableName, $sorting, $id)
    {
        $sql = sprintf(
            "UPDATE %s SET sorting = ? WHERE id = ?",
            $tableName
        );

        $this->database
            ->prepare($sql)
            ->execute($sorting, $id);
    }

    /**
     * Update alias
     *
     * @param string $tableName
     *
     * @param string $alias
     *
     * @param int    $id
     *
     * @return void
     */
    public function updateAlias($tableName, $alias, $id)
    {
        $sql = sprintf(
            "UPDATE %s SET alias = ? WHERE id = ?",
            $tableName
        );
        $this->database
            ->prepare($sql)
            ->execute($alias, $id);
    }
}