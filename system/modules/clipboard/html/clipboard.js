/**
 * Class ClipboardMenu
 *
 * Provide methods to handle context menus
 * @copyright  Leo Feyer 2005-2011
 * @author     Leo Feyer <http://www.contao.org>
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
        $$('a.cl_paste').each(function(el)
        {
            el.addClass('invisible');
        });
                
        // Add a trigger to the edit buttons
        $$('a.clipboardmenu').each(function(el)
        {                               
            var arrEl = el.getAllNext('a.cl_paste');
            arrEl.unshift(el);

            // Show the context menu
            el.addEvent('contextmenu', function(e)
            {
                e.preventDefault();
                ClipboardMenu.show(arrEl, e);
            });
        });

        // Hide the context menu 
        $(document.body).addEvent('click', function()
        {
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
            html += '<a href="'+ el.href +'" title="'+ el.title +'">'+ el.get('html') +' '+ el.getFirst('img').alt +'</a>';
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
        if ($defined($('clipboardmenu')))
        {
            $('clipboardmenu').destroy();
        }
    }
};

window.addEvent('domready', function()
{
    ClipboardMenu.initialize();
    $('clipboard').addEvent('mouseover', function(){
        $$('a.edit_button').setStyle('opacity', '1');
    });
    $('clipboard').addEvent('mouseout', function(){
        $$('a.edit_button').setStyle('opacity', '0');
    });    
    $$('a.edit_button').addEvent('click', function(){
        $$('div#cl_show').setStyle('display', 'none');
        $$('div#cl_edit').setStyle('display', 'block');
        return false;
    });  
    $$('a.cancel_button').addEvent('click', function(){
        $$('div#cl_show').setStyle('display', 'block');
        $$('div#cl_edit').setStyle('display', 'none');
        alert('foo');
        return false;
    });      
});

window.addEvent('structure', function()
{
    ClipboardMenu.initialize();
});

