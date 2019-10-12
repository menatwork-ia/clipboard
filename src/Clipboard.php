<?php

/**
 * Contao Open Source CMS
 *
 * @copyright  MEN AT WORK 2013
 * @package    clipboard
 * @license    GNU/LGPL
 * @filesource
 */

namespace MenAtWork\ClipboardBundle;

use Contao\BackendTemplate;
use MenAtWork\ClipboardBundle\Helper\Base as HelperBase;
use MenAtWork\ClipboardBundle\Helper\Database as HelperDatabase;
use MenAtWork\ClipboardBundle\Xml\Base as XmlBase;

/**
 * Class Clipboard
 */
class Clipboard
{
    /**
     * Contains some helper functions
     *
     * @var HelperBase
     */
    private $helper;

    /**
     * Contains all xml specific functions and all information to the
     * clipboard elements
     *
     * @var XmlBase
     */
    private $xml;

    /**
     * Contains specific database request
     *
     * @var HelperDatabase
     */
    private $database;

    /**
     * Session Container
     *
     * @var \Contao\Session
     */
    private $session;

    /**
     * Current backend user.
     *
     * @var \Contao\BackendUser
     */
    private $user;

    /**
     * Current backend user.
     *
     * @var \Contao\Environment
     */
    private $environment;

    /**
     * Prevent constructing the object (Singleton)
     *
     * @param HelperBase          $clipboardHelper
     *
     * @param XmlBase             $clipboardXml
     *
     * @param HelperDatabase      $clipboardDatabase
     *
     * @param \Contao\Session     $session
     *
     * @param \Contao\BackendUser $user
     *
     * @param \Contao\Environment $environment
     */
    protected function __construct(
        $clipboardHelper,
        $clipboardXml,
        $clipboardDatabase,
        $session,
        $user,
        $environment
    ) {
        $this->helper      = $clipboardHelper;
        $this->xml         = $clipboardXml;
        $this->database    = $clipboardDatabase;
        $this->session     = $session;
        $this->user        = $user;
        $this->environment = $environment;
    }

    /**
     * Get clipboard container object
     *
     * @return \MenAtWork\ClipboardBundle\Xml\Base
     *
     * @deprecated Use getXml instead.
     */
    public function cb()
    {
        return $this->getXml();
    }

    /**
     * Get clipboard container object
     *
     * @return \MenAtWork\ClipboardBundle\Xml\Base
     */
    public function getXml()
    {
        return $this->xml;
    }

    /**
     * Return boolean if the clipboard is for given dca and user allowed
     *
     * @param string $dca
     *
     * @return boolean
     */
    public function isClipboard($dca = null)
    {
        $arrAllowedLocations = $GLOBALS['CLIPBOARD']['locations'];

        if ($dca == null || !isset($GLOBALS['CLIPBOARD']['locations']) || !$this->user->clipboard) {
            return false;
        }

        if (in_array($dca, $arrAllowedLocations)) {
            if (TL_MODE == 'BE' && in_array($this->helper->getPageType(), $arrAllowedLocations)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Handle all main operations, clean up the url and redirect to itself
     */
    public function init()
    {
        $arrSession = $this->session->get('clipboardExt');

        if ($arrSession['readXML']) {
            return;
        }

        if (stristr(\Contao\Input::get('key'),
                'cl_') || \Contao\Input::post('FORM_SUBMIT') == 'tl_select' && isset($_POST['cl_group'])) {
            $arrUnsetParams = array();
            foreach (array_keys($_GET) AS $strGetParam) {
                switch ($strGetParam) {
                    case 'key':
                        switch (\Contao\Input::get($strGetParam)) {
                            // Set new favorite
                            case 'cl_favor':
                                if (strlen(\Contao\Input::get('cl_id'))) {
                                    $this->favor(\Contao\Input::get('cl_id'));
                                }
                                break;

                            // Delete an element
                            case 'cl_delete':
                                if (strlen(\Contao\Input::get('cl_id'))) {
                                    $this->delete(\Contao\Input::get('cl_id'));
                                }
                                break;

                            // Edit Element
                            case 'cl_edit':
                                $arrTitles = \Contao\Input::post('title');
                                if (is_array($arrTitles)) {
                                    $this->edit($arrTitles);
                                }
                                break;

                            // Create new entry
                            case 'cl_copy':
                                $this->copy();
                                break;

                            case 'cl_header_pastenew':
                            case 'cl_paste_into':
                                $this->pasteInto();
                                break;

                            case 'cl_paste_after':
                                $this->pasteAfter();
                                break;
                        }
                        $arrUnsetParams[$strGetParam] = \Contao\Input::get($strGetParam);
                        break;
                    case 'act':
                        if (\Contao\Input::get('key') != 'cl_delete') {
                            // Copy multi edit elements to clipboard
                            $ids = deserialize(\Contao\Input::post('IDS'));

                            if (!is_array($ids) || empty($ids)) {
                                $this->reload();
                            }

                            $this->copy(true, $ids);
                            $arrUnsetParams[$strGetParam] = \Contao\Input::get($strGetParam);
                        }
                        break;
                    case 'childs':
                    case 'mode':
                    case 'cl_id':
                        $arrUnsetParams[$strGetParam] = \Contao\Input::get($strGetParam);
                        break;
                }
            }

            foreach ($arrUnsetParams AS $k => $v) {
                \Contao\Input::setGet($k, null);
                $this->environment->request     = str_replace("&$k=$v", '', $this->environment->request);
                $this->environment->queryString = str_replace("&$k=$v", '', $this->environment->queryString);
                $this->environment->requestUri  = str_replace("&$k=$v", '', $this->environment->requestUri);
            }

            $arrUnsetKeyParams = array(
                'cl_copy',
                'cl_paste_into',
                'cl_paste_after'
            );

            if (in_array($arrUnsetParams['key'], $arrUnsetKeyParams) && $this->helper->getPageType() == 'content') {
                $objArticle = $this->database->getArticleObjectFromContentId(\Contao\Input::get('id'));

                $strRequestWithoutId = str_replace(
                    substr($this->environment->request, strpos($this->environment->request, '&id')), '',
                    $this->environment->request
                );
                \Contao\Backend::redirect($strRequestWithoutId . '&id=' . $objArticle->id);
            } elseif (in_array($arrUnsetParams['key'],
                    $arrUnsetKeyParams) && $this->helper->getPageType() == 'module') {
                $objTheme = $this->database->getThemeObjectFromModuleId(\Contao\Input::get('id'));

                $strRequestWithoutId = str_replace(
                    substr($this->environment->request, strpos($this->environment->request, '&id')), '',
                    $this->environment->request
                );

                \Contao\Backend::redirect($strRequestWithoutId . '&id=' . $objTheme->id);
            }

            \Contao\Backend::redirect($this->environment->request);
        }
    }

    /**
     * Add the Clipboard to the backend template
     *
     * HOOK: $GLOBALS['TL_HOOKS']['outputBackendTemplate']
     *
     * @param string $strContent
     * @param string $strTemplate
     *
     * @return string
     */
    public function outputBackendTemplate($strContent, $strTemplate)
    {
        $this->session->set('clipboardExt', array('readXML' => false));

        if ($strTemplate == 'be_main' && $this->user->clipboard && $this->getXml()->hasElements()) {
            $objTemplate = new BackendTemplate('be_clipboard');

            $arrClipboard = $this->getXml()->getElements();

            $objTemplate->clipboard = $arrClipboard;
            $objTemplate->isContext = $this->helper->isContext();
            $objTemplate->action    = $this->environment->request . '&key=cl_edit';

            if (!$this->helper->isContext()) {
                $strContent = preg_replace('/<body.*class="/', "$0clipboard ", $strContent, 1);
            }

            return preg_replace('/<div.*id="container".*>/', $objTemplate->parse() . "\n$0", $strContent, 1);
        }

        return $strContent;
    }


    /**
     * Prepare context if is set or disable context
     */
    public function prepareContext()
    {
        if (!$this->helper->isContext()) {
            foreach ($GLOBALS['CLIPBOARD'] AS $key => $value) {
                if (array_key_exists('attributes', $GLOBALS['CLIPBOARD'][$key])) {
                    $GLOBALS['CLIPBOARD'][$key]['attributes'] = 'onclick="Backend.getScrollOffset();"';
                }
            }
        }
    }

    /**
     * Paste favorite into
     */
    public function pasteInto()
    {
        $this->getXml()->read('pasteInto', \Contao\Input::get('id'));
    }

    /**
     * Paste favorite after
     */
    public function pasteAfter()
    {
        $this->getXml()->read('pasteAfter', \Contao\Input::get('id'));
    }

    /**
     * Delete the given element
     *
     * @param string $hash
     */
    public function delete($hash)
    {
        $this->getXml()->deleteFile($hash);
    }

    /**
     * Make the given element favorit
     *
     * @param string $hash
     */
    public function favor($hash)
    {
        $this->getXml()->setFavor($hash);
    }

    /**
     * Rename all given clipboard titles
     *
     * @param array $arrTitles
     */
    public function edit($arrTitles)
    {
        $this->getXml()->editTitle($arrTitles);
    }


    /**
     * Return the title for the given id
     *
     * @param mixed $mixedId
     *
     * @return string
     */
    public function getTitle($mixedId)
    {
        $arrTitle = array();

        $booClGroup = false;

        if (is_array($mixedId)) {
            $booClGroup = true;
        }

        switch ($this->helper->getPageType()) {
            case 'page':
                if ($booClGroup) {
                    $mixedId = $mixedId[0];
                }
                $objElem  = $this->database->getPageObject($mixedId);
                $arrTitle = array('title' => $objElem->title);
                break;

            case 'article':
                $arrTitle = array(
                    'title' => call_user_func_array(array(
                        $this->database,
                        'get' . $this->helper->getPageType() . 'Object'
                    ), array($mixedId))->title
                );
                break;

            case 'content':
                if (!$booClGroup) {
                    $mixedTitle = $this->helper->createContentTitle($mixedId, $booClGroup);
                    if (!is_object($mixedTitle) && is_array($mixedTitle)) {
                        $arrTitle = $mixedTitle;
                    } else {
                        $arrTitle = array(
                            'title'     => $GLOBALS['TL_LANG']['MSC']['noClipboardTitle'],
                            'attribute' => $GLOBALS['TL_LANG']['CTE'][$mixedTitle->type][0]
                        );
                    }
                } else {
                    $strTitle = '';
                    foreach ($mixedId AS $intId) {
                        $mixedTitle = $this->helper->createContentTitle($intId, $booClGroup);
                        if (!is_object($mixedTitle) && is_array($mixedTitle)) {
                            $strTitle = $mixedTitle['title'];
                            break;
                        }
                    }

                    if (strlen($strTitle) > 0) {
                        $arrTitle = array('title' => $strTitle);
                    } else {
                        $arrTitle = array('title' => $GLOBALS['TL_LANG']['MSC']['noClipboardTitle']);
                    }
                }
                break;
            case 'module':
                $objElem  = $this->database->getModuleObject($mixedId);
                $arrTitle = array('title' => $objElem->name);
                break;

            default:
                $arrTitle = array('title' => $GLOBALS['TL_LANG']['MSC']['noClipboardTitle']);
        }

        $arrTitle['title'] = \StringUtil::substr($arrTitle['title'], '24');

        return $arrTitle;
    }

    /**
     * Copy element to clipboard and write xml
     *
     * @param bool  $booClGroup
     * @param array $ids
     */
    public function copy($booClGroup = false, $ids = array())
    {
        $arrSet = array(
            'user_id' => $this->user->id,
            'table'   => $this->helper->getDatabasePageType()
        );

        if ($booClGroup == true && count($ids) > 1) {
            $arrSet['childs']     = 0;
            $arrSet['elem_id']    = $ids;
            $arrSet['grouped']    = true;
            $arrSet['groupCount'] = count($ids);
            $arrSet               = array_merge($arrSet, $this->getTitle($ids));
        } else {
            if (count($ids) == 1) {
                $intId = $ids[0];
            } else {
                $intId = \Contao\Input::get('id');
            }

            $arrSet['childs']     = ((\Contao\Input::get('childs') == 1) ? 1 : 0);
            $arrSet['elem_id']    = $intId;
            $arrSet['grouped']    = false;
            $arrSet['groupCount'] = 0;
            $arrSet               = array_merge($arrSet, $this->getTitle($intId));
        }

        if (!$arrSet['attribute']) {
            $arrSet['attribute'] = '';
        }

        $this->getXml()->write($arrSet);
    }
}
