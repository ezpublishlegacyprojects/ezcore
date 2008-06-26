/*
    eZ Core BlackBox: tiny element and multiple elements highlighter
    Created on: <09-Des-2007 00:00:00 ar>
    
    Copyright (c) 2007-2008 eZ Systems AS & André Rømcke
    Licensed under the MIT License:
    http://www.opensource.org/licenses/mit-license.php

    Inspired by:
    lightbox
    
    Requirements:
    eZ Core Javascript Library v0.7 or higher
    
    
    Example HTML:
<div class="blackbox_content">some content</div>
    
    Example js:
// create the box
var my = ez.blackbox('blackbox_content', {duration: 400});

// custimize the background and foreground aniamation
my.bg.animationTarget({opacity: 0.5});
my.fg.animationTarget({heigth: 500});

// show index number 2
my.open( 2 );

// show specific element
my.open( ez.$('my_element') );

// close
my.close();

    Example styles:
div.ez_blackbox_bg {
    background-color: black;
    width: 100%;
    height: 100%;
    margin: 0;
    padding: 0;
}
    

*/

ez.element.eZextensions.prototype = ez.object.extend(ez.element.eZextensions.prototype, {
    moveTo: function( newParentNode )
    {
        newParentNode = ez.$(newParentNode);
        if ( newParentNode ) newParentNode.el.appendChild( this.el );
    }
});


ez.blackbox = function( els, settings )
{
    if ( this === ez ) return new ez.blackbox( els, settings );
    this.bg = ez.$(new document.createElement('div'));
    this.box.addClass('ez_blackbox_bg');
    this.box.setStyles({display:'none'});
    this.els = ez.$$( els );
    els.callEach('moveTo', this.box.el);
    document.body.appendChild( this.box.el );
    return this;
};

ez.blackbox.prototype = { 
    open: function( target )
    {
    },
    close: function()
    {
    },
    next: function()
    {
    },
    previous: function()
    {
    }
};




