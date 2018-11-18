<?php
/**
 * Created by PhpStorm.
 * User: Stefan Heimes
 * Date: 18.11.2018
 * Time: 14:33
 */

namespace MenAtWork\ClipboardBundle\Helper;

/**
 * Class ContaoBridge
 *
 * @package MenAtWork\ClipboardBundle\Helper
 */
class ContaoBridge
{
    /**
     * @return \Contao\Database
     */
    public function getDatabase()
    {
        return \Contao\Database::getInstance();
    }

    /**
     * @return \Contao\Files
     */
    public function getFiles()
    {
        return \Contao\Files::getInstance();
    }

    /**
     * @return \Contao\BackendUser|\Contao\User
     */
    public function getBackendUser()
    {
        return \Contao\BackendUser::getInstance();
    }

    /**
     *
     * @param string $path The path of the wile. Without tl_root
     *
     * @return \Contao\File
     *
     * @throws \Exception
     */
    public function getNewFile($path)
    {
        return new \Contao\File($path);
    }
}