<?php
//
// Created on: <30-Sep-2007 00:00:00 ar>
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
// ## BEGIN ABOUT TEXT, SPECS AND DESCRIPTION  ##
//
// MODULE:		ezcore
// VIEW:		attribute_edit
// PARAMS:		'ObjectID', 'AttributeIdentifier', 'AttributeValue'
// OUTPUT:		text
// DESCRIPTION: Script for editing attribute content directly
//              Only owner has access for security reasons
//
// ## END ABOUT TEXT, SPECS AND DESCRIPTION  ##
//

include_once( 'kernel/classes/ezcontentcachemanager.php' );
include_once( 'kernel/classes/ezcontentobject.php' );
include_once( 'kernel/classes/datatypes/ezuser/ezuser.php' );


if ( !isset( $Params['object_id'] ) || !is_numeric($Params['object_id']) )
{
    echo "No ObjectID!";
    eZExecution::cleanExit();
}
else if ( !isset( $Params['attribute_identifier'] ) || !$Params['attribute_identifier'])
{
    echo "No attribute identifier!";
    eZExecution::cleanExit();
}

$Module              = $Params['Module'];
$objectID            = (int) $Params['object_id'];
$attributeIdentifier = trim( $Params['attribute_identifier'] );
$attributeValue      = trim( $Params['attribute_value'] );
$contentObject       = eZContentObject::fetch( $objectID );
$user                = eZUser::currentUser();
$error               = false;

if ( !$contentObject )  $error = 'Could not fetch object!';
else if ( !$user )  $error = 'Could not fetch user object!';
else if ( $contentObject->checkAccess( 'edit' ) == '1' )
{
    $userID           = $user->attribute( 'contentobject_id' );
    $curVersionObject = $contentObject->attribute( 'current' );
    $objectDataMap    = $curVersionObject->attribute('data_map');
    
    if ( isset( $objectDataMap[$attributeIdentifier] ) )
    {
        switch ( $objectDataMap[$attributeIdentifier]->attribute( 'data_type_string' ) )
        {
            // TODO: VALIDATE INPUT
            case 'eztext':
            case 'ezstring':
                $objectDataMap[$attributeIdentifier]->setAttribute("data_text", $attributeValue );
                $objectDataMap[$attributeIdentifier]->store();
                echo 'Stored string value: ' . $attributeValue;
                break;
            case 'ezfloat':
                $objectDataMap[$attributeIdentifier]->setAttribute("data_float", (float) str_replace(',', '.', $attributeValue ) );
                $objectDataMap[$attributeIdentifier]->store();
                echo 'Stored float value: ' . $attributeValue;
                break;
            case 'ezinteger':
            case 'ezboolean':
                $objectDataMap[$attributeIdentifier]->setAttribute("data_int", (int) $attributeValue );
                $objectDataMap[$attributeIdentifier]->store();
                echo 'Stored int/boolean value: ' . $attributeValue;
                break;
            default:
                $error = 'DataType not supported: '. $objectDataMap[$attributeIdentifier]->attribute( 'data_type_string' );
                break;
        }
    } else $error = 'Could not find attribute by identifier \'' . $attributeIdentifier . '\' !';
    
    //return $Module->redirectTo( ( $http->hasPostVariable( 'RedirectURIAfterPublish' ) ) ? $http->postVariable( 'RedirectURIAfterPublish' ) : $parentNode->attribute( 'url_alias' ) );

} else $error = 'You don\'t have sufficient access to this object, only the owner has access to edit the object directly!';

if ( $error ) echo $error;
else
{
    eZContentCacheManager::clearContentCache( $contentObject->attribute( 'id' ), true, false );
    $node = $contentObject->attribute('main_node');
    return $Module->redirectTo( $node->attribute( 'url_alias' ) );
}

echo '<br /><a href="/">Return to website</a>';
eZDB::checkTransactionCounter();
eZExecution::cleanExit();

