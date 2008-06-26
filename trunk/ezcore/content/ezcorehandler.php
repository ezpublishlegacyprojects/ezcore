<?php
//
// Created on: <11-Apr-2008 00:00:00 ar>
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
 * hides nodes that are supposed to be unhided in the future,
 * and change publish time on nodes that have a unhide time in the past
 * (basically mimics 'wait untill date' publish workflow, but uses hide/ unhide instead)
 * delayed hiding / unhiding is done by cronjob ( ezcore/cronjobs/hide_unhide.php )
 */

class ezcoreHandler extends eZContentObjectEditHandler
{
    function fetchInput( $http, &$module, &$class, $object, &$version, $contentObjectAttributes, $editVersion, $editLanguage, $fromLanguage  )
    {
    }
 
    static function storeActionList()
    {
        return array();
    }
 
    function publish( $contentObjectID, $contentObjectVersion )
    {
        // fetch object
        $object = eZContentObject::fetch( $contentObjectID );

        $currrentDate         = time();
        $ini                  = eZINI::instance( 'content.ini' );
        $rootNodeIDList       = $ini->hasVariable( 'HideSettings','RootNodeList' ) ? $ini->variable( 'HideSettings','RootNodeList' ) : 2;
        $hideAttributeArray   = $ini->hasVariable( 'HideSettings', 'HideDateAttributeList' ) ? $ini->variable( 'HideSettings', 'HideDateAttributeList' ) : array();
        $unhideAttributeArray = $ini->hasVariable( 'HideSettings', 'UnhideDateAttributeList' ) ? $ini->variable( 'HideSettings', 'UnhideDateAttributeList' ) : array();
        $unhideModifyPublishDate = $ini->hasVariable( 'HideSettings', 'UnhideModifyPublishDate' ) && $ini->variable( 'HideSettings', 'UnhideModifyPublishDate' ) === 'enabled';
        
        $classIdentifier         = $object->attribute('class_identifier');
        $classID                 = $object->attribute('contentclass_id');
        $hideDateAttributeName   = false;
        $unhideDateAttributeName = false;

        if ( isset( $hideAttributeArray[$classIdentifier] ) )
            $hideDateAttributeName = $hideAttributeArray[$classIdentifier];

        if ( isset( $unhideAttributeArray[$classIdentifier] ) )
            $unhideDateAttributeName = $unhideAttributeArray[$classIdentifier];
  
        if ( !$hideDateAttributeName && !$unhideDateAttributeName )
        {
            return true;
        }
    
        $dataMap             = $object->attribute( 'data_map' );
        $hideDateAttribute   = isset( $dataMap[$hideDateAttributeName] ) ? $dataMap[$hideDateAttributeName] : null;
        $unhideDateAttribute = isset( $dataMap[$unhideDateAttributeName] ) ? $dataMap[$unhideDateAttributeName]: null;

        if ( ($hideDateAttribute === null || !$hideDateAttribute->hasContent()) &&
             ($unhideDateAttribute === null || !$unhideDateAttribute->hasContent()) )
        {
            return true;
        }

        $hideDate   = 0;
        $unhideDate = 0;

        if ( is_object( $hideDateAttribute ) && $hideDateAttribute->hasContent() )
        {
            $hideDateAttributeContent = $hideDateAttribute->content();
            $hideDate = $hideDateAttributeContent->attribute( 'timestamp' );
        }

        if (  is_object( $unhideDateAttribute ) && $unhideDateAttribute->hasContent() )
        {
            $unhideDateAttributeContent = $unhideDateAttribute->content();
            $unhideDate = $unhideDateAttributeContent->attribute( 'timestamp' );
        }

        if ( $hideDate > 0 && $hideDate < $currrentDate  )
        {
            // if hide date is passed, hide nodes
            eZDebug::writeNotice( 'Hiding nodes for object: ' . $object->attribute('id') , 'ezcoreHandler::publish' );
            $nodes = $object->attribute('assigned_nodes');
            foreach ( $nodes as $node )
            {
                // TODO: check with $rootNodeIDList
                if ( !$node->attribute( 'is_hidden' ) )
                {
                    eZContentObjectTreeNode::hideSubTree( $node );
                }
            }
        }
        else if ( $unhideDate > 0 && $unhideDate < $currrentDate  )
        {
            if ( $object->attribute('published') == $unhideDate )
            {
                // Skipp if object publish date is the same as attribute publish date
                return true;
            }
            // if unhide date is passed, unhide nodes and set published timestamp
            eZDebug::writeNotice( 'Unhiding nodes for object: ' . $object->attribute('id') , 'ezcoreHandler::publish' );
            if ( $unhideModifyPublishDate )
            {
                $object->setAttribute('published', $unhideDate );
                $object->store();
            }
            $nodes = $object->attribute('assigned_nodes');
            foreach ( $nodes as $node )
            {
                // TODO: check with $rootNodeIDList
                if ( $node->attribute( 'is_hidden' ) )
                {
                    eZContentObjectTreeNode::unhideSubTree( $node );
                }
            }
        }
        else if ( $unhideDate > $currrentDate  )
        {
            // if unhide date is NOT passed, hide nodes
            eZDebug::writeNotice( 'Hiding nodes (waiting for cronjob to unhide them later) for object: ' . $object->attribute('id') , 'ezcoreHandler::publish' );
            $nodes = $object->attribute('assigned_nodes');
            foreach ( $nodes as $node )
            {
                // TODO: check with $rootNodeIDList
                if ( !$node->attribute( 'is_hidden' ) )
                {
                    eZContentObjectTreeNode::hideSubTree( $node );
                }
            }
        }
        else if ( $unhideDate === 0 || $hideDate === 0 )
        {
            $nodes = $object->attribute('assigned_nodes');
            foreach ( $nodes as $node )
            {
                // TODO: check with $rootNodeIDList
                if ( $node->attribute( 'is_hidden' ) )
                {
                    eZContentObjectTreeNode::unhideSubTree( $node );
                }
            }
        }
        
        
        return true;
    }
}

?>