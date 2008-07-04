<?php
//
// Created on: <16-Jun-2008 00:00:00 ar>
//
// ## BEGIN COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
// SOFTWARE NAME: eZ Core extension for eZ Publish
// SOFTWARE RELEASE: 1.x
// COPYRIGHT NOTICE: Copyright (C) 2008 eZ Systems AS
// SOFTWARE LICENSE: GNU General Public License v2.0
// NOTICE: >
//   This program is free software; you can redistribute it and/or
//   modify it under the terms of version 2.0  of the GNU General
//   Public License as published by the Free Software Foundation.
//
//   This program is distributed in the hope that it will be useful,
//   but WITHOUT ANY WARRANTY; without even the implied warranty of
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//   GNU General Public License for more details.
//
//   You should have received a copy of version 2.0 of the GNU General
//   Public License along with this program; if not, write to the Free
//   Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
//   MA 02110-1301, USA.
//
//
// ## END COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
//

/*
 * Examples on eZCoreServerCall Functions
 */

class eZCoreServerCallExampleFunctions
{
    /*
     * Example function for returning time stamp
     * + first function argument if present
     */
    public static function time( $args )
    {
        if ( $args && isset( $args[0] ) )
            return $args[0]. '_' . time();
        return time();
    }

    /*
     * Get keywords by input parameters
     * Arguments:
     * - keyword string to search for, but postvariable Keyword
     *   is prefered because of encoding issues in urls
     * - limit, how many suggestions to return, default ini value is used if not set
     * - class id, serach is restricted to class id if set
     */
    public static function keyword( $args )
    {
        $ezcoreINI = eZINI::instance( 'ezcore.ini' );
        $http      = eZHTTPTool::instance();
        
        $keywordLimit            = 30;
        $keywordSuggestionsArray = $ezcoreINI->variable( 'Keyword', 'SuggestionsArray' );
        $classID                 = false;
        $keywordStr              = '';

        if( $http->hasPostVariable( 'Keyword' ) )
            $keywordStr = $http->postVariable( 'Keyword' );
        else if ( isset( $args[0] ) )
            $keywordStr = $args[0];

        if ( isset( $args[1] ) )
            $keywordLimit = (int) $args[1];
        else if( $ezcoreINI->hasVariable( 'Keyword', 'Limit' ) )
            $keywordLimit = (int) $ezcoreINI->variable( 'Keyword', 'Limit' );

        if ( isset( $args[2] ) )
        {
            $classID = (int) $args[2];
        }

        if ( !is_array( $keywordSuggestionsArray ) )
        {
            $keywordSuggestionsArray = array();
        }

        $keywords = array();
        $searchList = array('result' => array() );

        // first return keyword matches from ini
        foreach ( $keywordSuggestionsArray as $string )
        {
            if( $keywordStr === '' or strpos( strtolower( $string ), strtolower( $keywordStr ) ) === 0)
            {
                $keywords[] = $string;
                $keywordLimit--;
                if ( $keywordLimit === 0 ) break;
            }
        }

        if ( $keywordLimit > 0 )
        {
            $searchList = eZContentFunctionCollection::fetchKeyword( $keywordStr, $classID, 0, $keywordLimit );
        }

        //then return matches from database
        foreach ( $searchList['result'] as $node )
        {
            if ( $node['keyword'] )
                $keywords[] = $node['keyword'];
        }

        return $keywords;
    }

    /*
     * Generates the javascript needed to do server calls directly from javascript
    */
    public static function serverCallInit()
    {
        $url = self::getIndexDir() . 'ezcore/call/';
        return "ez.serverCallUrl = '$url';
ez.server = {
    call: function( className, functionName, args, callback, postData )
    {
        args = args.join !== undefined && args.length ? '::' + args.join('::') : '';
        var url = ez.serverCallUrl + className + '::' + functionName + args, a = ez.ajax({'onError': ez.fn.bind( ez.server.errorCallback, this, callback ) });
        a.load( url, postData || null, ez.fn.bind( ez.server.doneCallBack, this, callback ) );
    },
    doneCallBack: function( cb, r )
    {
        if ( cb && cb.call !== undefined )
        {
            var res = ez.server.parseResponse( r )
            cb.call( this, res.content, 0, res.error_text );
        }
    },
    errorCallback: function( cb, code, text )
    {
        if ( cb && cb.call !== undefined ) cb.call( this, '', code, text );
    },
    parseResponse: function( r )
    {
        ez.script( 'ez.server.tmpResponse=' + r.responseText + ';' );
        return ez.server.tmpResponse || {content: '', error_text: 'Invalid json response'};
    }
};
// Example code:
//ez.server.call('ezcore', 'time', [], function( time ){ alert( time ) } );
";
    }

    public static function getCacheTime( $functionName )
    {
        // this data only expires when this timestamp is increased
        return 1211555036;
    }

    protected static function getIndexDir()
    {
        if ( self::$cachedIndexDir === null )
        {
            $sys = eZSys::instance();
            self::$cachedIndexDir = $sys->indexDir() . '/';
        }
        return self::$cachedIndexDir;
    }
    
    protected static $cachedIndexDir = null;
}

?>