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

class eZReverseProxyCacheManager
{
    /**
     * Constructor
     *
     */
    function __construct()
    {
    }

    /**
     * Purges the given relative URL on the reverse proxy server. 
     * E.g. /en/products/url_alias_for_page
     * Returns null if the purge fails, if it suceeds it will return the response (a string)
     * 
     * @static
     * @param string $path
     * @return mixed
     */
    static function purgeURL( $path )
    {

        $ini = eZINI::instance( 'reverse_proxy.ini.ini' );

        $server = $ini->variable( 'ReverseProxy', 'Server' );
        $port = $ini->variable( 'ReverseProxy', 'Port' );
        $timeout = $ini->variable( 'ReverseProxy', 'Timeout' );

        $rawResponse = '';
        $errorNumber = '';
        $errorString = '';

        $fp = fsockopen( $server,
                         $port,
                         $errorNumber,
                         $errorString,
                         $timeout );

        $HTTPRequest = "PURGE " . $path . " HTTP/1.0\r\n" .
                       "Accept: */*\r\n\r\n";

        if ( !fputs( $fp, $HTTPRequest, strlen( $HTTPRequest ) ) )
        {
            // print("Error purging cache" );
            return  null;
        }        

        // fetch the response
        while ( $data = fread( $fp, 32768 ) )
        {
            $rawResponse .= $data;
        }

        // close the socket
        fclose( $fp );
        return $rawResponse;
      }

      /*static function isEnabled()
      {
          $ini = eZINI::instance( 'reverse_proxy.ini' );
          return $ini->variable( 'ReverseProxy', 'PurgeCacheOnPublish' ) === 'enabled' );
      }*/
}

?>