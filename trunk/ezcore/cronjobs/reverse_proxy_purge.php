<?php
//
// Created on: <26-Jun-2008 07:42:00 ar>
//
// ## BEGIN COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
// SOFTWARE NAME: eZ Publish
// SOFTWARE RELEASE: 4.1.x
// COPYRIGHT NOTICE: Copyright (C) 1999-2008 eZ Systems AS
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

/*! \file reverse_proxy_purge.php
*/

//include_once( 'lib/ezdb/classes/ezdb.php' );
include_once( 'extension/ezcore/classes/ezreverseproxycachemanager.php' );

/*
  This is a delayed reverse proxy purge that uses the static cache code in 3.10
  and higher that enables you to do delayed ( so it dosn't slow down the publishing process )
  static cleaning. So you can NOT run this cronjob thogheter with staticcache_clean.php

  You need to enable staticcache.ini[CacheSettings]CronjobCacheClear and site.ini[ContentSettings]StaticCache
  and configure your reverse proxy server settings in reverse_proxy.ini to make this work.
*/

if ( !$isQuiet )
{
    $cli->output( "Starting processing pending static cache cleanups for your reverse proxy" );
}

$db = eZDB::instance();

$offset      = 0;
$limit       = 200;
$doneList    = array();
$count       = $db->arrayQuery( "SELECT count(DISTINCT param) as count FROM ezpending_actions WHERE action = 'static_store'" );
$count       = (int) $count[0]['count'];
$iterations  = (int) ceil( $count / $limit );
$inDeleteSQL = '';

for ($i = 1; $i <= $iterations; $i++)
{
    $entries = $db->arrayQuery( "SELECT DISTINCT param FROM ezpending_actions WHERE action = 'static_store'",
                                array( 'limit' => $limit,
                                       'offset' => $offset ) );
    

    if ( is_array( $entries ) and count( $entries ) )
    {
        foreach ( $entries as $entry )
        {
            $param = $entry['param'];
            $source = explode( ',', $param );
            $source = explode('/', $source[1] );
            array_shift( $source ); // remove http:
            array_shift( $source ); // remove empty space between // in http://
            array_shift( $source ); // remove host
            $source = '/' . implode( '/', $source );
            $response = false;

            if ( !isset( $doneList[$source] ) )
            {
                $response = eZReverseProxyCacheManager::purgeURL( $source );
                $doneList[$source] = 1;

                if ( !$isQuiet && $response === null )
                {
                    $cli->output( 'Could not purge url: ' . $source );
                }
                else if ( $response !== null )
                {
                    //$cli->output( 'Reverse proxy returned response: ' . $response );
                    //$cli->output( 'Relative url used in put: ' . $source );
                    if ( $inDeleteSQL !== '' )
                    {
                        $inDeleteSQL .= ', ';
                    }
                    $inDeleteSQL .= '\'' . $param . '\'';
                }
            }
        }
    }
    else
    {
        break; // No valid result from ezpending_actions
    }
    $offset += $limit;
}

if ( $inDeleteSQL !== '' )
{
    $db->begin();
    $db->query( "DELETE FROM ezpending_actions WHERE action='static_store' AND param IN ($inDeleteSQL)" );
    $db->commit();
}

if ( !$isQuiet )
{
    $cli->output( "Done! Purged " . count( $doneList )  . " url's"  );
}

?>