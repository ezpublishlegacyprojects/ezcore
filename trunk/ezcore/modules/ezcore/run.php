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
 * Brief: eZCore module run
 * A light redirector to be able to run other modules indirectly,
 * when this module has rewrite rules to run under index_ajax.php
 * Net effect is ~3 times faster execution.
 */

$uriParams = $Params['Parameters'];
$userParams = $Params['UserParameters'];

// These functions are only set if called via index_ajax.php
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


// look for module and view info in uri parameters
if ( !isset( $uriParams[1] ) )
{
    exitWithInternalError( "Did not find module info in url." );
    return;
}

// set global module repositories in case this is called from index_ajax.php
eZModule::setGlobalPathList( eZModule::activeModuleRepositories() );

// find module
$moduleName = array_shift( $uriParams );
$module = eZModule::findModule( $moduleName );
if ( !$module instanceof eZModule )
{
    exitWithInternalError( "'$moduleName' module does not exist, or is not a valid module." );
    return;
}

// check existance of view
$viewName = array_shift( $uriParams );
$moduleViews = $module->attribute('views');
if ( !isset( $moduleViews[ $viewName ] ) )
{
    exitWithInternalError( "'$viewName' view does not exist on the current module." );
    return;
}

// check access to view
$ini          = eZINI::instance();
$currentUser  = eZUser::currentUser();
if ( !hasAccessToView( $currentUser, $module, $viewName, $ini->variable( 'RoleSettings', 'PolicyOmitList' ) ) )
{
    exitWithInternalError( "User does not have access to the $moduleName/$viewName policy." );
    return;
}

// run module view
$GLOBALS['eZRequestedModule'] = $module;
$moduleResult = $module->run( $viewName, $uriParams, false, $userParams );

// ouput result and end exit cleanly
eZDB::checkTransactionCounter();
echo $moduleResult['content'];
eZExecution::cleanExit();
