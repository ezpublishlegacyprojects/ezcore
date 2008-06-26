<?php
//
// Created on: <22-Nov-2007 00:00:00 ar>
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
 * Brief: eZCore GetRelated
 * get relations as a json object
 * 
 * contentobject_id: the object you want relations to (or from if in reverse_mode)
 * attribute_identifier: the attribute that has the relations
 * 
 * Optional params:
 * class_identifier: is only used in reverse_mode
 * reverse_mode: if this is evaluated to true, reverse relations are  fetched
 * related_to_id: to see if fetched relations are related to a third object
*/

include_once( 'extension/ezcore/classes/ezajaxcontent.php' );

$Module              = $Params['Module'];
$objectId            = $Params['contentobject_id'];
$attributeIdentifier = trim( $Params['attribute_identifier'] );
$classIdentifier     = trim( $Params['class_identifier'] );
$reverseMode         = !!$Params['reverse_mode'];
$relatedToId         = $Params['related_to_id'];

$user          = eZUser::currentUser();
$userID        = $user->attribute( 'contentobject_id' );
$objectId      = (int) $objectId == 'user_id' ? $userID : $objectId;
$relatedToId      = (int) $relatedToId == 'user_id' ? $userID : $relatedToId;

$contentObject = $objectId ? eZContentObject::fetch( $objectId ) : false;
$dataMap       = $contentObject ? $contentObject->attribute('data_map') : false;

if ( !$user->attribute('is_logged_in') )
{
    echo "You need to be logged in to be allowed to relate content!<br \/><a href='JavaScript:history.go(-1)'>Go Back</a>";
    eZExecution::cleanExit();
}
else if ( !$reverseMode && !isset( $dataMap[$attributeIdentifier] ) )
{
    echo "Could not fetch object($objectId) or idenfifier($attributeIdentifier) is missing!<br \/><a href='JavaScript:history.go(-1)'>Go Back</a>";
    eZExecution::cleanExit();
}
else if ( !$reverseMode && !in_array( $dataMap[$attributeIdentifier]->attribute('data_type_string'), array('ezobjectrelationlist', 'ezobjectrelationlistamount') ) )
{
    echo 'Only object relations and extended object relation are supported!<br \/><a href="JavaScript:history.go(-1)">Go Back</a>';
    eZExecution::cleanExit();
}
else if (  !$contentObject->checkAccess('read') )
{
    echo 'You don\'t have access to read thsi object!<br \/><a href="JavaScript:history.go(-1)">Go Back</a>';
    eZExecution::cleanExit();
}

$returnArray = array();

if ( $reverseMode )
{
    $identifier = eZContentObjectTreeNode::classAttributeIDByIdentifier( $classIdentifier . '/' . $attributeIdentifier );
    if ( !$identifier )
    {
        echo 'Could not find identifier ' . $classIdentifier . '/' . $attributeIdentifier . ' !<br \/><a href="JavaScript:history.go(-1)">Go Back</a>';
        eZExecution::cleanExit();
    }
    $idArray = array();
    $db = eZDB::instance();
    $returnArray = $db->arrayQuery( "SELECT ezco.id as contentobject_id,
                   ezco.remote_id as contentobject_remote_id,
                   ezco.name as name,
                   ezco.current_version as contentobject_version,
                   ezcc.identifier as contentclass_identifier,
                   ezcc.id as contentclass_id,
                   eztree.node_id as node_id,
                   eztree.parent_node_id as parent_node_id
             FROM ezcontentobject_link link,
                   ezcontentobject ezco,
                   ezcontentclass ezcc,
                   ezcontentobject_tree eztree
             WHERE link.from_contentobject_id = ezco.id AND
                   link.from_contentobject_version = ezco.current_version AND
                   link.to_contentobject_id=$objectId AND
                   link.contentclassattribute_id=$identifier AND
                   ezco.id = eztree.contentobject_id AND
                   eztree.node_id = eztree.main_node_id AND
                   ezcc.id = ezco.contentclass_id AND
                   ezcc.version=0");
    unset($db);
    if ( $returnArray ) foreach( $returnArray as $el ) $idArray[] = $el['contentobject_id'];
}
else
{
    $content = $dataMap[$attributeIdentifier]->content();
    $idArray = array();

    for ( $i = 0, $l = count( $content['relation_list'] ); $i < $l; ++$i )
    {
        $returnArray[] = $content['relation_list'][$i];
        $idArray[]     = $content['relation_list'][$i]['contentobject_id'];
    }
    if ( $idArray && $list = eZContentObject::fetchIDArray( $idArray ) )
    {
        for ( $i = 0, $l = count( $returnArray ); $i < $l; ++$i )
        {
            if ( isset($list[$returnArray[$i]['contentobject_id']]) );
                $returnArray[$i]['name'] = $list[$returnArray[$i]['contentobject_id']]->attribute('name');
        }
    }
}

if ( $idArray && $relatedToId)
{
    $db = eZDB::instance();
    $idArray = implode(',', $idArray);
    $temp = $db->arrayQuery( "SELECT link.from_contentobject_id as id
                             FROM ezcontentobject_link AS link, 
                                    ezcontentobject AS ezco
                             WHERE link.from_contentobject_id = ezco.id AND
                                   link.from_contentobject_version = ezco.current_version AND
                                   link.to_contentobject_id=$relatedToId AND
                                   link.from_contentobject_id in ( $idArray );");
    unset($db);
    for ( $i = 0, $l = count( $returnArray ); $i < $l; ++$i )
    {
        $returnArray[$i]['is_related'] = false;
        $returnArray[$i]['related_to_id'] = $relatedToId;
        if ( $temp )
        {
            foreach ( $temp as $rel )
            {
                if ( $returnArray[$i]['contentobject_id'] == $rel['id'] )
                {
                    $returnArray[$i]['is_related'] = true;
                    break;
                }
            }
        }
    }
    
}

echo eZAjaxContent::jsonEncode( $returnArray );
eZDB::checkTransactionCounter();
eZExecution::cleanExit();

?>