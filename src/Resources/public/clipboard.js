/**
 * Class ClipboardMenu
 *
 * Provide methods to handle context menus
 * @copyright  MEN AT WORK 2012
 * @package    Backend
 */
var ClipboardMenu={initialize:function(){$$("a.cl_paste").each(function(a){a.addClass("invisible")});$$("a.clipboardmenu").each(function(b){var a=b.getAllNext("a.cl_paste");a.unshift(b);b.addEvent("contextmenu",function(c){c.preventDefault();ClipboardMenu.show(a,c)})});$(document.body).addEvent("click",function(){ClipboardMenu.hide()})},show:function(a,c){ClipboardMenu.hide();var d=new Element("div",{id:"clipboardmenu",styles:{top:(a[0].getPosition().y-6)}});var b="";a.each(function(f,e){b+='<a href="'+f.href+'" title="'+f.title+'" onclick="Backend.getScrollOffset();">'+f.get("html")+" "+f.getFirst("img").alt+"</a>"});d.set("html",b);d.inject($(document.body));d.setStyle("left",a[0].getPosition().x-(d.getSize().x/2))},hide:function(){if($("clipboardmenu")!=null){$("clipboardmenu").destroy()}}};

/**
 * Class Clipboard
 *
 * Provide methods to handle the clipboard buttons and edit title functionality
 * @copyright  MEN AT WORK 2012
 * @package    clipboard
 * @license    GNU/GPL 2
 */
var Clipboard={initialize:function(){if($("clipboard")){$("clipboard").addEvent("mouseover",function(){Clipboard.showAllButtos()});$("clipboard").addEvent("mouseout",function(){Clipboard.hideAllButtons()})}if($("edit")){$("edit").addEvent("click",function(a){a.stop();Clipboard.editTitle();Clipboard.showSave()})}if($("cancel")){$("cancel").addEvent("click",function(a){a.stop();Clipboard.cancelEditTitle();Clipboard.showEdit()})}},editTitle:function(){var a=$$("p.cl_title");a.each(function(d,c){var b=d.getElement("input").removeProperty("readonly").addClass("edit")})},cancelEditTitle:function(){var a=$$("p.cl_title");a.each(function(d,c){var b=d.getElement("input").setProperty("readonly","readonly").removeClass("edit")})},showSave:function(){$("hide").removeClass("invisible");$("show").addClass("invisible")},showEdit:function(){$("show").removeClass("invisible");$("hide").addClass("invisible")},showAllButtos:function(){$$("p.button").removeClass("inactive").addClass("active")},hideAllButtons:function(){$$("p.button").removeClass("active").addClass("inactive")}};window.addEvent("domready",function(){if($("clipboard")&&((window.innerWidth-980)/2)>290){$("clipboard").addClass("toolbox")}else{if($("clipboard")){$("clipboard").removeClass("toolbox")}}});window.addEvent("resize",function(){if($("clipboard")&&((window.innerWidth-980)/2)>290){$("clipboard").addClass("toolbox")}else{if($("clipboard")){$("clipboard").removeClass("toolbox")}}});