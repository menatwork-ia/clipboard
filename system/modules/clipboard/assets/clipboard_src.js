/**
 * Class ClipboardMenu
 *
 * Provide methods to handle context menus
 * @copyright  MEN AT WORK 2012
 * @package    Backend
 */
var ClipboardMenu =
{

    /**
     * Initialize the context menu
     */
    initialize: function()
    {
        // Hide the edit header buttons
        $$('a.cl_paste').each(function(el){
            el.addClass('invisible');
        });
                
        // Add a trigger to the edit buttons
        $$('a.clipboardmenu').each(function(el){                               
            var arrEl = el.getAllNext('a.cl_paste');
            arrEl.unshift(el);

            // Show the context menu
            el.addEvent('contextmenu', function(e){
                e.preventDefault();
                ClipboardMenu.show(arrEl, e);
            });
        });

        // Hide the context menu 
        $(document.body).addEvent('click', function(){
            ClipboardMenu.hide();
        });
    },


    /**
     * Show the context menu
     * @param arrEl
     * @param e
     */
    show: function(arrEl, e)
    {
        ClipboardMenu.hide();
        var div = new Element('div',
        {
            'id': 'clipboardmenu',
            'styles': {
                'top': (arrEl[0].getPosition().y - 6)
            }
        });

        var html = '';
        arrEl.each(function(el, index){
            html += '<a href="'+ el.href +'" title="'+ el.title +'" onclick="Backend.getScrollOffset();">'+ el.get('html') +' '+ el.getFirst('img').alt +'</a>';
        });
            
        div.set('html',html);
        div.inject($(document.body));
        div.setStyle('left', arrEl[0].getPosition().x - (div.getSize().x / 2));
    },


    /**
     * Hide the context menu
     */
    hide: function()
    {
        if ($('clipboardmenu') != null)
        {
            $('clipboardmenu').destroy();
        }
    }
};

/**
 * Class Clipboard
 *
 * Provide methods to handle the clipboard buttons and edit title functionality
 * @copyright  MEN AT WORK 2012
 * @package    clipboard
 * @license    GNU/GPL 2
 */
var Clipboard = 
{
    
    /**
     * Initialize the clipboard
     */
    initialize: function()
    {  
        // Add hover events to button bars
        if($('clipboard'))
        {        
            $('clipboard').addEvent('mouseover', function(){
                Clipboard.showAllButtos();
            });
            
            $('clipboard').addEvent('mouseout', function(){
                Clipboard.hideAllButtons();
            });
        }
        
        // Add edit title event
        if($('edit'))
        {
            $('edit').addEvent('click', function(event){
                event.stop();
                Clipboard.editTitle();
                Clipboard.showSave();
            })
        }
        
        // Add cancel edit title event
        if($('cancel'))
        {
            $('cancel').addEvent('click', function(event){
                event.stop();
                Clipboard.cancelEditTitle();
                Clipboard.showEdit();
            });
        }     
    },
    
    /**
     * Start the edit title functionality
     */
    editTitle: function()
    {        
        var edit = $$('p.cl_title');
        edit.each(function(el, index){
            var input = el.getElement('input').removeProperty('readonly').addClass('edit');
        });      
    },
    
    /**
     * Cancel the edit title functionality
     */
    cancelEditTitle: function()
    {
        var save = $$('p.cl_title');
        save.each(function(el, index){
            var input = el.getElement('input').setProperty('readonly', 'readonly').removeClass('edit');
        });          
    },
    
    /**
     * Hide edit button bar and show save button bar
     */
    showSave: function()
    {
        $('hide').removeClass('invisible');
        $('show').addClass('invisible');
    },
    
    /**
     * Hide show button bar and show edit button bar
     */
    showEdit: function()
    {
        $('show').removeClass('invisible');
        $('hide').addClass('invisible');
    },
    
    /**
     * Add all button bars
     */
    showAllButtos: function()
    {
        $$('p.button').removeClass('inactive').addClass('active');    
    },
    
    /**
     * Remove all button bars
     */
    hideAllButtons: function()
    {
        $$('p.button').removeClass('active').addClass('inactive');
    } 
};

window.addEvent('domready',function(){

    if ($('clipboard') && ((window.innerWidth - 980) / 2) > 290) {
        $('clipboard').addClass('toolbox');
    } else if($('clipboard')) {
        $('clipboard').removeClass('toolbox');
    }

});

window.addEvent('resize', function() {

    if ($('clipboard') && ((window.innerWidth - 980) / 2) > 290) {
        $('clipboard').addClass('toolbox');
    } else if($('clipboard')) {
        $('clipboard').removeClass('toolbox');
    }

});