<?php
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

// Set a default time zone if none is given. The time zone can be overriden
// in config.php or php.ini.
if ( !ini_get( "date.timezone" ) )
{
    date_default_timezone_set( "UTC" );
}

/*define( 'MAX_AGE', 86400 );

if ( isset( $_SERVER['HTTP_IF_MODIFIED_SINCE'] ) )
{
    header( $_SERVER['SERVER_PROTOCOL'] . ' 304 Not Modified' );
    header( 'Expires: ' . gmdate( 'D, d M Y H:i:s', time() + MAX_AGE ) . ' GMT' );
    header( 'Cache-Control: max-age=' . MAX_AGE );
    header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s', strtotime( $_SERVER['HTTP_IF_MODIFIED_SINCE'] ) ) . ' GMT' );
    exit();
}*/

require_once( 'lib/ezutils/classes/ezsession.php' );
require_once( 'kernel/common/ezincludefunctions.php' );
require 'autoload.php';

function ezupdatedebugsettings()
{
}

function eZFatalError()
{
    header( $_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error' );
}

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

function hasAccessToModule( $user, $module, $view = false, $policyCheckOmitList = false, $crc32AccessName = false )
{
    if ( $policyCheckOmitList !== false )
    {
        if ( in_array( $module, $policyCheckOmitList) )
            return true;
        if ( $view !== false && in_array( $module . '/' . $view, $policyCheckOmitList) )
            return true;
    }
    $siteAccessResult = $user->hasAccessTo( $module, $view );
    if ( $crc32AccessName && $siteAccessResult[ 'accessWord' ] == 'limited' )
    {
        $policyChecked = false;
        foreach ( $siteAccessResult['policies'] as $policy )
        {
            if ( isset( $policy['SiteAccess'] ) )
            {
                $policyChecked = true;
                if ( in_array( $crc32AccessName, $policy['SiteAccess'] ) )
                {
                    return true;
                }
            }
        }
        if ( !$policyChecked )
        {
            return true;
        }
    }
    else if ( $siteAccessResult[ 'accessWord' ] == 'yes' )
    {
        return true;
    }
    return false;
}

ignore_user_abort( true );
ob_start();
error_reporting ( E_ALL );

eZExecution::addFatalErrorHandler( 'eZFatalError' );
eZDebug::setHandleType( eZDebug::HANDLE_FROM_PHP );

// Trick to get eZSys working with a script other than index.php (while index.php still used in generated URLs):
$_SERVER['SCRIPT_FILENAME'] = str_replace( '/index_ajax.php', '/index.php', $_SERVER['SCRIPT_FILENAME'] );
$_SERVER['PHP_SELF'] = str_replace( '/index_ajax.php', '/index.php', $_SERVER['PHP_SELF'] );

$ini = eZINI::instance();

$timezone = $ini->variable( 'TimeZoneSettings', 'TimeZone' );
if ( $timezone )
{
    putenv( "TZ=$timezone" );
}

$GLOBALS['eZGlobalRequestURI'] = eZSys::serverVariable( 'REQUEST_URI' );

eZSys::init( 'index.php', $ini->variable( 'SiteAccessSettings', 'ForceVirtualHost' ) === 'true' );

$uri = eZURI::instance( eZSys::requestURI() );
$GLOBALS['eZRequestedURI'] = $uri;

require_once 'pre_check.php';

// Check for extension
eZExtension::activateExtensions( 'default' );

require_once 'access.php';
$access = accessType( $uri,
                      eZSys::hostname(),
                      eZSys::serverPort(),
                      eZSys::indexFile() );
$access = changeAccess( $access );

// Check for new extension loaded by siteaccess ( disabled for performance reasons )
//eZExtension::activateExtensions( 'access' );

$extensionModule = $uri->element();
if ( $extensionModule === '' || strpos( $extensionModule, '.php' ) !== false  )
{
    exitWithInternalError( 'Did not find module info in url.' );
    return;  
}

$moduleINI = eZINI::instance( 'module.ini' );
$globalModuleRepositories = array( );
if ( $moduleINI->hasVariable( 'ModuleSettings', 'ExtensionAjaxRepositories' ) )
{
    foreach ( $moduleINI->variable( 'ModuleSettings', 'ExtensionAjaxRepositories' ) as $repo )
    {
        $globalModuleRepositories[] = 'extension/' . $repo . '/modules';
    }
}

eZModule::setGlobalPathList( $globalModuleRepositories );

$module = eZModule::findModule( $extensionModule );
if ( !$module instanceof eZModule )
{
    exitWithInternalError( "'$extensionModule' module does not exist, or is not a valid ajax module." );
    return;
}

$uri->increase();
$function_name = $uri->element();
$uri->increase();

if ( !$function_name )
{
    exitWithInternalError( 'Did not find view info in url.' );
    return;
}

$moduleViews = $module->attribute('views');
if ( !isset( $moduleViews[$function_name] ) )
{
    exitWithInternalError( "'$function_name' view does not exist on the current module." );
    return;
}

$db = eZDB::instance();
if ( $db->isConnected() )
{
    eZSessionStart();
}
else
{
    exitWithInternalError( 'Could not connect to database.' );
    return;
}

$currentUser = eZUser::currentUser();
if ( !hasAccessToModule( $currentUser, 'user', 'login', false, eZSys::ezcrc32( $access[ 'name' ] ) ) )
{
    exitWithInternalError( 'User does not have access to the current siteaccess.' );
    return;
}

$functionList = $module->attribute('available_functions');
$hasAccess    = false;
if ( isset( $functionList[$function_name] ) )
{
    $hasAccess = hasAccessToModule( $currentUser, $extensionModule, $function_name, $ini->variable( 'RoleSettings', 'PolicyOmitList' ) );
}
else
{
    $hasAccess = hasAccessToModule( $currentUser, $extensionModule, false, $ini->variable( 'RoleSettings', 'PolicyOmitList' ) );
}

if ( !$hasAccess )
{
    exitWithInternalError( "User does not have access to the $extensionModule/$function_name policy." );
    return;
}

$GLOBALS['eZRequestedModule'] = $module;
$moduleResult = $module->run( $function_name, $uri->elements( false ) );

eZExecution::cleanExit();

?>