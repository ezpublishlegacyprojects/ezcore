/*
    eZ Core clickNdrop: tiny click and drop script
    Created on: <02-Nov-2007 00:00:00 ar>
    
    Copyright (c) 2007-2008 eZ Systems AS & André Rømcke
    Licensed under the MIT License:
    http://www.opensource.org/licenses/mit-license.php
    
    Requirements:
    eZ Core Javascript Library v0.8 or higher    
*/


ez.clickNdrop = function( drag, drop, pool, settings )
{
    if ( this === ez ) return new ez.clickNdrop( drag, drop, pool, settings );
    this.dr = ez.$$( drag );
    this.dp = ez.$$( drop );
    this.pool = ez.$( pool );
    this.pool.addClass('drag_pool');
    this.dp.callEach('addClass', 'dropable');
    this.dr.callEach('addClass', 'dragable');
    this.dp.callEach('addEvent', 'mouseover', ez.fn.bindEvent(function( e, el, o ){ if ( this.active ) o.addClass('drop_mouseover'); }, this));
    this.dp.callEach('addEvent', 'mouseout', function(){ this.removeClass('drop_mouseover'); });
    this.dr.callEach('addEvent', 'mouseover', function(){ this.addClass('drag_mouseover'); });
    this.dr.callEach('addEvent', 'mouseout', function(){ this.removeClass('drag_mouseover'); });
    this.dr.callEach('addEvent', 'click', ez.fn.bindEvent(function( e, el, o ){
        this.dr.callEach('removeClass', 'drag_clicked');
        if ( o.isChildOfElement( this.pool.el ) )
        {
            // select a dragable
            o.addClass('drag_clicked');
            this.active = o;
        }
        else
        {
            // select a droped dragable
            var drop = el.parentNode;
            this.backInPool( e, el );
            this.moveActive( e, drop );
        }
        this.dr.callEach('removeClass', 'drag_mouseover');
    }, this));
    this.dp.callEach('addEvent', 'click', ez.fn.bindEvent(this.moveActive, this)); 
    return this;
};

ez.clickNdrop.prototype = {
    backInPool: function( e, el )
    {
        this.pool.el.appendChild( el );
    },
    moveActive: function( e, target )
    {
        if ( !this.active )
        {
            target.removeClass('drop_has_content');
            return;
        }
        if ( this.active.el.parentNode !== target )
        {
             target.appendChild( this.active.el );
             target.addClass('drop_has_content');
        }
        this.active.removeClass('drag_clicked');
        this.active = null;
    }
};

