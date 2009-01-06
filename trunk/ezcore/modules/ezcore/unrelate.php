<?php
//
// Created on: <17-Sep-2007 00:00:00 ar>
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
 * Brief: eZCore RemoveRelation ajax rate
 * Lets you remove attribute relations directly
*/


//include_once( 'kernel/classes/ezcontentobject.php' );
//include_once( 'kernel/classes/ezcontentcachemanager.php' );


$Module              = $Params['Module'];
$objectId            = $Params['contentobject_id'];
$attributeIdentifier = trim( $Params['attribute_identifier'] );
$relateObjectId      = $Params['relate_to_object_id'];

$user                = eZUser::currentUser();
$userID              = (int) $user->attribute( 'contentobject_id' );

$objectId       = (int) $objectId == 'user_id' ? $userID : $objectId;
$relateObjectId = (int) $relateObjectId == 'user_id' ? $userID : $relateObjectId;

$contentObject       = $objectId ? eZContentObject::fetch( $objectId ) : false;
$dataMap             = $contentObject ? $contentObject->attribute('data_map') : false;
$relateObject        = $relateObjectId ? eZContentObject::fetch( $relateObjectId ) : false;
$is_updated          = false;

if ( !$user->attribute('is_logged_in') )
{
    echo "You need to be logged in to be allowed to relate content!<br \/><a href='JavaScript:history.go(-1)'>Go Back</a>";
    eZExecution::cleanExit();
}
else if ( !$relateObject )
{
    echo "Could not fetch related object($relateObjectId)!<br \/><a href='JavaScript:history.go(-1)'>Go Back</a>";
    eZExecution::cleanExit();
}
else if ( !isset( $dataMap[$attributeIdentifier] ) )
{
    echo "Could not fetch object($objectId) or idenfifier($attributeIdentifier) is missing!<br \/><a href='JavaScript:history.go(-1)'>Go Back</a>";
    eZExecution::cleanExit();
}
else if ( !$contentObject->checkAccess('edit') )
{
    echo 'There was something wrong with the object, or you don\' have access to edit it..<br \/><a href="JavaScript:history.go(-1)">Go Back</a>';
    eZExecution::cleanExit();
}
else if ( !in_array( $dataMap[$attributeIdentifier]->attribute('data_type_string'), array('ezobjectrelationlist', 'ezobjectrelationlistamount') ) )
{
    echo 'Only object relations and extended object relation are supported!';
}
else
{
    $content = $dataMap[$attributeIdentifier]->content();
    $newContent = array('relation_list' => array() );
    $priority = 0;
    
    for ( $i = 0, $l = count( $content['relation_list'] ); $i < $l; ++$i )
    {
        if ( $content['relation_list'][$i]['contentobject_id'] == $relateObjectId )
        {
            $is_updated = true;
        }
        else
        {
            ++$priority;
            $content['relation_list'][$i]['priority'] = $priority;
            $newContent['relation_list'][] = $content['relation_list'][$i];
        }
    }
}

if ( $is_updated )
{
    $dataMap[$attributeIdentifier]->setContent( $newContent );
    $dataMap[$attributeIdentifier]->store();
    eZContentCacheManager::clearContentCache( $objectId, true, array( $relateObject->attribute('main_node_id') ) );
    eZContentCacheManager::clearTemplateBlockCache( $relateObjectId );
    echo 'ok: Relation removed!';
}
else
{
    echo 'Could not find relation!';
}


$http = eZHTTPTool::instance();
if ( $http->hasSessionVariable( "LastAccessesURI" ) )
{
    $lastAccessedViewURI = $http->sessionVariable( "LastAccessesURI" );
}
else
{
    $node = $contentObject->attribute('main_node');
    $lastAccessedViewURI = $node->attribute('url_alias');
}

return $Module->redirectTo( $lastAccessedViewURI );
 
 
 
 
 
?>