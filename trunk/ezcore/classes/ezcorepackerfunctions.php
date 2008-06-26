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
 * Generates all i18n strings for the TinyMCE editor
 * and transforms them to the TinyMCE format for
 * translations.
 */

class eZCorePackerFunctions
{
    public static function test( $args )
    {
        if ( $args && isset( $args[0] ) )
            return $args[0]. '_' . time();
        return time();
    }
    
    public static function serverCallInit()
    {
        $url = self::getIndexDir() . 'ezcore/call/';
        return "ez.serverCallUrl = $url;
ez.server = {

};
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