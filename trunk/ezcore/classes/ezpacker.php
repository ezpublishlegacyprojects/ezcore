<?php
//
// Definition of eZPacker class
//
// Created on: <23-Aug-2007 12:42:08 ar>
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
 Functions for merging and packing css and javascript files.
 Reduces page load time both in terms of reducing connections from clients
 and bandwidth ( if packing is turned on ).
 
 Packing has 4 levels:
 0 = off
 1 = merge files
 2 = 1 + remove whitespace
 3 = 2 + remove more whitespace  (jsmin is used for scripts)
 
 In case of css files, relative image paths will be replaced
 by absolute paths.

 You can also use css / js generators to generate content dynamically.
 This is better explained in ezcore.ini[Packer_<function>]

 buildStylesheetFiles and buildJavascriptFiles functions does not return html, just 
 an array of file urls / content (from generators).
 
*/

//include_once( 'lib/ezfile/classes/ezfile.php' );
//include_once( 'lib/ezutils/classes/ezuri.php' );
//include_once( 'kernel/common/ezoverride.php' );

include_once( 'extension/ezcore/lib/jsmin.php' );
include_once( 'extension/ezcore/classes/ezcoreservercall.php' );

class eZPacker
{
    function eZPacker()
    {
    }
    
    static protected $wwwDir = null;
    static protected $cacheDir = null;
    
    // static :: Builds the xhtml tag(s) for scripts
    static function buildJavascriptTag( $scriptFiles, $type, $lang, $packLevel = 2, $wwwInCacheHash = false )
    {
        $ret = '';
        $packedFiles = eZPacker::packFiles( $scriptFiles, 'javascript/', '.js', $packLevel, $wwwInCacheHash );
        foreach ( $packedFiles as $packedFile )
        {
            // Is this a js file or js content?
            if ( strlen( $packedFile ) > 3 && strripos( $packedFile, '.js' ) === ( strlen( $packedFile ) -3 ) )
                $ret .=  $packedFile ? "<script language=\"$lang\" type=\"$type\" src=\"$packedFile\"></script>\r\n" : '';
            else
                $ret .=  $packedFile ? "<script language=\"$lang\" type=\"$type\">\r\n$packedFile\r\n</script>\r\n" : '';
        }
        return $ret;
    }
    
    // static :: Builds the xhtml tag(s) for stylesheets
    static function buildStylesheetTag( $cssFiles, $media, $type, $rel, $packLevel = 3, $wwwInCacheHash = true )
    {
        $ret = '';
        $packedFiles = eZPacker::packFiles( $cssFiles, 'stylesheets/', '_' . $media . '.css', $packLevel, $wwwInCacheHash );
        foreach ( $packedFiles as $packedFile )
        {
            // Is this a css file or css content?
            if ( strlen( $packedFile ) > 4 && strripos( $packedFile, '.css' ) === ( strlen( $packedFile ) -4 ) )
                $ret .= $packedFile ? "<link rel=\"$rel\" type=\"$type\" href=\"$packedFile\" media=\"$media\" />\r\n" : '';
            else
                $ret .= $packedFile ? "<style rel=\"$rel\" type=\"$type\" media=\"$media\">\r\n$packedFile\r\n</style>\r\n" : '';
        }
        return $ret;
    }
    
    
    // static :: Builds a array of script files
    static function buildJavascriptFiles( $scriptFiles, $packLevel = 2, $wwwInCacheHash = false )
    {
        return eZPacker::packFiles( $scriptFiles, 'javascript/', '.js', $packLevel, $wwwInCacheHash );
    }
    
    // static :: Builds a array of stylesheet files
    static function buildStylesheetFiles( $cssFiles, $packLevel = 3, $wwwInCacheHash = true )
    {
        return eZPacker::packFiles( $cssFiles, 'stylesheets/', '_all.css', $packLevel, $wwwInCacheHash );
    }

    // static :: sets the system directories
    static function setSystemDirs()
    {
        if ( self::$cacheDir === null )
        {
            $sys = eZSys::instance();
            self::$cacheDir = $sys->cacheDirectory() . '/public/';
            self::$wwwDir = $sys->wwwDir() . '/';
        }
    }
    
    /* static ::
     Merges a collection of files togheter and returns array of paths to the files.
     js /css content is returned as string if packlevel is 0 and you use a js/ css generator.
     $fileArray can also be array of array of files, like array(  'file.js', 'file2.js', array( 'file5.js' ) )
     The name of the cached file is a md5 hash consistant of the file paths
     of the valid files in $file_array and the packlevel. 
     The whole argument is used instead of file path on js/ css generators in the cache hash.
     */
    static function packFiles( $fileArray, $subPath = '', $fileExtension = '.js', $packLevel = 2, $wwwInCacheHash = false )
    {
        if ( !$fileArray )
        {
            return array();
        }

        $cacheName = '';
        $lastmodified = 0;
        $validFiles = array();
        $validWWWFiles = array();
        $ezCoreIni = eZINI::instance( 'ezcore.ini' );
        $bases   = eZTemplateDesignResource::allDesignBases();
        
        self::setSystemDirs();
        
        $packerInfo = array(
            'file_extension' => $fileExtension,
            'pack_level' => $packLevel,
            'sub_path' => $subPath,
            'cache_dir' => self::$cacheDir,
            'www_dir' => self::$wwwDir,
        );

        if ( $wwwInCacheHash )
        {
            $cacheName = self::$wwwDir;
        }

        while( count( $fileArray ) > 0 )
        {
            $file = array_shift( $fileArray );

            // if $file is array, concat it to the file array and continue
            if ( $file && is_array( $file ) )
            {
                $fileArray = array_merge( $file, $fileArray );
                continue;
            }
            else if ( !$file )
            {
                continue;
            }
            // if the file name contains :: it is threated as a custom code genarator
            else if ( strpos( $file, '::' ) !== false )
            {
                $serverCall = eZCoreServerCall::getInstance( explode( '::', $file ) );
                if ( !$serverCall instanceOf eZCoreServerCall )
                {
                    // continue if not valid
                    continue;
                }
                
                $lastmodified = $serverCall->getCacheTime( $lastmodified, $packerInfo );

                // make sure the function is present on the class
                if ( !$serverCall->hasCall() )
                {
                    eZDebug::writeWarning( 'Could not find function: ' . $serverCall->getCallName() . '()', "eZPacker::packFiles()" );
                    continue;
                }

                $validFiles[] = $serverCall;
                $cacheName   .= $file . '_';
                // generate content straight away if packing is disabled
                if ( $packLevel === 0 )
                {
                   $validWWWFiles[] = $serverCall->call( $packerInfo );
                }
                continue;
            }
            // else: normal css / js files:

            // is it a absolute ?
            if ( strpos( $file, 'var/' ) === 0 )
            {
                if ( substr( $file, 0, 2 ) === '//' || preg_match( "#^[a-zA-Z0-9]+:#", $file ) )
                    $file = '/';
                else if ( strlen( $file ) > 0 &&  $file[0] !== '/' )
                    $file = '/' . $file;

                eZURI::transformURI( $file, true, 'relative' );
            }
            // or is it a relative path
            else
            {
                $file = $subPath . $file;
                $triedFiles = array();
                $match = eZTemplateDesignResource::fileMatch( $bases, '', $file, $triedFiles );

                if ( $match === false )
                {
                    eZDebug::writeWarning( "Could not find: $file", "eZPacker::packFiles()" );
                    continue;
                }
                $file = htmlspecialchars( self::$wwwDir . $match['path'] );
            }

            // get file time and continue if it return false
            $file      = str_replace( '//' . self::$wwwDir, '', '//' . $file );
            $fileTime = @filemtime( $file );

            if ( $fileTime === false )
            {
                eZDebug::writeWarning( "Could not find: $file", "eZPacker::packFiles()" );
                continue;
            }

            // calculate last modified time and store in arrays
            $lastmodified  = max( $lastmodified, $fileTime );
            $validFiles[] = $file;
            $validWWWFiles[] = self::$wwwDir . $file;
            $cacheName   .= $file . '_';
        }

        // if packing is disabled, return the valid paths / content we have generated
        if ( $packLevel === 0 ) return $validWWWFiles;

        if ( !$validFiles )
        {
            eZDebug::writeWarning( "Could not find any files: " . var_export( $fileArray, true ), "eZPacker::packFiles()" );
            return array();
        }

        // generate cache file name and path
        $cacheName = md5( $cacheName . $packLevel ) . $fileExtension;
        $cachePath = self::$cacheDir . $subPath;

        if ( file_exists( $cachePath . $cacheName ) )
        {
            // check last modified time and return path to cache file if valid
            if ( $lastmodified <= filemtime( $cachePath . $cacheName ) )
            {
                return array( self::$wwwDir . $cachePath . $cacheName );
            }
        }

        // Merge file content and create new cache file
        $content = '';
        foreach ( $validFiles as $file )
        {

           // if this is a js / css generator, call to get content
           if ( $file instanceOf eZCoreServerCall )
           {
               $content .= $file->call( $packerInfo );
               continue;
           }

           // else, get content of normal file
           $fileContent = @file_get_contents( $file );

           if ( !trim( $fileContent ) )
           {
               $content .= "/* empty: $file */\r\n";
               continue;
           }

           // we need to fix relative background image paths if this is a css file
           if ( strpos($fileExtension, '.css') !== false )
           {
                $fileContent = eZPacker::fixImgPaths( $fileContent, $file );
           }

           $content .= "/* start: $file */\r\n";
           $content .= $fileContent;
           $content .= "\r\n/* end: $file */\r\n\r\n";
        }

        // Pack the file to save bandwidth
        if ( $packLevel > 1 )
        {
            if ( strpos($fileExtension, '.css') !== false )
                $content = eZPacker::optimizeCSS( $content, $packLevel );
            else
                $content = eZPacker::optimizeScript( $content, $packLevel );
        }

        // save file and return path if sucsessfull
        if( eZFile::create( $cacheName, $cachePath, $content ) )
        {
            return array( self::$wwwDir . $cachePath . $cacheName );
        }

        return array();
    }

    static function fixImgPaths( $fileContent, $file )
    {
        if ( preg_match_all("/url\(\s?[\'|\"]?(.+)[\'|\"]?\s?\)/ix", $fileContent, $urlMatches) )
        {
           $urlMatches = array_unique( $urlMatches[1] );
           $cssPathArray   = explode( '/', $file );
           // pop the css file name
           array_pop( $cssPathArray );
           $cssPathCount = count( $cssPathArray );
           foreach( $urlMatches as $match )
           {
               $match = str_replace( array('"', "'"), '', $match );
               $relativeCount = substr_count( $match, '../' );
               // replace path if it is realtive
               if ( $match[0] !== '/' and strpos( $match, 'http:' ) === false )
               {
                   $cssPathSlice = $relativeCount === 0 ? $cssPathArray : array_slice( $cssPathArray  , 0, $cssPathCount - $relativeCount  );
                   $newMatchPath = self::$wwwDir . implode('/', $cssPathSlice) . '/' . str_replace('../', '', $match);
                   $fileContent = str_replace( $match, $newMatchPath, $fileContent );
               }
           }
        }
        return $fileContent;
    }

    // 'compress' css code by removing whitespace
    static function optimizeCSS( $css, $packLevel )
    {
        // normalize line feeds
        $css = str_replace(array("\r\n", "\r"), "\n", $css);

        // remove multiline comments
        $css = preg_replace('!(?:\n|\s|^)/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);

        // remove whitespace from start and end of line + singelline comment + multiple linefeeds
        $css = preg_replace(array('/\n\s+/', '/\s+\n/', '!\n//.+\n!', '!\n//\n!', '/\n+/'), "\n", $css);

        if ( $packLevel > 2 )
        {
            // remove space around ':' and ','
            $css = preg_replace(array('/:\s+/', '/\s+:/'), ':', $css);
            $css = preg_replace(array('/,\s+/', '/\s+,/'), ',', $css);

            // remove unnecesery line breaks
            $css = str_replace(array(";\n", '; '), ';', $css);
            $css = str_replace(array("}\n","\n}", ';}'), '}', $css);
            $css = str_replace(array("{\n", "\n{", '{;'), '{', $css);

            // optimize css
            $css = str_replace(array(' 0em', ' 0px',' 0pt', ' 0pc'), ' 0', $css);
            $css = str_replace(array(':0em', ':0px',':0pt', ':0pc'), ':0', $css);
            $css = str_replace('0 0 0 0;', '0;', $css);

            // these should use regex to work on all colors
            $css = str_replace(array('#ffffff','#FFFFFF'), '#fff', $css);
            $css = str_replace('#000000', '#000', $css);
        }
        return $css;
    }

    // 'compress' javascript code by removing whitespace
    // uses JSMin if packing level is set to 2 or higher
    static function optimizeScript( $script, $packLevel )
    {
        if ( $packLevel < 3 )
        {
            // normalize line feeds
            $script = str_replace(array("\r\n", "\r"), "\n", $script);
    
            // remove multiline comments
            $script = preg_replace('!(?:\n|\s|^)/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $script);
    
            // remove whitespace from start & end of line + singelline comment + multiple linefeeds
            $script = preg_replace(array('/\n\s+/', '/\s+\n/', '!\n//.+\n!', '!\n//\n!', '/\n+/'), "\n", $script);
        }
        else
        {
            $script = JSMin::minify( $script );
        }
        return $script;
    }
}

?>