<?php
//
// Created on: <11-Apr-08 16:00:52 ar>
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

/*! \file hide_unhide.php
 *  Takes care of delayed hiding and unhiding nodes based on content.ini,
 *  ezcore/content/ezcorehandler.php takes care of actions on publish.
 *  PS: do not run hide.php togheter with this script, hide action is handeled
 *      by this script
*/

//include_once( "kernel/classes/ezcontentobjecttreenode.php" );
//include_once( "lib/ezutils/classes/ezini.php" );

$ini = eZINI::instance( 'content.ini' );
$rootNodeIDList = $ini->variable( 'HideSettings','RootNodeList' );
$hideAttributeArray = $ini->variable( 'HideSettings', 'HideDateAttributeList' );
$unhideAttributeArray = $ini->variable( 'HideSettings', 'UnhideDateAttributeList' );
$unhideModifyPublishDate = $ini->hasVariable( 'HideSettings', 'UnhideModifyPublishDate' ) && $ini->variable( 'HideSettings', 'UnhideModifyPublishDate' ) === 'enabled';
$fetchClasses = array_merge( array_keys( $hideAttributeArray ), array_keys( $unhideAttributeArray ) );

$currrentDate = time();

$offset = 0;
$limit = 20;

foreach( $rootNodeIDList as $nodeID )
{
    $rootNode = eZContentObjectTreeNode::fetch( $nodeID );

    while( true )
    {
        $nodeArray = $rootNode->subTree( array( 'ClassFilterType' => 'include',
                                                'ClassFilterArray' => $fetchClasses,
                                                'IgnoreVisibility' => true,
                                                'Offset' => $offset,
                                                'Limit' => $limit ) );
        if ( !$nodeArray ||
             count( $nodeArray ) == 0 )
        {
            break;
        }

        $offset += $limit;

        foreach ( $nodeArray as $node )
        {
            $dataMap = $node->attribute( 'data_map' );
            $hideDateAttributeName = false;
            $unhideDateAttributeName = false;

            if ( isset( $hideAttributeArray[$node->attribute( 'class_identifier' )] ) )
                $hideDateAttributeName = $hideAttributeArray[$node->attribute( 'class_identifier' )];

            if ( isset( $unhideAttributeArray[$node->attribute( 'class_identifier' )] ) )
                $unhideDateAttributeName = $unhideAttributeArray[$node->attribute( 'class_identifier' )];

            if ( !$hideDateAttributeName && !$unhideDateAttributeName )
            {
                continue;
            }

            $hideDateAttribute = isset( $dataMap[$hideDateAttributeName] ) ? $dataMap[$hideDateAttributeName] : null;
            $unhideDateAttribute = isset( $dataMap[$unhideDateAttributeName] ) ? $dataMap[$unhideDateAttributeName] : null;

            if ( ($hideDateAttribute === null || !$hideDateAttribute->hasContent()) &&
                 ($unhideDateAttribute === null || !$unhideDateAttribute->hasContent()) )
            {
                continue;
            }

            $hideDate   = 0;
            $unhideDate = 0;

            if ( is_object( $hideDateAttribute ) && $hideDateAttribute->hasContent() )
            {
                $hideDateAttributeContent = $hideDateAttribute->content();
                $hideDate = $hideDateAttributeContent->attribute( 'timestamp' );
            }

            if ( is_object( $unhideDateAttribute ) && $unhideDateAttribute->hasContent() )
            {
                $unhideDateAttributeContent = $unhideDateAttribute->content();
                $unhideDate = $unhideDateAttributeContent->attribute( 'timestamp' );
            }

            if ( $hideDate > 0 && $hideDate < $currrentDate )
            {
                // if hide date is passed, hide node
                if ( $node->attribute( 'is_hidden' ) )
                {
                    // node is already hidden, do nothing
                    continue;
                }
                eZContentObjectTreeNode::hideSubTree( $node );
                if ( !$isQuiet )
                {
                    $cli->output( 'Hiding node : ' . $node->attribute( 'node_id' ) );
                }
            }
            else if ( $unhideDate > 0 && $unhideDate < $currrentDate )
            {
                // if unhide date is passed, unhide node and set published timestamp
                if ( !$node->attribute( 'is_hidden' ) )
                {
                    // node is already visiable, do nothing
                    continue;
                }
                $object = $node->attribute('object');
                if ( $object->attribute('published') == $unhideDate )
                {
                    // Skipp if object publish date is the same as attribute publish date
                    continue;
                }
                eZContentObjectTreeNode::unhideSubTree( $node );
                if ( $unhideModifyPublishDate )
                {
                    $object->setAttribute('published', $unhideDate );
                    $object->store();
                }
                if ( !$isQuiet )
                {
                    $cli->output( 'Unhiding node : ' . $node->attribute( 'node_id' ) );
                }
            }
            else
            {
                if ( !$isQuiet )
                {
                    $cli->output( 'Ignoring node : ' . $node->attribute( 'node_id' ) . ' ' . $unhideDate . '>' . $currrentDate . '<' . $hideDate );
                }
            }
        }
    }
}


?>
