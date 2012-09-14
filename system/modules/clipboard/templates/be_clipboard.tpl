<?php if ($this->clipboard): ?>
<div id="clipboard">
    <div class="inside">
    <h1><?php echo $GLOBALS['TL_LANG']['MSC']['clipboard']; ?></h1>
    <div>
        <form action="<?php echo $this->action; ?>" method="post">
            <input type="hidden" name="REQUEST_TOKEN" value="<?php echo REQUEST_TOKEN; ?>">
            <ul>
            <?php foreach ($this->clipboard as $id => $item): ?>
                <li<?php echo ($item->getFavorite()) ? ' class="featured"' : ''; ?>>
                    <p class="edit">
                        <a title="<?php echo $GLOBALS['TL_LANG']['MSC']['featureSelected']; ?>" href="<?php echo $this->addToUrl('key=cl_favor&amp;cl_id=' . $id); ?>">
                            <img height="16" width="16" alt="<?php echo $GLOBALS['TL_LANG']['MSC']['featureSelected']; ?>" src="<?php echo $GLOBALS['CLIPBOARD']['favorite']['icon' . ((!$item->getFavorite()) ? '_' : '')]; ?>" />
                        </a>
                        <a onclick="if (!confirm('<?php echo sprintf($GLOBALS['TL_LANG']['MSC']['deleteConfirm'], $id); ?>')) return false; Backend.getScrollOffset();" title="<?php echo $GLOBALS['TL_LANG']['MSC']['deleteSelected']; ?>" href="<?php echo $this->addToUrl('key=cl_delete&amp;cl_id=' . $id); ?>">
                            <img height="16" width="14" alt="<?php echo $GLOBALS['TL_LANG']['MSC']['deleteSelected']; ?>" src="system/themes/default/images/delete.gif" />
                        </a>
                        <?php if($item->getChilds()): ?>
                            <img class="exception" title="<?php echo $GLOBALS['TL_LANG']['MSC']['titleChild']; ?>" height="16" width="14" alt="<?php echo $GLOBALS['TL_LANG']['MSC']['titleChild']; ?>" src="<?php echo $GLOBALS['CLIPBOARD']['childs']['icon']; ?>" />
                        <?php endif; ?>
                        <?php if($item->getEncryptionKey() != md5($GLOBALS['TL_CONFIG']['encryptionKey'])): ?>
                            <img class="exception" title="<?php echo $GLOBALS['TL_LANG']['MSC']['importedClipboard']; ?>" height="16" width="18" alt="<?php echo $GLOBALS['TL_LANG']['MSC']['importedClipboard']; ?>" src="<?php echo $GLOBALS['CLIPBOARD']['imported']['icon']; ?>" />
                        <?php endif; ?>
                        <?php if($item->getGroup()): ?>
                        <img class="icon" height="16" width="16" title="<?php echo $item->getGroup(); ?><?php echo (($item->getGroupCount() > 0) ? ' (' . $item->getGroupCount() . ')' : ''); ?>" alt="<?php echo $item->getGroup(); ?>" src="<?php echo $GLOBALS['CLIPBOARD']['group']['icon']; ?>" />
                        <span title="<?php echo $item->getGroup(); ?>">
                            <?php echo $item->getGroup(); ?><?php echo (($item->getGroupCount() > 0) ? ' (' . $item->getGroupCount() . ')' : ''); ?>
                        </span>
                        <?php endif; ?>
                        <?php if($item->getAttribute()): ?>
                        <img class="icon" height="16" width="16" title="<?php echo $item->getAttribute(); ?>" alt="<?php echo $item->getAttribute(); ?>" src="<?php echo $GLOBALS['CLIPBOARD']['attribute']['icon']; ?>" />
                        <span title="<?php echo $item->getAttribute(); ?>"><?php echo $item->getAttribute(); ?></span>
                        <?php endif; ?>
                    </p>
                    <p class="cl_title">
                        <input maxlength="24" class="<?php echo ((strlen($item->getTitle())) ? '' : 'empty '); ?>text" readonly="readonly" type="text" name="title[<?php echo $id; ?>]" value="<?php echo $item->getTitle(); ?>" />
                        
                    </p>
                </li>
            <?php endforeach; ?>
            </ul>
            <p id="show" class="inactive button">
                <a id="edit" href="#" onfocus="Backend.getScrollOffset()"><?php echo $GLOBALS['TL_LANG']['MSC']['editSelected']; ?></a>
            </p>
            <p id="hide" class="inactive invisible button">
                <a id="cancel" href="#" onfocus="Backend.getScrollOffset()"><?php echo $GLOBALS['TL_LANG']['MSC']['cancelBT']; ?></a>&nbsp;
                <button id="save" onfocus="Backend.getScrollOffset()"><?php echo $GLOBALS['TL_LANG']['MSC']['save']; ?></button>
            </p>
        </form>  
    </div>
    </div>
</div>
<?php endif; ?>

<script>
window.addEvent('domready', function(){<?php if($this->isContext): ?>ClipboardMenu.initialize();<?php endif; ?>Clipboard.initialize();});
window.addEvent('structure', function(){<?php if($this->isContext): ?>ClipboardMenu.initialize();<?php endif; ?>});  
</script>
