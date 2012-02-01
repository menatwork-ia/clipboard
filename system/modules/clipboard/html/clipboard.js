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

window.addEvent('domready', function(){

    ClipboardMenu.initialize();
	
    if($('clipboard'))
    {
        $('clipboard').addEvent('mouseover', function(){
            $$('p.button').removeClass('inactive').addClass('active');
        });

        $('clipboard').addEvent('mouseout', function(){
            $$('p.button').removeClass('active').addClass('inactive');
        });
    }
    
    if($('show'))
    {
        $('show').getElement('a').addEvent('click', function(){
            $(this).getParent().addClass('invisible').getNext('p').removeClass('invisible');
            return false;
        }); 
    }
	
    if($('hide'))
    {       
        $('hide').getElement('a').addEvent('click', function(){
            $(this).getParent().addClass('invisible').getPrevious('p').removeClass('invisible');
            return false;
        });
    }
    
    if($('edit'))
    {
        $('edit').addEvent('click', function(){
            var inputElem = new Element('input', {
                type:'text'
            });
            var edit = $$('p.cl_title');
            inputElem.inject(edit[0], top);
        })
    }
	
});

window.addEvent('structure', function(){

    ClipboardMenu.initialize();
	
});

