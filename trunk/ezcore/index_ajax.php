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

require 'autoload.php';
require_once( 'lib/ezutils/classes/ezsession.php' );
require_once( 'kernel/common/ezincludefunctions.php' );

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

function hasAccessToLogin( $user, $crc32AccessName = false )
{
    $policyChecked = false;
    $siteAccessResult = $user->hasAccessTo( 'user', 'login' );
    if ( $crc32AccessName && $siteAccessResult[ 'accessWord' ] === 'limited' )
    {
        foreach ( $siteAccessResult['policies'] as $policy )
        {
            if ( isset( $policy['SiteAccess'] ) )
            {
                $policyChecked = true;
                if ( in_array( $crc32AccessName, $policy['SiteAccess'] ) )
                    return true;
            }
        }
    }
    if ( $siteAccessResult[ 'accessWord' ] === 'yes' || !$policyChecked )
    {
        return true;
    }
    return false;
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

ignore_user_abort( true );
ob_start();
error_reporting ( E_ALL );

// register fatal error & debug handler
eZExecution::addFatalErrorHandler( 'eZFatalError' );
eZDebug::setHandleType( eZDebug::HANDLE_FROM_PHP );

// Trick to get eZSys working with a script other than index.php (while index.php still used in generated URLs):
$_SERVER['SCRIPT_FILENAME'] = str_replace( '/index_ajax.php', '/index.php', $_SERVER['SCRIPT_FILENAME'] );
$_SERVER['PHP_SELF'] = str_replace( '/index_ajax.php', '/index.php', $_SERVER['PHP_SELF'] );

// set timezone to avoid strict errors
$ini = eZINI::instance();
$timezone = $ini->variable( 'TimeZoneSettings', 'TimeZone' );
if ( $timezone )
{
    putenv( "TZ=$timezone" );
}

// init uri code
$GLOBALS['eZGlobalRequestURI'] = eZSys::serverVariable( 'REQUEST_URI' );
eZSys::init( 'index.php', $ini->variable( 'SiteAccessSettings', 'ForceVirtualHost' ) === 'true' );
$uri = eZURI::instance( eZSys::requestURI() );
$GLOBALS['eZRequestedURI'] = $uri;

require_once 'pre_check.php';

// Check for extension
eZExtension::activateExtensions( 'default' );

// load siteaccess
require_once 'access.php';
$access = accessType( $uri,
                      eZSys::hostname(),
                      eZSys::serverPort(),
                      eZSys::indexFile() );
$access = changeAccess( $access );

// Check for new extension loaded by siteaccess ( disabled for performance reasons )
//eZExtension::activateExtensions( 'access' );

// check module name
$moduleName = $uri->element();
if ( $moduleName === '' || strpos( $moduleName, '.php' ) !== false  )
{
    exitWithInternalError( 'Did not find module info in url.' );
    return;  
}

// chack db connection
$db = eZDB::instance();
if ( $db->isConnected() )
{
    eZSessionStart();
}
else
{
    exitWithInternalError( 'Could not connect to database.' );
}


// Initialize with locale settings
$locale       = eZLocale::instance();
$languageCode = $locale->httpLocaleCode();
$httpCharset  = eZTextCodec::httpCharset();
$phpLocale    = trim( $ini->variable( 'RegionalSettings', 'SystemLocale' ) );
if ( $phpLocale !== '' )
{
    setlocale( LC_ALL, explode( ',', $phpLocale ) );
}

// send header information
$headerList = array( 'Expires' => 'Mon, 26 Jul 1997 05:00:00 GMT',
                     'Last-Modified' => gmdate( 'D, d M Y H:i:s' ) . ' GMT',
                     'Cache-Control' => 'no-cache, must-revalidate',
                     'Pragma' => 'no-cache',
                     'X-Powered-By' => 'eZ Publish',
                     'Content-Type' => 'text/html; charset=' . $httpCharset,
                     'Served-by' => $_SERVER["SERVER_NAME"],
                     'Content-language' => $languageCode );
$headerOverrideArray = eZHTTPHeader::headerOverrideArray( $uri );
$headerList = array_merge( $headerList, $headerOverrideArray );
foreach( $headerList as $key => $value )
{
    header( $key . ': ' . $value );
}

// set default section id
eZSection::initGlobalID();

// get and set ajax module repositories
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

// find module
$module = eZModule::findModule( $moduleName );
if ( !$module instanceof eZModule )
{
    exitWithInternalError( "'$moduleName' module does not exist, or is not a valid ajax module." );
    return;
}

// find module view
$uri->increase();
$viewName = $uri->element();
if ( !$viewName )
{
    exitWithInternalError( 'Did not find view info in url.' );
    return;
}

// verify view name
$uri->increase();
$moduleViews = $module->attribute('views');
if ( !isset( $moduleViews[$viewName] ) )
{
    exitWithInternalError( "'$viewName' view does not exist on the current module." );
    return;
}

// check user/login access
$currentUser = eZUser::currentUser();
if ( !hasAccessToLogin( $currentUser, eZSys::ezcrc32( $access[ 'name' ] ) ) )
{
    exitWithInternalError( 'User does not have access to the current siteaccess.' );
    return;
}

// check access to view
if ( !hasAccessToView( $currentUser, $module, $viewName, $ini->variable( 'RoleSettings', 'PolicyOmitList' ) ) )
{
    exitWithInternalError( "User does not have access to the $moduleName/$viewName policy." );
    return;
}

// run module
$GLOBALS['eZRequestedModule'] = $module;
$moduleResult = $module->run( $viewName, $uri->elements( false ), false, $uri->userParameters() );

// run ouput filter
$classname = eZINI::instance()->variable( "OutputSettings", "OutputFilterName" );
if( class_exists( $classname ) )
{
    $moduleResult['content'] = call_user_func( array ( $classname, 'filter' ), $moduleResult['content'] );
}

// ouput content
$out = ob_get_clean();
echo trim( $out );
eZDB::checkTransactionCounter();
echo $moduleResult['content'];
eZExecution::cleanExit();

?>