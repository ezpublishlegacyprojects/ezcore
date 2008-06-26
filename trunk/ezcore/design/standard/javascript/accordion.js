/*
    eZ Core Accordion: Accordion extension for ez core js library
    Created on: <09-Des-2007 00:00:00 ar>
    
    Copyright (c) 2007-2008 eZ Systems AS & André Rømcke
    Licensed under the MIT License:
    http://www.opensource.org/licenses/mit-license.php
    
    Optional Extension: Animation
*/

if ( window.ez !== undefined && window.ez.array.eZextensions.prototype.accordionIndex === undefined )
{

ez.object.extend( ez.array.eZextensions.prototype, {
    accordionTarget: {},
    accordionNavigation: 0,
    accordion: function( navs, settings, target )
    {
        if ( settings ) this.setSettings( settings );
        this.accordionIndex = this.settings.accordionStartIndex || 0;
        this.forEach(function( o, i )
        {
            if ( navs && navs[i] )
            {
                navs[i].addEvent('click', ez.fn.bind( this.accordionGoto, this, i ) );
                navs[i].addClass('accordion_navigation');
                if ( i === this.accordionIndex ) navs[ i ].addClass('accordion_selected'); 
            }
            if ( i === this.accordionIndex ) o.addClass('accordion_selected');
            else this.settings.accordionDontAnimateInit ? o.hide( this.settings ) : o.hide( this.settings, target );
        }, this);
        this.accordionNavigation = navs;
        this.accordionTarget = target;
    },
    accordionGoto: function( i )
    {
        if ( i === this.accordionIndex || this[i] === undefined )
        {
            if ( this.settings.accordionOnNoChange ) this.settings.accordionOnNoChange.call( this, i );
            return false;
        }
        else if ( this.settings.accordionOnChange )
        {
            this.settings.accordionOnChange.call( this, i );
        }
        var fn = ez.fn.bind(function( i, tag ){
            if ( tag ) i = ez.$$( tag, this[ i ].el );
            if ( i.length ) i[0].el.focus();
        }, this, i, this.settings.accordionAutoFocusTag || '' ), hide = this[ this.accordionIndex ] !== undefined;
        if ( hide ) this[ this.accordionIndex ].removeClass('accordion_selected');
        if ( this.accordionNavigation && this.accordionNavigation[ this.accordionIndex ] ) this.accordionNavigation[ this.accordionIndex ].removeClass('accordion_selected');
        if ( this.settings.accordionHideDirectly )
        {
            if ( hide ) this[ this.accordionIndex ].hide( this.settings, this.accordionTarget );
            this[ i ].show( this.settings, this.accordionTarget, fn );
        }
        else
        {
            if ( hide ) this[ this.accordionIndex ].hide( this.settings, this.accordionTarget, ez.fn.bind( this[ i ].show, this[ i ], this.settings, this.accordionTarget, fn ) );
            else this[ i ].show( this.settings, this.accordionTarget, fn );
        }
        this[ i ].addClass('accordion_selected');
        if ( this.accordionNavigation && this.accordionNavigation[ i ] ) this.accordionNavigation[ i ].addClass('accordion_selected');
        this.accordionIndex = i;
        if ( this.settings.accordionOnChangeDone )
        {
            this.settings.accordionOnChangeDone.call( this, i );
        }
        return false;
    },
    accordionNext: function()
    {
        if ( this.length === 1 ) return false;
        this.accordionGoto( (this.accordionIndex === this.length -1) ? 0 : this.accordionIndex+1 );
        return false;
        
    },
    accordionPrev: function()
    {
        if ( this.length === 1 ) return false;
        this.accordionGoto( (this.accordionIndex === 0) ? this.length -1 : this.accordionIndex-1 );
        return false;
    }
});

}//if ( window.ez !== undefined && window.ez.array.eZextensions.prototype.accordionIndex === undefined )