<?php
//
// Created on: <26-Jun-2008 07:42:00 ar>
// Forked from: ezsquidcachemanager.php in eZ Flow <15-Feb-2007 11:25:31 bf>
//
// ## BEGIN COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
// SOFTWARE NAME: eZ Core
// SOFTWARE RELEASE: 1.x
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

/*
   DEPRECATED: USe this instead: http://projects.ez.no/all2evcc should work a bit better since it uses workflow.
   TODO: Merge multisite purge stuff in eZReverseProxyCacheManager to all2evcc
 */


class eZReverseProxyCacheManager
{
    /**
     * Constructor
     * 
     * @access protected
     */
    protected function __construct()
    {
    }

    /**
     * Purges the given relative URL on the reverse proxy server. 
     * Behavour is defined by reverse_proxy.ini settings.
     * 
     * @static
     * @param string $path contains url you want to purge e.g. /en/products/url_alias_for_page
     * @param mixed $site_id is optional site identifier as used by reverse_proxy.ini settings
     */
    static function purgeURL( $path, $siteId = false )
    {
        $settings = self::getSettings();
        $siteIdList = array();
        $hostNameList = array();
        if ( $siteId )
        {
        	$siteIdList[] = $siteId;
        }

        // Find SiteId from UrlToSiteIdMap
        foreach( $settings['UrlToSiteIdMap'] as $urlMap )
        {
            if ( self::urlMatch( $path, $urlMap[0] ) )
            {
                if ( $urlMap[2] === 'negative' )
                {
                    $siteIdList = array_diff( $siteIdList, $urlMap[1] );
                }
                else if ( !$urlMap[2] || ($urlMap[2] === 'no_site' && !$siteId) )
                {
                    $siteIdList = array_merge( $siteIdList, array_diff( $urlMap[1], $siteIdList ) );
                }
            }
        }
        //eZDebug::writeDebug( 'SiteIdList is: ' . var_export( $siteIdList, true ) . ' where siteId from caller is: '. $siteId , 'eZReverseProxyCacheManager::purgeURL' );

		// Make host name array to purge based on $siteIdList
        foreach( $siteIdList as $selectedSiteId )
        {
            if ( isset( $settings['SiteIdToHostMap'][$selectedSiteId] ) )
            {
                $hostNameList = array_merge( $hostNameList, $settings['SiteIdToHostMap'][$selectedSiteId] );
            }
            else
            {
                eZDebug::writeError( 'SiteId: ' . $selectedSiteId .' not defined in: [UrlHostMapping]SiteIdToHostMap', 'eZReverseProxyCacheManager::purgeURL' );
            }
        }
        $hostNameList = array_unique( $hostNameList );

        if ( !$settings['UnGreedy'] || !$siteIdList )
        {
            self::purge( $settings['HostName'], $settings['Port'], $settings['TimeOut'], $path );
        }

        foreach( $hostNameList as $hostName )
        {
            self::purge( $hostName, $settings['Port'], $settings['TimeOut'], $path );
        }
        return true;
    }

    /**
     * Purges the given relative URL on the reverse proxy server for a specific host.
     * Use {@link eZReverseProxyCacheManager::purgeURL()} unless you now what you are doing.
     * 
     * @static
     * @param string $hostName is the domain to purge.
     * @param int $port port number for the host name.
     * @param int $timeOut the timeout for the connection.
     * @param string $path contains url you want to purge e.g. /en/products/url_alias_for_page
     * @return string|null Returns null if the purge fails, if it suceeds it will return the response
     */
    static function purge( $hostName, $port, $timeOut, $path )
    {
        $rawResponse = '';
        $errorNumber = '';
        $errorString = '';

        // Try to connect to server 
        $fp = fsockopen( $hostName,
                         $port,
                         $errorNumber,
                         $errorString,
                         $timeOut );
        if ( !$fp )
        {
            eZDebug::writeError( 'Error connecting to: ' . $hostName .':'. $port . " error string: $errstr ($errno)" , 'eZReverseProxyCacheManager::purge' );
            return null;
        }

        $HTTPRequest = 'PURGE ' . $path . " HTTP/1.0\r\n" .
                       "Accept: */*\r\n\r\n";

        // Try to put(write) PURGE to server
        if ( !fputs( $fp, $HTTPRequest, strlen( $HTTPRequest ) ) )
        {
            eZDebug::writeError( 'Error purging: ' . $path .' on host: ' . $hostName .':'. $port, 'eZReverseProxyCacheManager::purge' );
            return null;
        }

        // fetch the response
        while ( $data = fread( $fp, 32768 ) )
        {
            $rawResponse .= $data;
        }

        // close the socket
        fclose( $fp );

        eZDebug::writeNotice( 'Successfully purged: ' . $path .' on host: ' . $hostName .':'. $port, 'eZReverseProxyCacheManager::purge' );

        return $rawResponse;
    }

    /**
     * Sees if a given url matches a given url pattern or returns false if not.
     * 
     * @static
     * @param string $url
     * @param string $urlPattern the url pattern to match
     * @return bool
     */
    static function urlMatch( $url, $urlPattern )
    {
        $url = '/' . eZURLAliasML::cleanURL( $url );
        $urlPattern = '/' . eZURLAliasML::cleanURL( $urlPattern );
        if ( $url == $urlPattern )
        {
            return true;
        }
        else if ( strpos( $urlPattern, '*') !== false )
        {
            if ( strpos( $url, str_replace( '*', '', $urlPattern ) ) === 0 )
            {
                return true;
            }
        }
        return false;
    }

    /**
     * Gets the settings and prepers them so less code is needed to use them later.
     * As in arrays are exploded and UrlToSiteIdMap always returns a 3 dimmension array.
     * 
     * @static
     * @return array
     */
    static public function getSettings()
    {
        if ( self::$settings === null )
        {
            $ini = eZINI::instance( 'reverse_proxy.ini' );
            self::$settings              = array();
            self::$settings['Port']      = (int) $ini->variable( 'ReverseProxy', 'Port' );
            self::$settings['TimeOut']   = (int) $ini->variable( 'ReverseProxy', 'Timeout' );
            self::$settings['HostName']  = $ini->variable( 'ReverseProxy', 'HostName' );
            self::$settings['UnGreedy']  = $ini->variable( 'ReverseProxy', 'UnGreedy' ) === 'enabled';
            self::$settings['UrlToSiteIdMap']  = $ini->variable( 'UrlHostMapping', 'UrlToSiteIdMap' );
            self::$settings['SiteIdToHostMap'] = $ini->variable( 'UrlHostMapping', 'SiteIdToHostMap' );
            for( $i = 0; $i < count( self::$settings['UrlToSiteIdMap'] ); ++$i )
            {
                self::$settings['UrlToSiteIdMap'][$i] = explode( ';', self::$settings['UrlToSiteIdMap'][$i] );
                self::$settings['UrlToSiteIdMap'][$i][1] = explode( ',', self::$settings['UrlToSiteIdMap'][$i][1] );
                if ( !isset( self::$settings['UrlToSiteIdMap'][$i][2] ) )
                    self::$settings['UrlToSiteIdMap'][$i][] = '';
            }
            foreach( self::$settings['SiteIdToHostMap'] as $key => $value )
            {
                self::$settings['SiteIdToHostMap'][$key] = explode( ',', $value );
            }
        }
        return self::$settings;
    }

    /**
     * Internal storage for settings as used by "getSettings()"
     * 
     * @access protected
     * @see function getSettings
     */
    static protected $settings = null;    
}

?>