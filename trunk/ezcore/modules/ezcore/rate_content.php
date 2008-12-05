<?php
//
// Created on: <17-Sep-2007 00:00:00 ar>
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
 * Brief: eZCore contentobjectrate ajax rate
 * Lets users rate by link / ajax GET request
 */


//include_once( 'kernel/classes/ezcontentobject.php' );
//include_once( 'lib/ezdb/classes/ezdb.php' );

$Module   = $Params['Module'];
$objId    = (int) $Params['contentobject_id'];
$rate     = is_numeric( $Params['object_rating'] ) ? (float) str_replace(',', '.', $Params['object_rating'] ) : null;
$ret      = 'ok';
$obj      = $objId ? eZContentObject::fetch( $objId ) : false;
$user     = eZUser::currentUser();
$ini      = eZINI::instance();
$anonymId = $ini->variable( 'UserSettings', 'AnonymousUserID' );
$userId   = $user ? $user->attribute('contentobject_id') : $anonymId;
$now      = time();
$maxRate  = 5;


if ( !$obj )
{
    $ret = 'Could not find page!';
}
else if ( !$obj->checkAccess('read') )
{
    $ret = "You don't have read access to the given object!";
}
else if ( $userId == $anonymId or !$user->attribute('is_logged_in') )
{
    $ret = "You need to be logged in to be allowed to rate content!";
}
else if ( $rate === null or $rate > $maxRate )
{
    $ret = "Rating must be a number between 0 and $maxRate!";
}
else
{
    $db = eZDB::instance();
    $rs = $db->arrayQuery( "SELECT COUNT(*)
                            FROM
                             ezcontentobject_rating 
                            WHERE
                             contentobject_id=$objId AND user_id=$userId" );
    if ( $rs === false )
    {
        $ret = "Rating table is missing, contact administrator!";
        eZDebug::writeError( 'ezcontentrating table is missing!', 'ezcore/rate' );
    }
    else if ( $rs[0]['COUNT(*)'] !== '0' )
    {
        $ret = "You are only allowed to vote 1 time per unique page!";
    }
    else
    {
        $rs = $db->query( "INSERT INTO ezcontentobject_rating 
                           VALUES ($objId, $userId, $rate, $now)" );
        if ( $rs !== true )
        {
            $ret = "Something went wrong on rating insert, contact administrator!";
        }
    }
}

if ( $ret === 'ok' ) eZContentCacheManager::clearContentCache( $objId, true, false );
else echo $ret;

$http = eZHTTPTool::instance();
$lastAccessedViewURI = '/';

if ( $http->hasSessionVariable( 'LastAccessesURI' ) )
{
    $lastAccessedViewURI = $http->sessionVariable( 'LastAccessesURI' );
}
else if ( $obj )
{
    $node = $obj->attribute('main_node');
    $lastAccessedViewURI = $node->attribute('url_alias');
}

return $Module->redirectTo( $lastAccessedViewURI );

//eZExecution::cleanExit();
