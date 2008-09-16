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


// these functions are only set if called via index_ajax.php
if ( !function_exists( 'hasAccessToView' ) )
{
    function exitWithInternalError( $errorText )
    {
        header( $_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error' );
        include_once( 'extension/ezcore/classes/ezajaxcontent.php' );
        $contentType = eZAjaxContent::getHttpAccept();
    
        // set headers
        if ( $contentType === 'xml' )
            header('Content-Type: text/xml; charset=utf-8');
        else if ( $contentType === 'json' )
            header('Content-Type: text/javascript; charset=utf-8');
    
        echo eZAjaxContent::autoEncode( array( 'error_text' => $errorText, 'content' => '' ), $contentType );
        eZExecution::cleanExit();
    }
    
    function hasAccessToView( eZUser $user, eZModule $module, $view = false, $policyCheckOmitList = false )
    {
        if ( $policyCheckOmitList !== false )
        {
            $moduleName = $module->attribute('name');
            if ( in_array( $moduleName, $policyCheckOmitList) )
                return true;
            if ( $view !== false && in_array( $moduleName . '/' . $view, $policyCheckOmitList) )
                return true;
        }
        return $user->hasAccessToView( $module, $view, $params );
    }
}// if ( !function_exists( 'hasAccessToModule' ) )


if ( !isset( $uriParams[1] ) )
{
    exitWithInternalError( "Did not find module info in url." );
    return;
}

// set global module repositories in case this is called from index_ajax.php
eZModule::setGlobalPathList( eZModule::activeModuleRepositories() );

$extensionModule = array_shift( $uriParams );
$module = eZModule::findModule( $extensionModule );
if ( !$module instanceof eZModule )
{
    exitWithInternalError( "'$extensionModule' module does not exist, or is not a valid module." );
    return;
}

$function_name = array_shift( $uriParams );
$moduleViews = $module->attribute('views');
if ( !isset( $moduleViews[ $function_name ] ) )
{
    exitWithInternalError( "'$function_name' view does not exist on the current module." );
    return;
}

// check access to view
$ini          = eZINI::instance();
$currentUser  = eZUser::currentUser();
if ( !hasAccessToView( $currentUser, $module, $function_name, $ini->variable( 'RoleSettings', 'PolicyOmitList' ) ) )
{
    exitWithInternalError( "User does not have access to the $extensionModule/$function_name policy." );
    return;
}

$GLOBALS['eZRequestedModule'] = $module;
$moduleResult = $module->run( $function_name, $uriParams, false, $userParams );

eZDB::checkTransactionCounter();

echo $moduleResult['content'];

eZExecution::cleanExit();
