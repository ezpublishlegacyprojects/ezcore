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
$parentNodeId        = $Params['parent_node_id'];
$classIdentifier     = trim( $Params['class_identifier'] );
$relatedToId         = $Params['related_to_id'];

$user          = eZUser::currentUser();
$userID        = $user->attribute( 'contentobject_id' );
$userObject    = $user->attribute('contentobject');
$userNodeID    = $userObject->attribute('main_node_id');
$relatedToId   = (int) $relatedToId == 'user_id' ? $userID : $relatedToId;
$parentNodeId  = (int) $parentNodeId == 'user_node_id' ? $userNodeID : $parentNodeId;

$nodeObject = $parentNodeId ? eZContentObjectTreeNode::fetch( $parentNodeId ) : false;

if ( !$user->attribute('is_logged_in') )
{
    echo "You need to be logged in to be allowed to relate content!<br \/><a href='JavaScript:history.go(-1)'>Go Back</a>";
    eZExecution::cleanExit();
}
else if ( !$nodeObject || !$nodeObject->checkAccess('read') )
{
    echo 'You don\'t have access to read thsi object!<br \/><a href="JavaScript:history.go(-1)">Go Back</a>';
    eZExecution::cleanExit();
}

$returnArray = array();
$idArray     = array();
$params      = array('AsObject' => false,
                     'SortBy' => $nodeObject->sortArray(),
                     'Depth' => 2,
                     'DepthOperator' => 'lt'
);

if ( $classIdentifier )
{
    $params['ClassFilterType'] = 'include';
    $params['ClassFilterArray'] = array( $classIdentifier );
}

$children = $nodeObject->subTree( $params );

for ( $i = 0, $l = count( $children ); $i < $l; ++$i )
{
    $returnArray[] = $children[$i];
    $idArray[]     = $children[$i]['contentobject_id'];
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