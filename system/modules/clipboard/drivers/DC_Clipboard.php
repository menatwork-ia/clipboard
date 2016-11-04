<?php

/**
 * Contao Open Source CMS
 *
 * @copyright  MEN AT WORK 2013 
 * @package    clipboard
 * @license    GNU/LGPL 
 * @filesource
 */

class DC_Clipboard extends DC_Table
{
    
	/**
	 * Recursively generate the tree and return it as HTML string
	 * @param string
	 * @param integer
	 * @param array
	 * @param boolean
	 * @param integer
	 * @param array
	 * @param boolean
	 * @param boolean
	 * @param boolean
	 * @return string
	 */
	protected function generateTree($table, $id, $arrPrevNext, $blnHasSorting, $intMargin=0, $arrClipboard=null, $blnCircularReference=false, $protectedPage=false, $blnNoRecursion=false)
	{
		static $session;

		$session = $this->Session->getData();
		$node = ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] == 6) ? $this->strTable.'_'.$table.'_tree' : $this->strTable.'_tree';

		// Toggle nodes
		if (\Input::get('ptg'))
		{
			$session[$node][\Input::get('ptg')] = (isset($session[$node][\Input::get('ptg')]) && $session[$node][\Input::get('ptg')] == 1) ? 0 : 1;
			$this->Session->setData($session);

			$this->redirect(preg_replace('/(&(amp;)?|\?)ptg=[^& ]*/i', '', \Environment::get('request')));
		}

		$objRow = $this->Database->prepare("SELECT * FROM " . $table . " WHERE id=?")
								 ->limit(1)
								 ->execute($id);

		// Return if there is no result
		if ($objRow->numRows < 1)
		{
			$this->Session->setData($session);
			return '';
		}

		$return = '';
		$intSpacing = 20;
		$childs = array();

		// Add the ID to the list of current IDs
		if ($this->strTable == $table)
		{
			$this->current[] = $objRow->id;
		}

		// Check whether there are child records
		if (!$blnNoRecursion)
		{
			if ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] == 5 || $this->strTable != $table)
			{
				$objChilds = $this->Database->prepare("SELECT id FROM " . $table . " WHERE pid=?" . ($blnHasSorting ? " ORDER BY sorting" : ''))
											->execute($id);

				if ($objChilds->numRows)
				{
					$childs = $objChilds->fetchEach('id');
				}
			}
		}

		$blnProtected = false;

		// Check whether the page is protected
		if ($table == 'tl_page')
		{
			$blnProtected = ($objRow->protected || $protectedPage) ? true : false;
		}

		$session[$node][$id] = (is_int($session[$node][$id])) ? $session[$node][$id] : 0;
		$mouseover = ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] == 5 || $table == $this->strTable) ? ' onmouseover="Theme.hoverDiv(this,1)" onmouseout="Theme.hoverDiv(this,0)"' : '';

		$return .= "\n  " . '<li class="'.((($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] == 5 && $objRow->type == 'root') || $table != $this->strTable) ? 'tl_folder' : 'tl_file').' click2edit"'.$mouseover.'><div class="tl_left" style="padding-left:'.($intMargin + $intSpacing + (empty($childs) ? 20 : 0)).'px">';

		// Calculate label and add a toggle button
		$args = array();
		$showFields = $GLOBALS['TL_DCA'][$table]['list']['label']['fields'];
		$level = ($intMargin / $intSpacing + 1);

		if (!empty($childs))
		{
			$img = ($session[$node][$id] == 1) ? 'folMinus.gif' : 'folPlus.gif';
			$alt = ($session[$node][$id] == 1) ? $GLOBALS['TL_LANG']['MSC']['collapseNode'] : $GLOBALS['TL_LANG']['MSC']['expandNode'];
			$return .= '<a href="'.$this->addToUrl('ptg='.$id).'" title="'.specialchars($alt).'" onclick="Backend.getScrollOffset();return AjaxRequest.toggleStructure(this,\''.$node.'_'.$id.'\','.$level.','.$GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'].')">'.$this->generateImage($img, '', 'style="margin-right:2px"').'</a>';
		}

		foreach ($showFields as $k=>$v)
		{
			// Decrypt the value
			if ($GLOBALS['TL_DCA'][$table]['fields'][$v]['eval']['encrypt'])
			{
				$objRow->$v = \Encryption::decrypt(deserialize($objRow->$v));
			}

			if (strpos($v, ':') !== false)
			{
				list($strKey, $strTable) = explode(':', $v);
				list($strTable, $strField) = explode('.', $strTable);

				$objRef = $this->Database->prepare("SELECT " . $strField . " FROM " . $strTable . " WHERE id=?")
										 ->limit(1)
										 ->execute($objRow->$strKey);

				$args[$k] = $objRef->numRows ? $objRef->$strField : '';
			}
			elseif (in_array($GLOBALS['TL_DCA'][$table]['fields'][$v]['flag'], array(5, 6, 7, 8, 9, 10)))
			{
				$args[$k] = $this->parseDate($GLOBALS['TL_CONFIG']['datimFormat'], $objRow->$v);
			}
			elseif ($GLOBALS['TL_DCA'][$table]['fields'][$v]['inputType'] == 'checkbox' && !$GLOBALS['TL_DCA'][$table]['fields'][$v]['eval']['multiple'])
			{
				$args[$k] = ($objRow->$v != '') ? (isset($GLOBALS['TL_DCA'][$table]['fields'][$v]['label'][0]) ? $GLOBALS['TL_DCA'][$table]['fields'][$v]['label'][0] : $v) : '';
			}
			else
			{
				$args[$k] = $GLOBALS['TL_DCA'][$table]['fields'][$v]['reference'][$objRow->$v] ?: $objRow->$v;
			}
		}

		$label = vsprintf(((strlen($GLOBALS['TL_DCA'][$table]['list']['label']['format'])) ? $GLOBALS['TL_DCA'][$table]['list']['label']['format'] : '%s'), $args);

		// Shorten the label it if it is too long
		if ($GLOBALS['TL_DCA'][$table]['list']['label']['maxCharacters'] > 0 && $GLOBALS['TL_DCA'][$table]['list']['label']['maxCharacters'] < utf8_strlen(strip_tags($label)))
		{
			$label = trim(\String::substrHtml($label, $GLOBALS['TL_DCA'][$table]['list']['label']['maxCharacters'])) . ' …';
		}

		$label = preg_replace('/\(\) ?|\[\] ?|\{\} ?|<> ?/', '', $label);

		// Call the label_callback ($row, $label, $this)
		if (is_array($GLOBALS['TL_DCA'][$table]['list']['label']['label_callback']))
		{
			$strClass = $GLOBALS['TL_DCA'][$table]['list']['label']['label_callback'][0];
			$strMethod = $GLOBALS['TL_DCA'][$table]['list']['label']['label_callback'][1];

			$this->import($strClass);
			$return .= $this->$strClass->$strMethod($objRow->row(), $label, $this, '', false, $blnProtected);
		}
		else
		{
			$return .= $this->generateImage('iconPLAIN.gif', '') . ' ' . $label;
		}

		$return .= '</div> <div class="tl_right">';
		$previous = ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] == 6) ? $arrPrevNext['pp'] : $arrPrevNext['p'];
		$next = ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] == 6) ? $arrPrevNext['nn'] : $arrPrevNext['n'];
		$_buttons = '';

		// Regular buttons ($row, $table, $root, $blnCircularReference, $childs, $previous, $next)
		if ($this->strTable == $table)
		{
			$_buttons .= (\Input::get('act') == 'select') ? '<input type="checkbox" name="IDS[]" id="ids_'.$id.'" class="tl_tree_checkbox" value="'.$id.'">' : $this->generateButtons($objRow->row(), $table, $this->root, $blnCircularReference, $childs, $previous, $next);
		}
    
    // Added by Patrick Kahl
    // HOOK: call the hooks for clipboardButtons
    if (isset($GLOBALS['TL_HOOKS']['clipboardButtons']) && is_array($GLOBALS['TL_HOOKS']['clipboardButtons']))
    {
        foreach ($GLOBALS['TL_HOOKS']['clipboardButtons'] as $callback)
        {
            $this->import($callback[0]);
            $_buttons .= $this->$callback[0]->$callback[1]($this, $objRow->row(), $table, $blnCircularReference, $arrClipboard, $childs, $previous, $next);
        }
    }    

		// Paste buttons
		if ($arrClipboard !== false && \Input::get('act') != 'select')
		{
			$_buttons .= ' ';

			// Call paste_button_callback(&$dc, $row, $table, $blnCircularReference, $arrClipboard, $childs, $previous, $next)
			if (is_array($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['paste_button_callback']))
			{
				$strClass = $GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['paste_button_callback'][0];
				$strMethod = $GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['paste_button_callback'][1];

				$this->import($strClass);
				$_buttons .= $this->$strClass->$strMethod($this, $objRow->row(), $table, $blnCircularReference, $arrClipboard, $childs, $previous, $next);
			}
			else
			{
				$imagePasteAfter = $this->generateImage('pasteafter.gif', sprintf($GLOBALS['TL_LANG'][$this->strTable]['pasteafter'][1], $id));
				$imagePasteInto = $this->generateImage('pasteinto.gif', sprintf($GLOBALS['TL_LANG'][$this->strTable]['pasteinto'][1], $id));

				// Regular tree (on cut: disable buttons of the page all its childs to avoid circular references)
				if ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] == 5)
				{
					$_buttons .= ($arrClipboard['mode'] == 'cut' && ($blnCircularReference || $arrClipboard['id'] == $id) || $arrClipboard['mode'] == 'cutAll' && ($blnCircularReference || in_array($id, $arrClipboard['id'])) || (!empty($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['root']) && in_array($id, $this->root))) ? $this->generateImage('pasteafter_.gif').' ' : '<a href="'.$this->addToUrl('act='.$arrClipboard['mode'].'&amp;mode=1&amp;pid='.$id.(!is_array($arrClipboard['id']) ? '&amp;id='.$arrClipboard['id'] : '')).'" title="'.specialchars(sprintf($GLOBALS['TL_LANG'][$this->strTable]['pasteafter'][1], $id)).'" onclick="Backend.getScrollOffset()">'.$imagePasteAfter.'</a> ';
					$_buttons .= ($arrClipboard['mode'] == 'paste' && ($blnCircularReference || $arrClipboard['id'] == $id) || $arrClipboard['mode'] == 'cutAll' && ($blnCircularReference || in_array($id, $arrClipboard['id']))) ? $this->generateImage('pasteinto_.gif').' ' : '<a href="'.$this->addToUrl('act='.$arrClipboard['mode'].'&amp;mode=2&amp;pid='.$id.(!is_array($arrClipboard['id']) ? '&amp;id='.$arrClipboard['id'] : '')).'" title="'.specialchars(sprintf($GLOBALS['TL_LANG'][$this->strTable]['pasteinto'][1], $id)).'" onclick="Backend.getScrollOffset()">'.$imagePasteInto.'</a> ';
				}

				// Extended tree
				else
				{
					$_buttons .= ($this->strTable == $table) ? (($arrClipboard['mode'] == 'cut' && ($blnCircularReference || $arrClipboard['id'] == $id) || $arrClipboard['mode'] == 'cutAll' && ($blnCircularReference || in_array($id, $arrClipboard['id']))) ? $this->generateImage('pasteafter_.gif') : '<a href="'.$this->addToUrl('act='.$arrClipboard['mode'].'&amp;mode=1&amp;pid='.$id.(!is_array($arrClipboard['id']) ? '&amp;id='.$arrClipboard['id'] : '')).'" title="'.specialchars(sprintf($GLOBALS['TL_LANG'][$this->strTable]['pasteafter'][1], $id)).'" onclick="Backend.getScrollOffset()">'.$imagePasteAfter.'</a> ') : '';
					$_buttons .= ($this->strTable != $table) ? '<a href="'.$this->addToUrl('act='.$arrClipboard['mode'].'&amp;mode=2&amp;pid='.$id.(!is_array($arrClipboard['id']) ? '&amp;id='.$arrClipboard['id'] : '')).'" title="'.specialchars(sprintf($GLOBALS['TL_LANG'][$this->strTable]['pasteinto'][1], $id)).'" onclick="Backend.getScrollOffset()">'.$imagePasteInto.'</a> ' : '';
				}
			}
		}

		$return .= ($_buttons ?: '&nbsp;') . '</div><div style="clear:both"></div></li>';

		// Add the records of the table itself
		if ($table != $this->strTable)
		{
			$objChilds = $this->Database->prepare("SELECT id FROM " . $this->strTable . " WHERE pid=?" . ($blnHasSorting ? " ORDER BY sorting" : ''))
							 			->execute($id);

			if ($objChilds->numRows)
			{
				$ids = $objChilds->fetchEach('id');

				for ($j=0; $j<count($ids); $j++)
				{
					$return .= $this->generateTree($this->strTable, $ids[$j], array('pp'=>$ids[($j-1)], 'nn'=>$ids[($j+1)]), $blnHasSorting, ($intMargin + $intSpacing), $arrClipboard, false, ($j<(count($ids)-1) || !empty($childs)));
				}
			}
		}

		// Begin a new submenu
		if (!$blnNoRecursion)
		{
			if (!empty($childs) && $session[$node][$id] == 1)
			{
				$return .= '<li class="parent" id="'.$node.'_'.$id.'"><ul class="level_'.$level.'">';
			}

			// Add the records of the parent table
			if ($session[$node][$id] == 1)
			{
				if (is_array($childs))
				{
					for ($k=0; $k<count($childs); $k++)
					{
						$return .= $this->generateTree($table, $childs[$k], array('p'=>$childs[($k-1)], 'n'=>$childs[($k+1)]), $blnHasSorting, ($intMargin + $intSpacing), $arrClipboard, ((($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] == 5 && $childs[$k] == $arrClipboard['id']) || $blnCircularReference) ? true : false), ($blnProtected || $protectedPage));
					}
				}
			}

			// Close the submenu
			if (!empty($childs) && $session[$node][$id] == 1)
			{
				$return .= '</ul></li>';
			}
		}

		$this->Session->setData($session);
		return $return;
	}    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    

    /**
     * List all records of the current table as tree and return them as HTML string
     * @return string
     */
    protected function treeView()
    {
        $strTreeView = parent::treeView();

        $arrButtons = array();

        // Added by Patrick Kahl
        // HOOK: call the hooks for clipboardActSelectButtons
        if (isset($GLOBALS['TL_HOOKS']['clipboardActSelectButtonsTreeView']) && is_array($GLOBALS['TL_HOOKS']['clipboardActSelectButtonsTreeView']))
        {
            foreach ($GLOBALS['TL_HOOKS']['clipboardActSelectButtonsTreeView'] as $callback)
            {
                $this->import($callback[0]);
                $arrButtons[] = $this->$callback[0]->$callback[1]($this);
            }
        }

        return preg_replace('/<div.*class="tl_submit_container".*>/', "$0" . implode('', $arrButtons), $strTreeView, 1);
    }

    /**
     * Show header of the parent table and list all records of the current table
     * @return string
     */
    protected function parentView()
    {
        $strParentView = parent::parentView();

        $arrButtons = array();

        // Added by Patrick Kahl
        // HOOK: call the hooks for clipboardActSelectButtons
        if (isset($GLOBALS['TL_HOOKS']['clipboardActSelectButtonsParentView']) && is_array($GLOBALS['TL_HOOKS']['clipboardActSelectButtonsParentView']))
        {
            foreach ($GLOBALS['TL_HOOKS']['clipboardActSelectButtonsParentView'] as $callback)
            {
                $this->import($callback[0]);
                $arrButtons[] = $this->$callback[0]->$callback[1]($this);
            }
        }

        return preg_replace('/<div.*class="tl_submit_container".*>/', "$0" . implode('', $arrButtons), $strParentView, 1);
    }
	
	public function setNewActiveRecord($objRow)
    {
        $this->objActiveRecord = $objRow;
    }

    public function setNewId($intId)
    {
        $this->intId = $intId;
    }

}

?>