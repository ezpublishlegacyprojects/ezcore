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
 * Brief: eZCore AddRelation ajax rate
 * Lets you add attribute relations directly
*/


//include_once( 'kernel/classes/ezcontentobject.php' );
//include_once( 'kernel/classes/ezcontentcachemanager.php' );


$Module              = $Params['Module'];
$objectId            = $Params['contentobject_id'];
$attributeIdentifier = trim( $Params['attribute_identifier'] );
$relateObjectId      = $Params['relate_to_object_id'];
$relateObjectVar1    = trim( $Params['relation_var1'] );
$relateObjectVar2    = trim( $Params['relation_var2'] );
$replaceByVars       = (int) $Params['replace_by_vars'];

$user                = eZUser::currentUser();
$userID              = (int) $user->attribute( 'contentobject_id' );

$objectId       = (int) $objectId == 'user_id' ? $userID : $objectId;
$relateObjectId = (int) $relateObjectId == 'user_id' ? $userID : $relateObjectId;

$contentObject       = $objectId ? eZContentObject::fetch( $objectId ) : false;
$dataMap             = $contentObject ? $contentObject->attribute('data_map') : false;
$relateObject        = $relateObjectId ? eZContentObject::fetch( $relateObjectId ) : false;

if ( !$user->attribute('is_logged_in') )
{
    echo "You need to be logged in to be allowed to relate content!<br \/><a href='JavaScript:history.go(-1)'>Go Back</a>";
    eZExecution::cleanExit();
}
else if ( $objectId === $relateObjectId )
{
    echo "Circular relations not allowed!<br \/><a href='JavaScript:history.go(-1)'>Go Back</a>";
    eZExecution::cleanExit();
}
else if ( !$relateObject )
{
    echo "Could not fetch related object($relateObjectId)!<br \/><a href='JavaScript:history.go(-1)'>Go Back</a>";
    eZExecution::cleanExit();
}
else if ( !isset( $dataMap[$attributeIdentifier] ) )
{
    echo "Could not fetch object($objectId) or identifier($attributeIdentifier) is missing!<br \/><a href='JavaScript:history.go(-1)'>Go Back</a>";
    eZExecution::cleanExit();
}
else if ( !in_array( $dataMap[$attributeIdentifier]->attribute('data_type_string'), array('ezobjectrelationlist', 'ezobjectrelationlistamount') ) )
{
    echo 'Only object relations and extended object relation are supported!<br \/><a href="JavaScript:history.go(-1)">Go Back</a>';
    eZExecution::cleanExit();
}
else if ( !$contentObject->checkAccess('edit') )
{
    echo 'There was something wrong with the object, or you don\' have access to edit it..<br \/><a href="JavaScript:history.go(-1)">Go Back</a>';
    eZExecution::cleanExit();
}

/*    TODO: check placement recursively.
      $placement = (int) isset( $classContent['default_placement']['node_id'] ) ? $classContent['default_placement']['node_id'] : 1;
 */
$classContent = $dataMap[$attributeIdentifier]->attribute('class_content');
$classList = $classContent['class_constraint_list'];

if ( !in_array( $relateObject->attribute('class_identifier'),  $classList  ) )
{
    echo 'You are not allowed to relate class of type ' . $relateObject->attribute('class_identifier') . ' to this object!<br \/><a href="JavaScript:history.go(-1)">Go Back</a>';
    eZExecution::cleanExit();
}

$extendedMode = ($dataMap[$attributeIdentifier]->attribute('data_type_string') === 'ezobjectrelationlistamount');
$content = $dataMap[$attributeIdentifier]->content();
$priority = 0;
$is_new = true;
$replace = false;
if ( $replaceByVars and $extendedMode )
{
    for ( $i = 0; $i < count( $content['relation_list'] ); ++$i )
    {
        if ( $content['relation_list'][$i]['custom_amount'] == $relateObjectVar1 and
                  $content['relation_list'][$i]['custom_amount_type'] == $relateObjectVar2 )
        {
          $replace = true;
          break;
        }
    }

}

for ( $i = 0; $i < count( $content['relation_list'] ); ++$i )
{
    if ( $content['relation_list'][$i]['priority'] > $priority )
    {
        $priority = $content['relation_list'][$i]['priority'];
    }

    if ( !$replace and $content['relation_list'][$i]['contentobject_id'] == $relateObjectId )
    {
        //update existing relation
        $is_new = false;
        if ( $extendedMode )
        {
            $content['relation_list'][$i]['custom_amount'] = $relateObjectVar1;
            $content['relation_list'][$i]['custom_amount_type'] = $relateObjectVar2;
        }
        break;
    }
    else if ( $replace and $content['relation_list'][$i]['custom_amount'] == $relateObjectVar1 and
              $content['relation_list'][$i]['custom_amount_type'] == $relateObjectVar2 )
    {
        // replace existing relation based on position
        $is_new = false;
        $relatedClass = $relateObject->attribute('content_class');
        $content['relation_list'][$i]['contentobject_id']        = $relateObjectId;
        $content['relation_list'][$i]['contentobject_version']   = $relateObject->attribute( 'current_version' );
        $content['relation_list'][$i]['contentobject_remote_id'] = $relateObject->attribute( 'remote_id' );
        $content['relation_list'][$i]['node_id']                 = $relateObject->attribute( 'main_node_id' );
        $content['relation_list'][$i]['parent_node_id']          = $relateObject->attribute( 'main_parent_node_id' );
        $content['relation_list'][$i]['contentclass_id']         = $relatedClass->attribute( 'id' );
        $content['relation_list'][$i]['contentclass_identifier'] = $relatedClass->attribute( 'identifier' );
        break;
    }
}

if ( $is_new )
{
    ++$priority;
    $classAttribute = $dataMap[$attributeIdentifier]->attribute('contentclass_attribute');
    $dataType       = $classAttribute->attribute('data_type');
    $content['relation_list'][] = $dataType->appendObject( $relateObjectId, $priority, $dataMap[$attributeIdentifier], $relateObjectVar1, $relateObjectVar2 );
    echo 'ok: New relation added!';
}
else
{
    echo 'ok: Existing relation ' . ($replace ? 'replaced' : 'updated') . '!';
}

$dataMap[$attributeIdentifier]->setContent( $content );
$dataMap[$attributeIdentifier]->store();

eZContentCacheManager::clearContentCache( $objectId, true, array( $relateObject->attribute('main_node_id') ) );
eZContentCacheManager::clearTemplateBlockCache( $relateObjectId );

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