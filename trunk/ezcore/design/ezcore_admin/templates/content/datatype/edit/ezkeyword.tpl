{default attribute_base=ContentObjectAttribute}
<input id="ezcoa-{if ne( $attribute_base, 'ContentObjectAttribute' )}{$attribute_base}-{/if}{$attribute.contentclassattribute_id}_{$attribute.contentclass_attribute_identifier}" class="box ezcc-{$attribute.object.content_class.identifier} ezcca-{$attribute.object.content_class.identifier}_{$attribute.contentclass_attribute_identifier} ezcca-edit-keyword" type="text" size="70" name="{$attribute_base}_ezkeyword_data_text_{$attribute.id}" value="{$attribute.content.keyword_string|wash(xhtml)}" autocomplete="off" />
{/default}


{run-once}

{ezscript( array('ez_core.js', 'animation.js', 'ezcore::server') )}

<script type="text/javascript">
<!--
{literal}
ez.element.addEvent( window, 'load', function()
{
    ezcoreKeyword.inputs = ez.$$('input.ezcca-edit-keyword');
    
    // if there are input keyword boxes on this page, we'll have to attache a couple of events
    if ( ezcoreKeyword.inputs.length > 0 )
    {
        //Create div for suggestions
        var newDiv = document.createElement('div');
        newDiv.id = 'ezcore_keyword_suggestion_dropdown';
        document.body.appendChild( newDiv );
        ezcoreKeyword.div = ez.$( newDiv );
        
        // Avoid that enter submits the form while selecting keyword
        var tempForm = document.getElementById('editform');
        if ( tempForm ) tempForm.onsubmit = function(){ if ( ezcoreKeyword.dropdownLI != 0 ) return false; else return true; };

        //Cleanup and hide the keywords when the input box loses focus
        ezcoreKeyword.inputs.callEach( 'addEvent', 'blur', function(){ ezcoreKeyword.dropdownLI = 0; ezcoreKeyword.div.hide( {duration:500, transition: ez.fx.sinoidal}, {height:0, display:'none'} ); });
        
        //event function on keydown on the input element
        ezcoreKeyword.inputs.callEach( 'addEvent', 'keydown', ezcoreKeyword.press );

        ezcoreKeyword.div.hide( {duration:500, transition: ez.fx.sinoidal}, {height:0, display:'none'} );
    }
});

// var currentClassID = {$#object.content_class.id}, 
var ezcoreKeyword = {
    inputs : 0,
    dropdownLI  : 0,
    div : null,
    timeout: null,
    inpIndex : -1,
    liIndex : 0,
    callBack: function( content, errorCode, errorText )
    { 
        // errorCode is 0 if everything went ok
        var html = '<ul><li>' + content.join('</li><li>') + '</li>';
        ezcoreKeyword.div.el.innerHTML = html + '</ul>';

        var inp = ezcoreKeyword.inputs[ ezcoreKeyword.inpIndex ];
        ezcoreKeyword.div.setStyles({
            top: inp.getPosition('top') + inp.getSize('height', true) + 'px',
            left: inp.getPosition('left') + 'px',
            width: inp.getSize('width', true) + 'px'
        });

        ezcoreKeyword.dropdownLI = ez.$$('#ezcore_keyword_suggestion_dropdown li');
        ezcoreKeyword.dropdownLI.callEach('addEvent', 'mouseover', ezcoreKeyword.mouse, ezcoreKeyword );
        ezcoreKeyword.dropdownLI.callEach('addEvent', 'mousedown', ez.fn.bind( ezcoreKeyword.enter, ezcoreKeyword, inp.el ) );

        ezcoreKeyword.div.show( {duration:500, transition: ez.fx.sinoidal}, {height:0, display:'none'} );
    },
    press : function( e, el )
    {
        clearTimeout(ezcoreKeyword.timeout);
        e = e || window.event;
        
        //cancle that the event bubbles up to the form element
        e.cancelBubble = true;
        if ( e.stopPropagation ) e.stopPropagation();
        
        var c = e.keyCode || e.which;
        var keyword = ez.string.trim( el.value.split(',').pop() );
        
        //break any futher action on specific keys like backspace
        if (c == 44 || c == 8 || c == 188 || c == 32 || keyword.length < 1) return true;
        else if ( (c == 38 || c == 40) && ezcoreKeyword.dropdownLI != 0 ) return ezcoreKeyword.select( c );
        else if ( c == 13 ) return ezcoreKeyword.enter( el );
        
        ezcoreKeyword.div.hide( {duration:500, transition: ez.fx.sinoidal}, {height:0, display:'none'} );
        ezcoreKeyword.inpIndex = ezcoreKeyword.indexOfElement( el, ezcoreKeyword.inputs );
        ezcoreKeyword.timeout = setTimeout( 'ezcoreKeyword.call( "' + keyword + String.fromCharCode(c) + '" )', ((c == 46) ? 200 : 100) );
        return true;
    },
    select : function( c )
    {
        var i = ezcoreKeyword.liIndex;
        ezcoreKeyword.liIndex = i = (i < 0) ? 0 : i + c - 39;
        ezcoreKeyword.liIndex = i = ( ezcoreKeyword.dropdownLI.length <= i ) ? ezcoreKeyword.dropdownLI.length : i;
        return ezcoreKeyword.setClass( i );
    },
    mouse : function( e, el )
    {
        var i = ezcoreKeyword.liIndex = ezcoreKeyword.indexOfElement( el, ezcoreKeyword.dropdownLI ) + 1;
        return ezcoreKeyword.setClass( i );
    },
    setClass: function( i )
    {
        if( i > 0 ) ezcoreKeyword.dropdownLI[ i - 1 ].addClass( 'selected' );
        else ezcoreKeyword.dropdownLI.callEach( 'removeClass', 'selected' );
        return false;
    },
    enter : function( el )
    {
        if ( ezcoreKeyword.dropdownLI != 0 && ezcoreKeyword.liIndex > 0 )
        {
           var arr = el.value.split(',');
           arr[arr.length -1] = ' ' + ezcoreKeyword.dropdownLI[ ezcoreKeyword.liIndex - 1 ].el.innerHTML;
           el.value = ez.string.trim( arr.join(',') );
           el.focus();
        }
       ezcoreKeyword.liIndex = 0;
       ezcoreKeyword.div.hide( {duration:500, transition: ez.fx.sinoidal}, {height:0, display:'none'} );
       return false;
    },
    indexOfElement: function( el, arr )
    {
        var index = -1;
        if ( arr ) arr.forEach( function( o, i ){
            if ( (el.id && el.id === o.el.id) || el === o.el ) index = i;
        });
        return index;
    },
    call : function( key )
    {
        ez.server.call( 'ezcore::keyword::' + key, ezcoreKeyword.callBack );
    }
};
{/literal}
-->
</script>
{/run-once}