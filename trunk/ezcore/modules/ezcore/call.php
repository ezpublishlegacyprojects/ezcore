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

include_once( 'extension/ezcore/autoloads/ezpacker.php' );

$http           = eZHTTPTool::instance();
$callType       = isset($Params['type']) ? $Params['type'] : 'call';

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
    $callSeperator = '@SEPERATOR@';

if ( $http->hasPostVariable( 'stream_seperator' ) )
    $stramSeperator = $http->postVariable( 'stream_seperator' );
else
    $stramSeperator = '@END@';


if ( $callType === 'stream' )
{
    $endTime = time() + 6;
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
        echo $stramSeperator . implode($callSeperator, eZPacker::buildJavascriptFiles($callList , 0) );
        flush();
        usleep($callInterval);
    }
}
else
{
    echo implode($callSeperator, eZPacker::buildJavascriptFiles($callList , 0) );
}

eZDB::checkTransactionCounter();
eZExecution::cleanExit();
