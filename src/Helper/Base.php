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

use Contao\Input;
use Contao\System;

/**
 * Class ClipboardHelper
 */
class Base
{
    /**
     * The clipboard database helper.
     *
     * @var \Contao\Database
     */
    private $database;

    /**
     * The current user.
     *
     * @var \Contao\BackendUser
     */
    private $user;

    /**
     * Page type
     *
     * @var string
     */
    private $pageType;

    /**
     * Search for special chars
     *
     * @var array
     */
    public $searchFor = array(
        "\\",
        "'"
    );

    /**
     * Replace special chars with
     *
     * @var array
     */
    public $replaceWith = array(
        "\\\\",
        "\\'"
    );

    /**
     * Base constructor.
     *
     * @param \Database    $clipboardDatabase The helper class of the clipboard.
     *
     * @param ContaoBridge $contaoBridge      The contao bindings.
     */
    public function __construct($clipboardDatabase, $contaoBridge)
    {
        $this->database = $clipboardDatabase;
        $this->user     = $contaoBridge->getBackendUser();
        $this->setPageType();
    }

    /**
     * Get the current pagetype
     *
     * @return string
     */
    public function getPageType()
    {
        return $this->pageType;
    }

    /**
     * Get the page type as database name
     *
     * @return string
     */
    public function getDatabasePageType()
    {
        return 'tl_' . $this->pageType;
    }

    /**
     * Set the current page type
     */
    protected function setPageType()
    {
        if (Input::get('table') == 'tl_content') {
            $this->pageType = 'content';
        } elseif (Input::get('table') == 'tl_module') {
            $this->pageType = 'module';
        } else {
            $this->pageType = Input::get('do');
        }
    }

    /**
     * Return if context checkbox is true or false
     *
     * @return boolean
     */
    public function isContext()
    {
        return !$this->user->clipboard_context;
    }

    /**
     * Return the paste button
     *
     * @param array  $row
     * @param string $href
     * @param string $label
     * @param string $title
     * @param string $icon
     * @param string $attributes
     * @param string $table
     *
     * @return string
     */
    public function getPasteButton($row, $href, $label, $title, $icon, $attributes, $table)
    {
        $boolFavorit = Clipboard::getInstance()->cb()->hasFavorite();

        if ($boolFavorit) {
            $return = '';
            if ($this->user->isAdmin || ($this->user->hasAccess($row['type'], 'alpty') && $this->user->isAllowed(2,
                        $row))) {
                // Create link
                $return .= vsprintf('<a href="%s" title="%s" %s>%s</a>', array(
                        // Create URL
                        $this->addToUrl(
                            vsprintf('%s&amp;id=%s', array(
                                    $href,
                                    $row['id']
                                )
                            )
                        ),
                        specialchars($title),
                        $attributes,
                        // Create linkimage
                        \Image::getHtml($icon, $label)
                    )
                );
            } else {
                // Create image
                $return .= \Image::getHtml(preg_replace('/\.gif$/i', '_.gif', $icon)) . ' ';
            }

            return $return;
        } else {
            return '';
        }
    }

    /**
     * Return clipboard button
     *
     * HOOK: $GLOBALS['TL_HOOKS']['clipboardButtons']
     *
     * @param object  $dc
     * @param array   $row
     * @param string  $table
     * @param boolean $cr
     * @param array   $arrClipboard
     * @param childs  $childs
     *
     * @return string
     */
    public function clipboardButtons(DataContainer $dc, $row, $table, $cr, $arrClipboard = false, $childs)
    {
        if (!Input::get('act')) {
            $objFavorit = Clipboard::getInstance()->cb()->getFavorite();

            if ($dc->table == 'tl_article' && $table == 'tl_page') {
                // Create button title and lable
                $label = $title = vsprintf($GLOBALS['TL_LANG'][$dc->table]['pasteinto'][1], array(
                        $row['id']
                    )
                );

                // Create Paste Button
                $return = $this->getPasteButton(
                    $row, $GLOBALS['CLIPBOARD']['pasteinto']['href'], $label, $title,
                    $GLOBALS['CLIPBOARD']['pasteinto']['icon'], $GLOBALS['CLIPBOARD']['pasteinto']['attributes'],
                    $dc->table
                );

                return $return;
            }
        }
    }

    public function clipboardActSelectButtons(DataContainer $dc)
    {
        return '<input id="cl_multiCopy" class="tl_submit" type="submit" value="' . $GLOBALS['TL_LANG']['MSC']['groupSelected'] . '" name="cl_group">';
    }

    /**
     * Write a message to the customer in TL_INFO and the log
     *
     * @param string $strMessage
     */
    public function writeCustomerInfoMessage($strMessage)
    {
        $this->log($strMessage, __FUNCTION__, TL_GENERAL);
        if (version_compare(VERSION, '2.11', '>=')) {
            $this->addInfoMessage($strMessage);
        } else {
            $strType = 'TL_INFO';
            if (!is_array($_SESSION[$strType])) {
                $_SESSION[$strType] = array();
            }

            $_SESSION[$strType][] = $strMessage;
        }
    }

    /**
     * Create title for content element and return it as string. If no title
     * exists return content element as object.
     *
     * @param integer $intId
     * @param boolean $booClGroup
     *
     * @return DB_Mysql_Result|string
     */
    public function createContentTitle($intId, $booClGroup)
    {
        $objContent  = $this->database->getContentObject($intId);
        $strHeadline = $this->_getHeadlineValue($objContent);

        $arrTitle = array();

        switch ($objContent->type) {
            case 'headline':
            case 'gallery':
            case 'downloads':
                if ($strHeadline) {
                    $arrTitle['title'] = $strHeadline;
                }
                break;

            case 'text':
                if ($strHeadline) {
                    $arrTitle['title'] = $strHeadline;
                } elseif ($objContent->text) {
                    $arrTitle['title'] = $objContent->text;
                }
                break;

            case 'html':
                if ($objContent->html) {
                    $arrTitle['title'] = $objContent->html;
                }
                break;

            case 'list':
                $arrList = deserialize($objContent->listitems);
                if ($strHeadline) {
                    $arrTitle['title'] = $strHeadline;
                } elseif ($arrList[0]) {
                    $arrTitle['title'] = $arrList[0];
                }
                break;

            case 'table':
                $arrTable = deserialize($objContent->tableitems);
                if ($strHeadline) {
                    $arrTitle['title'] = $strHeadline;
                } elseif ($arrTable[0][0]) {
                    $arrTitle['title'] = $arrTable[0][0];
                }
                break;

            case 'accordion':
                if ($objContent->mooHeadline) {
                    $arrTitle['title'] = $objContent->mooHeadline;
                }
                break;

            case 'code':
                if ($strHeadline) {
                    $arrTitle['title'] = $strHeadline;
                } elseif ($objContent->code) {
                    $arrTitle['title'] = $objContent->code;
                }
                break;

            case 'hyperlink':
            case 'download':
                if ($strHeadline) {
                    $arrTitle['title'] = $strHeadline;
                } elseif ($objContent->linkTitle) {
                    $arrTitle['title'] = $objContent->linkTitle;
                }
                break;

            case 'toplink':
                if ($objContent->linkTitle) {
                    $arrTitle['title'] = $objContent->linkTitle;
                }
                break;

            case 'image':
                if ($strHeadline) {
                    $arrTitle['title'] = $strHeadline;
                } elseif ($objContent->caption) {
                    $arrTitle['title'] = $objContent->caption;
                } elseif ($objContent->alt) {
                    $arrTitle['title'] = $objContent->alt;
                }
                break;

            default:

                // HOOK: call the hooks for clipboardContentTitle
                if (isset($GLOBALS['TL_HOOKS']['clipboardContentTitle']) && is_array($GLOBALS['TL_HOOKS']['clipboardContentTitle'])) {
                    foreach ($GLOBALS['TL_HOOKS']['clipboardContentTitle'] as $arrCallback) {
                        $this->import($arrCallback[0]);
                        $strTmpTitle = $this->{$arrCallback[0]}->{$arrCallback[1]}($this, $strHeadline, $objContent,
                            $booClGroup);
                        if ($strTmpTitle !== false && !is_null($strTmpTitle)) {
                            $arrTitle['title'] = $strTmpTitle;
                            break;
                        }
                    }
                }

                break;
        }

        if (!$arrTitle['title']) {
            return $objContent;
        }

        $arrTitle['attribute'] = $GLOBALS['TL_LANG']['CTE'][$objContent->type][0];

        return $arrTitle;
    }

    protected function _getHeadlineValue($objElem)
    {
        $arrHeadline = deserialize($objElem->headline, true);
        if ($arrHeadline['value']) {
            return $arrHeadline['value'];
        }

        return false;
    }

    /**
     * Get field array with all fields from given array
     *
     * @param string $strTable
     *
     * @return array
     */
    public function getFields($strTable)
    {
        $arrTableMetaFields = $this->getTableMetaFields($strTable);
        $arrFields          = array();
        foreach ($arrTableMetaFields AS $key => $value) {
            $arrFields[] = $key;
        }

        return $arrFields;
    }

    /**
     * Write the field information from the given table string to an array and return it
     *
     * @param string $strTable
     *
     * @return array
     */
    public function getTableMetaFields($strTable)
    {
        $fields = $this->database->getFields($strTable);

        $arrFieldMeta = array();

        foreach ($fields as $value) {
            if ($value["type"] == "index") {
                continue;
            }

            $arrFieldMeta[$value["name"]] = $value;
        }

        return $arrFieldMeta;
    }

    /**
     * Get pid and new sorting for new element
     *
     * @param string $strTable
     * @param int    $intPid
     * @param string $strPastePos
     *
     * @return array
     */
    public function getNewPosition($strTable, $intPid, $strPastePos)
    {
        // Insert the current record at the beginning when inserting into the parent record
        if ($strPastePos == 'pasteInto') {
            $newPid     = $intPid;
            $objSorting = $this->database->getSorting($strTable, $intPid);

            // Select sorting value of the first record
            if ($objSorting->numRows) {
                $intCurSorting = $objSorting->sorting;

                // Resort if the new sorting value is not an integer or smaller than 1
                if (($intCurSorting % 2) != 0 || $intCurSorting < 1) {
                    $objNewSorting = $this->database->getSortingElem($strTable, $intPid);

                    $count      = 2;
                    $newSorting = 128;

                    while ($objNewSorting->next()) {
                        $this->database->updateSorting($strTable, ($count++ * 128), $objNewSorting->id);
                    }
                } // Else new sorting = (current sorting / 2)
                else {
                    $newSorting = ($intCurSorting / 2);
                }
            } // Else new sorting = 128
            else {
                $newSorting = 128;
            }
        } // Else insert the current record after the parent record
        elseif ($strPastePos == 'pasteAfter' && $intPid > 0) {
            $objSorting = $this->database->getDynamicObject($strTable, $intPid);

            // Set parent ID of the current record as new parent ID
            if ($objSorting->numRows) {
                $newPid        = $objSorting->pid;
                $intCurSorting = $objSorting->sorting;

                // Do not proceed without a parent ID
                if (is_numeric($newPid)) {
                    $objNextSorting = $this->database->getNextSorting($strTable, $newPid, $intCurSorting);

                    // Select sorting value of the next record
                    if ($objNextSorting->sorting !== null) {
                        $intNextSorting = $objNextSorting->sorting;

                        // Resort if the new sorting value is no integer or bigger than a MySQL integer
                        if ((($intCurSorting + $intNextSorting) % 2) != 0 || $intNextSorting >= 4294967295) {
                            $count = 1;

                            $objNewSorting = $this->database->getSortingElem($strTable, $newPid);

                            while ($objNewSorting->next()) {
                                $this->database->updateSorting($strTable, ($count++ * 128), $objNewSorting->id);

                                if ($objNewSorting->sorting == $intCurSorting) {
                                    $newSorting = ($count++ * 128);
                                }
                            }
                        } // Else new sorting = (current sorting + next sorting) / 2
                        else {
                            $newSorting = (($intCurSorting + $intNextSorting) / 2);
                        }
                    } // Else new sorting = (current sorting + 128)
                    else {
                        $newSorting = ($intCurSorting + 128);
                    }
                }
            } // Use the given parent ID as parent ID
            else {
                $newPid     = $intPid;
                $newSorting = 128;
            }
        }

        return array('pid' => intval($newPid), 'sorting' => intval($newSorting));
    }

    /**
     * Check if the content type exists in this system and return true or false
     *
     * @param array $arrSet
     *
     * @return boolean
     */
    public function existsContentType($arrSet)
    {
        foreach ($GLOBALS['TL_CTE'] AS $group => $arrCElems) {
            foreach ($arrCElems AS $strCType => $strCDesc) {
                if (substr($arrSet['type'], 1, -1) == $strCType) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if the module type exists in this system and return true or false
     *
     * @param array $arrSet
     *
     * @return boolean
     */
    public function existsModuleType($arrSet)
    {
        foreach ($GLOBALS['FE_MOD'] AS $group => $arrMElems) {
            foreach ($arrMElems AS $strMType => $strMDesc) {
                if (substr($arrSet['type'], 1, -1) == $strMType) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Return given file from path without file extension as array
     *
     * @param string $strFilePath
     *
     * @return array
     */
    public function getArrFromFileName($strFilePath)
    {
        System::loadLanguageFile('default');
        $arrFileInfo = pathinfo($strFilePath);

        $arrFileName = explode(
            ',',
            $arrFileInfo['filename']
        );

        // Add groupflag for older xml filenames
        if (!in_array($arrFileName[4], array('G', 'NG'))) {
            $arrFileName[5] = $arrFileName[4];
            $arrFileName[4] = ((stristr($arrFileName[5],
                    standardize($GLOBALS['TL_LANG']['MSC']['clipboardGroup'])) === false) ? 'NG' : 'G');
        }

        return $arrFileName;
    }

}