/**
 * 
 */
var ClipboardArticleTree =  new Class({
    /**
     * Initialize the clipboard
     */
    initialize: function()
    {
        var arrArticleTree = $$('#tl_listing ul.tl_listing li.tl_folder');
        arrArticleTree.each(function(elem){           
            var pid = this.getFolderId(elem);   
            var id = this.getFavoriteElemId();
            var rightButtonContainer = elem.getChildren('div.tl_right')[0];
            var link = this.getNewButtonElem(pid, id);
            link.inject(rightButtonContainer);
        }.bind(this));
    },
    
    getFolderId: function(elem)
    {
        var arrLinkHref = elem.getChildren('div.tl_left').getChildren(':last-child')[0].get('href')[0].split('&');
        var id;
        
        arrLinkHref.each(function(linkFragment){
            if(linkFragment.contains('node'))
            {
                var arrNode = linkFragment.split('=');
                id = arrNode[1];
                return;
            }
        }.bind(id));
        
        return id;
    },
    
    getNewButtonElem: function(pid, id)
    {
        var url = window.location.href;
        var arrUrl = url.split('&');
        
        var link = new Element('a',
        {
            href: arrUrl[0] + '&act=copy&mode=2&pid=' + pid + '&id=' + id,
            'class': 'test',
            html: '<img height="16" width="16" src="system/themes/default/images/pasteinto.gif" />',
            events: {
                click: function(){
                    Backend.getScrollOffset();
                }
            }
        });
        return link;
    },
    
    getFavoriteElemId: function()
    {   
        return $$('#clipboard input[name=favorite]')[0].get('value');
    }
});

//act=copy&
//mode=2&
//pid=16&
//id=13

window.addEvent('domready', function(){

    var test = new ClipboardArticleTree();
	
});