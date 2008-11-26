/*
    eZ Core Crop: crop extension for ez core js library
    Created on: <09-Des-2007 00:00:00 ar>
    
    Copyright (c) 2007-2008 eZ Systems AS & André Rømcke
    Licensed under the MIT License:
    http://www.opensource.org/licenses/mit-license.php
    
*/


ez.object.extend( ez.element.eZextensions.prototype, {
    //crop functionality as an extension
    cropDiv: 0,
    cropPos: 0,
    cropActive: false,
    cropPositionTop: 0,
    cropPositionLeft: 0,
    crop: function( cropDiv, cropPos, settings )
    {
        this.cropDiv = ez.$( cropDiv );
        this.cropPos = ez.$( cropPos );
        this.addEvent('mousedown', ez.fn.bindEvent( this.cropClick, this ));
        this.addEvent('mousemove', ez.fn.bindEvent( this.cropMove, this ));
        this.cropDiv.addEvent('mousedown', ez.fn.bindEvent( this.cropClick, this ));
        this.cropDiv.addEvent('mousemove', ez.fn.bindEvent( this.cropMove, this ));
        if ( settings ) this.setSettings( settings );
        this.el.style.cursor = 'crosshair';
        return this;
    },
    cropClick: function( e, el )
    {
        if ( this.cropActive === false )
        {
            this.cropActive = true;
            this.cropPositionTop = e.clientY + ez.element.getScroll('top');
            this.cropPositionLeft = e.clientX + ez.element.getScroll('left');
            this.cropPos.setStyles({
                left: this.cropPositionLeft + 'px',
                top: this.cropPositionTop + 'px',
                position: 'absolute',
                zIndex: 90,
                width: 0,
                height: 0
            });
            this.cropDiv.setStyles({
                left: 0,
                top: 0,
                width: 0,
                height: 0,
                position: 'absolute'
            });
            if ( this.settings.onCropStart ) this.settings.onCropStart( this );
        }
        else
        {
            this.cropActive = false;
            this.el.style.cursor = this.cropDiv.el.style.cursor = 'crosshair';
            if ( this.settings.onCropDone ) this.settings.onCropDone( this );
        }
        return false;
    },
    cropMove: function( e )
    {
        if ( this.cropActive )
        {
            var tempHeight = e.clientY + ez.element.getScroll('top') - this.cropPositionTop;
            var tempWidth = e.clientX + ez.element.getScroll('left') - this.cropPositionLeft, ratio = this.settings.cropAspectRatio;
            if ( ratio )
            {
                var tw = Math.round( ratio * tempHeight );
                if ( tw < tempWidth )
                    tempWidth = tw;
                else if ( tw > tempWidth )
                    tempHeight = Math.round( tempWidth / ratio );
            }
            var o = {
                left: ( tempWidth > -1 ) ? 0 : 'auto',
                top: ( tempHeight > -1 ) ? 0 : 'auto',
                right: ( tempWidth < 0 ) ? 0 : 'auto',
                bottom: ( tempHeight < 0 ) ? 0 : 'auto',
                width: Math.abs( tempWidth ) + 'px',
                height: Math.abs( tempHeight ) + 'px',
                cursor: (( tempHeight < 0 ) ? 'n' : 's') + (( tempWidth < 0 ) ? 'w-resize' : 'e-resize')
            };
            this.cropDiv.setStyles( o );
            this.el.style.cursor = o.cursor;
            if ( this.settings.onCropMove ) this.settings.onCropMove( e, o, this );
        }
        return false;
    }
});
