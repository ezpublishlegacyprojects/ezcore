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
 * Brief: eZCore contentobjectrate ajax call
 * Lets you call custom php code(s) from javascript to return json / xhtml / xml / text 
 */

include_once( 'extension/ezcore/classes/ezcoreservercall.php' );
include_once( 'extension/ezcore/classes/ezajaxcontent.php' );

$http           = eZHTTPTool::instance();
$callType       = isset($Params['type']) ? $Params['type'] : 'call';
$contentType    = isset($Params['content_type']) && $Params['content_type'] ? $Params['content_type'] : 'json';
$callFnList     = array();

if ( isset($Params['interval']) && $Params['interval'] > 49 )
    $callInterval = $Params['interval'] * 1000; // intervall in milliseconds, minimum is 0.05 seconds 
else
    $callInterval = 500 * 1000;//default interval is every 0.5 seconds

if ( $http->hasPostVariable( 'function_arguments' ) )
    $callList = explode( ':::', $http->postVariable( 'function_arguments' ) );
else if ( isset($Params['function_arguments']) )
    $callList = explode( ':::', $Params['function_arguments'] );
else
    $callList = array();

if ( $http->hasPostVariable( 'call_seperator' ) )
    $callSeperator = $http->postVariable( 'call_seperator' );
else
    $callSeperator = '@SEPERATOR#';

if ( $http->hasPostVariable( 'stream_seperator' ) )
    $stramSeperator = $http->postVariable( 'stream_seperator' );
else
    $stramSeperator = '@END#';
    
//prepere calls
foreach( $callList as $call )
{
    $callFnList[] = eZCoreServerCall::getInstance( explode( '::', $call ), true, true );
}

$callFnListCount = count( $callFnList ) -1;

// set headers
if ( $contentType === 'xml' )
    header("Content-Type: text/xml; charset=utf-8");
else
    header("Content-Type: text/javascript; charset=utf-8");

// do calls
if ( $callType === 'stream' )
{
    $endTime = time() + 29;
    while ( @ob_end_clean() );
    // flush 256 bytes first to force ie to not buffer the stream
    if ( strpos( eZSys::serverVariable( 'HTTP_USER_AGENT' ), 'MSIE' ) !== false )
    {
        echo '                                                  ';
        echo '                                                  ';
        echo '                                                  ';
        echo '                                                  ';
        echo "                                                  \n";
    }
    //set_time_limit(65);
    while( time() < $endTime )
    {
        echo $stramSeperator . implode( $callSeperator, multipleeZCoreServerCalls( $callFnList, $contentType ) );
        flush();
        usleep( $callInterval );
    }
}
else
{
    echo implode( $callSeperator, multipleeZCoreServerCalls( $callFnList, $contentType ) );
}


function multipleeZCoreServerCalls( $calls, $contentType = 'json' )
{
    $r = array();
    foreach( $calls as $key => $call )
    {
        $response = array( 'error_text' => '', 'content' => '' );
        if( $call instanceOf eZCoreServerCall )
        {
            $response['content'] =  $call->call();
        }
        else
        {
            $response['error_text'] = 'not a valid server call';
        }
        $r[] = $contentType === 'xml' ? eZAjaxContent::xmlEncode( $response ) : eZAjaxContent::jsonEncode( $response );
    }
    return $r;
}

eZDB::checkTransactionCounter();
eZExecution::cleanExit();
