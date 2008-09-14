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

$uriParams = $Params['Parameters'];
$userParams = $Params['UserParameters'];

if ( !isset( $uriParams[1] ) )
{
    exitWithInternalError( "Did not find module info in url." );
    return;
}

// set global module repositories in case this is called from index_ajax.php
eZModule::setGlobalPathList( eZModule::activeModuleRepositories() );


$module = eZModule::findModule( array_shift( $uriParams ) );
if ( !$module instanceof eZModule )
{
    exitWithInternalError( "'$extensionModule' module does not exist, or is not a valid ajax module." );
    return;
}

$function_name = array_shift( $uriParams );
$moduleViews = $module->attribute('views');
if ( !isset( $moduleViews[ $function_name ] ) )
{
    exitWithInternalError( "'$function_name' view does not exist on the current module." );
    return;
}

$GLOBALS['eZRequestedModule'] = $module;
$moduleResult = $module->run( $function_name, $uriParams, false, $userParams );

return $moduleResult;