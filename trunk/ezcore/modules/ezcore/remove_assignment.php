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
// VIEW:		remove_assignment
// PARAMS:		'ParentNodeId'
// OUTPUT:		text
// DESCRIPTION: Remove node assignment for several nodes
//              Either by node ids or by object id and parent node
//
// ## END ABOUT TEXT, SPECS AND DESCRIPTION  ##
//

include_once( 'kernel/classes/ezcontentcachemanager.php' );
include_once( 'kernel/classes/ezcontentobjecttreenode.php' );
include_once( 'kernel/classes/datatypes/ezuser/ezuser.php' );



$parentNodeID = 0;
$Module              = $Params['Module'];
$user                = eZUser::currentUser();
$error               = false;
$http                = eZHTTPTool::instance();
$deleteIDArray       = false;


if ( isset( $Params['parent_node_id'] ) || is_numeric($Params['parent_node_id']) )
{
    $parentNodeID = $Params['parent_node_id'];
}
else if ( $http->hasPostVariable( 'ParentNodeID' ) )
{
    $parentNodeID = $http->postVariable( 'ParentNodeID' );
}

if ( $parentNodeID == 'user_node_id' )
{
    $userObject    = $user->attribute('contentobject');
    $parentNodeID  = (int) $userObject->attribute('main_node_id');
}
else
{
    $parentNodeID = (int) $parentNodeID;
}

if ( $http->hasPostVariable( 'DeleteIDArray' ) )
{
    $deleteIDArray = $http->postVariable( 'DeleteIDArray' );
}
else if ( isset( $Params['delete_id'] ) || is_numeric($Params['delete_id']) )
{
    $deleteIDArray = array( $Params['delete_id'] );
}


$parentObject = $parentNodeID ? eZContentObject::fetchByNodeId( $parentNodeID ): false;

//DeleteIDArray[]  eznodeassignment.php
if ( !is_array( $deleteIDArray ) or  count( $deleteIDArray ) === 0 )
{
    $error = 'Missing list of object id\'s to remove assignment from!';
}
else if ( $parentObject && 
     $user && 
     $parentObject->attribute( 'owner_id') == $user->attribute( 'contentobject_id' )  )
{
    foreach ( $deleteIDArray as $id )
    {
        $object = eZContentObject::fetch( $id );
        $nodes = $object->attribute('assigned_nodes');
        $nodesLeft = 0;
        foreach ( $nodes as $node )
        {
            if ( $node->attribute('parent_node_id') == $parentNodeID )
            {
                if ( $node->attribute('can_remove') )
                {
                    $node->removeThis();
                }
                else
                {
                    $error = 'You don\'t have sufficient access to remove node: '. $node->attribute('node_id');
                }
            }
            else ++$nodesLeft;
        }
        if (  !$nodesLeft && !$error && $object->attribute('can_remove') )
        {
            $object->removeThis();
            echo 'Object Removed!';
        }
        eZContentCacheManager::clearContentCache( $id, true, false );
    }
}
else
{
    $error = 'You don\'t have sufficient access to this object, only the owner has access to edit the object directly!';
}

if ( $error )
{
    echo $error;
}

if ( $http->hasPostVariable( 'RedirectURIAfterPublish' ) )
{
    return $Module->redirectTo( ( $http->postVariable( 'RedirectURIAfterPublish' ) ) ? $http->postVariable( 'RedirectURIAfterPublish' ) : $parentNode->attribute( 'url_alias' ) );
}
else if ( $http->hasSessionVariable( "LastAccessesURI" ) )
{
    return $Module->redirectTo( $http->sessionVariable( "LastAccessesURI" ) );
}
else if ( !$error )
{
    echo 'OK, you have successfully nuked this node assignment!';
}

echo '<br /><a href="/">Return to website</a>';
eZExecution::cleanExit();

