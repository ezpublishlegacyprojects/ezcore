<?php
//
// Created on: <30-Jul-2007 00:00:00 ar>
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
// MODULE:		eZCore
// VIEW:		publish
// PARAMS:		'ParentNodeID', 'ClassIdentifier', 'LanguageCode'
// OUTPUT:		xhtml
// DESCRIPTION: Script for generating content directly
//              based on post input
//              Currently only ezstring and eztext
//
// ## END ABOUT TEXT, SPECS AND DESCRIPTION  ##
//

include_once( 'kernel/common/template.php' );
include_once( 'kernel/classes/ezsection.php' );
include_once( 'kernel/classes/ezcontentclass.php' );
include_once( 'kernel/classes/ezcontentlanguage.php' );
include_once( 'kernel/classes/eznodeviewfunctions.php' );
include_once( 'kernel/classes/ezcontentcachemanager.php' );
include_once( 'kernel/classes/ezcontentobjecttreenode.php' );
include_once( 'kernel/classes/datatypes/ezuser/ezuser.php' );
include_once( 'lib/ezutils/classes/ezoperationhandler.php' );


function generateNodeViewLite($node, $object, $parentClass = false, $languageCode = false, $viewMode = 'line' )
{
       
        // this is a lite version of eZNodeviewfunctions::generateNodeView
        // since view cache is stripped, it should also work on cluster
        
        eZSection::setGlobalID( $object->attribute( 'section_id' ) );

        $tpl = templateInit();
        $navigationPartIdentifier = null;
        $section = eZSection::fetch( $object->attribute( 'section_id' ) );
        if ( $section )
            $navigationPartIdentifier = $section->attribute( 'navigation_part_identifier' );


        $keyArray = array( array( 'object', $object->attribute( 'id' ) ),
                           array( 'node', $node->attribute( 'node_id' ) ),
                           array( 'parent_node', $node->attribute( 'parent_node_id' ) ),
                           array( 'class', $object->attribute( 'contentclass_id' ) ),
                           array( 'class_identifier', $node->attribute( 'class_identifier' ) ),
                           array( 'view_offset', 0 ),
                           array( 'viewmode', $viewMode ),
                           array( 'navigation_part_identifier', $navigationPartIdentifier ),
                           array( 'depth', $node->attribute( 'depth' ) ),
                           array( 'url_alias', $node->attribute( 'url_alias' ) ),
                           array( 'class_group', $object->attribute( 'match_ingroup_id_list' ) ) );

        $parentClassID = false;
        $parentClassIdentifier = false;
        if ( is_object( $parentClass ) )
        {
            $parentClassID = $parentClass->attribute( 'id' );
            $parentClassIdentifier = $parentClass->attribute( 'identifier' );

            $keyArray[] = array( 'parent_class', $parentClassID );
            $keyArray[] = array( 'parent_class_identifier', $parentClassIdentifier );
        }

        $res = eZTemplateDesignResource::instance();
        $res->setKeys( $keyArray );

        if ( $languageCode )
        {
            $oldLanguageCode = $node->currentLanguage();
            $node->setCurrentLanguage( $languageCode );
        }

        $tpl->setVariable( 'node', $node );
        $tpl->setVariable( 'viewmode', '$viewMode' );
        $tpl->setVariable( 'language_code', $languageCode );
        $tpl->setVariable( 'view_parameters', array( 'offset' => 0, 'year' => false,'month' => false,'day' => false ) );
        $tpl->setVariable( 'collection_attributes', false );
        $tpl->setVariable( 'validation', false );
        $tpl->setVariable( 'persistent_variable', false );

        $Result = $tpl->fetch( 'design:node/view/' . $viewMode . '.tpl' );

        if ( $languageCode )
        {
            $node->setCurrentLanguage( $oldLanguageCode );
        }
        return $Result;
}

$user = eZUser::currentUser();
$parentNodeID = (int) $Params['parent_node_id'];
$http = eZHTTPTool::instance();

if ( $Params['parent_node_id'] === 'user_node_id' )
{
    $userObject = $user->attribute('contentobject');
    $parentNodeID = $userObject->attribute('main_node_id');
}
else if ( $http->hasPostVariable( 'ContentNodeID' ) && (int) $http->postVariable( 'ContentNodeID' ) )
{
    $parentNodeID = (int) $http->postVariable( 'ContentNodeID' );
}

if ( !$parentNodeID )
{
    echo "No ParentNodeID!";
    eZExecution::cleanExit();
}

if ( !isset( $Params['class_identifier'] ) || !$Params['class_identifier'])
{
    echo "No ClassIdentifier!";
    eZExecution::cleanExit();
}

if ( !isset( $Params['language_code'] ) || !$Params['language_code'])
{
    echo "No LanguageCode!";
    eZExecution::cleanExit();
}

$Module = $Params['Module'];
$classIdentifier = trim( $Params['class_identifier'] );
$languageCode = trim( $Params['language_code'] );
$error = false;
$isPublish = true;
$parentNode = eZContentObjectTreeNode::fetch( $parentNodeID );
$class = eZContentClass::fetchByIdentifier( $classIdentifier );
$contentClassID = $class->attribute( 'id' );



if (!$parentNode)
{
    echo "No ParentNode!";
    eZExecution::cleanExit();
}

// is this edit of exiting object??
if ( $http->hasPostVariable( 'ContentObjectId' ) &&
     is_numeric($http->postVariable( 'ContentObjectId' )) &&
     (int) $http->postVariable( 'ContentObjectId' ) )
{
    echo 'You are editing existing object with id: ' . $http->postVariable( 'ContentObjectId' );
    $contentObject = eZContentObject::fetch( $http->postVariable( 'ContentObjectId' ) );
    $isPublish = false;
    // make sure object is child of $parentNode and that we have edit rights to it
    $contentNode = $contentObject ? $contentObject->attribute('main_node') : false;
    if ( !$contentNode ||
         $contentNode->attribute('parent_node_id') != $parentNodeID ||
         !$contentObject->attribute('can_edit'))
    {
        echo "<br />Did not find the object you are trying to edit or it is not child of the parent node you specified!";
        eZExecution::cleanExit();
    }
}


$parentContentObject = $parentNode->attribute( 'object' );
$parentClass = $parentContentObject->contentClass();

if ( $parentContentObject->checkAccess( 'create', $contentClassID,  $parentClass->attribute( 'id' ), false, $languageCode ) == '1' )
{
    
    $userID = $user->attribute( 'contentobject_id' );
    // Set section of the newly created object to the section's value of it's parent object
    $sectionID = $parentContentObject->attribute( 'section_id' );

    if ( is_object( $class ) )
    {
        $db = eZDB::instance();
        $db->begin();
        
        // only create
        if ( !isset( $contentObject ) )
        {
            $contentObject = $class->instantiateIn( $languageCode, $userID, $sectionID, false, eZContentObjectVersion::STATUS_INTERNAL_DRAFT );
            $nodeAssignment = eZNodeAssignment::create( array( 'contentobject_id' => $contentObject->attribute( 'id' ),
                                                               'contentobject_version' => $contentObject->attribute( 'current_version' ),
                                                               'parent_node' => $parentNode->attribute( 'node_id' ),
                                                               'is_main' => 1,
                                                               'sort_field' => $class->attribute( 'sort_field' ),
                                                               'sort_order' => $class->attribute( 'sort_order' ) ) );
            if ( $http->hasPostVariable( 'AssignmentRemoteID' ) )
            {
                $nodeAssignment->setAttribute( 'remote_id', $http->postVariable( 'AssignmentRemoteID' ) );
            }
            $nodeAssignment->store();
        }
        
        $curVersionObject  = $contentObject->attribute( 'current' );
        $objectDataMap = $curVersionObject->attribute('data_map');
        $addedData = false;
        
        foreach ( array_keys( $objectDataMap ) as $key )
        {
            //post pattern: ContentObjectAttribute_attribute-identifier
            $base = 'ContentObjectAttribute_'. $key;
            if ( $http->hasPostVariable( $base ) && trim( $http->postVariable( $base ) ) !== '' )
            {
                switch ( $objectDataMap[$key]->attribute( 'data_type_string' ) )
                {
                    // TODO: Validate input
                    case 'eztext':
                    case 'ezstring':
                        echo '<br />Storing text data in attribute:' . $key;
                        $objectDataMap[$key]->setAttribute("data_text", trim( $http->postVariable( $base ) ) );
                        $objectDataMap[$key]->store();
                        $addedData = true;
                        break;
                    case 'ezfloat':
                        echo '<br />Storing float data in attribute:' . $key;
                        $objectDataMap[$key]->setAttribute("data_float", (float) str_replace(',', '.', $http->postVariable( $base ) ) );
                        $objectDataMap[$key]->store();
                        $addedData = true;
                        break;
                    case 'ezinteger':
                    case 'ezboolean':
                        echo '<br />Storing int/boolean data in attribute:' . $key;
                        $objectDataMap[$key]->setAttribute("data_int", (int) $http->postVariable( $base ) );
                        $objectDataMap[$key]->store();
                        $addedData = true;
                        break;
                }
            }
            else
            {
                echo '<br />Did not find any post data for: '. $base;                
            }
        }
        
        if ( !$addedData )
        {
            eZDebug::writeError( 'No data inserted, transaction should be roled back at this point!', 'ezcore/publish' );
        }
        else
        {
            eZDebug::writeNotice( 'Data should have been inserted sucsessfully!', 'ezcore/publish' );
        }

        $additionalCacheNodeList = array();
        if ( $isPublish && $http->hasPostVariable( 'AdditionalNodeLocation' ) && $http->postVariable( 'AdditionalNodeLocation' ) )
        {
            $additinalNodes = $http->postVariable( 'AdditionalNodeLocation' );
            if ( !is_array( $additinalNodes ) ) $additinalNodes = array( $additinalNodes );
            foreach ( $additinalNodes as $aId )
            {
                $aId = (int) $aId;
                $aObject = $aId ? eZContentObject::fetchByNodeID( $aId ) : false;
                $parentClass = $aObject ? $aObject->contentClass() : false;
                if ( $aObject && $aObject->checkAccess( 'create', $contentClassID,  $parentClass->attribute( 'id' ), false, $languageCode ) == '1' )
                {
                    $nodeAssignment = eZNodeAssignment::create( array( 'contentobject_id' => $contentObject->attribute( 'id' ),
                                               'contentobject_version' => $contentObject->attribute( 'current_version' ),
                                               'parent_node' => $aId,
                                               'is_main' => 0,
                                               'sort_field' => $class->attribute( 'sort_field' ),
                                               'sort_order' => $class->attribute( 'sort_order' ) ) );
                    if ( $http->hasPostVariable( 'AssignmentRemoteID' ) )
                    {
                        $nodeAssignment->setAttribute( 'remote_id', $http->postVariable( 'AssignmentRemoteID' ) );
                    }
                    $nodeAssignment->store();
                    $additionalCacheNodeList[] = $aId;
                }
                else if ( $aObject )
                {
                    echo '<br />You don\'t have sufficient access to create node with class: ' . $contentClassID . ' on node '. $aId;
                }
                else
                {
                    echo '<br />You don\'t have sufficient access to read node '. $aId;
                }
            }
        }

        if ( $isPublish && $http->hasPostVariable( 'AdditionalNodeLocationObjectByAttribute' ) )
        {
            $key = $http->postVariable( 'AdditionalNodeLocationObjectByAttribute' );
            $base = 'ContentObjectAttribute_'. $key;
            if ( $http->hasPostVariable( $base ) && $http->postVariable( $base ) )
            {
                $additinalNodes = $http->postVariable( $base );
                if ( !is_array( $additinalNodes ) ) $additinalNodes = array( $additinalNodes );
                foreach ( $additinalNodes as $aId )
                {
                    $aId = (int) $aId;
                    $aObject = $aId ? eZContentObject::fetch( $aId ) : false;
                    $parentClass = $aObject ? $aObject->contentClass() : false;
                    if ( $aObject && $aObject->checkAccess( 'create', $contentClassID,  $parentClass->attribute( 'id' ), false, $languageCode ) == '1' )
                    {
                        $nodeAssignment = eZNodeAssignment::create( array( 'contentobject_id' => $contentObject->attribute( 'id' ),
                                                   'contentobject_version' => $contentObject->attribute( 'current_version' ),
                                                   'parent_node' => $aObject->attribute( 'main_node_id' ),
                                                   'is_main' => 0,
                                                   'sort_field' => $class->attribute( 'sort_field' ),
                                                   'sort_order' => $class->attribute( 'sort_order' ) ) );
                        if ( $http->hasPostVariable( 'AssignmentRemoteID' ) )
                        {
                            $nodeAssignment->setAttribute( 'remote_id', $http->postVariable( 'AssignmentRemoteID' ) );
                        }
                        $nodeAssignment->store();
                        $additionalCacheNodeList[] = $aObject->attribute( 'main_node_id' );
                    }
                    else if ( $aObject )
                    {
                        echo '<br />You don\'t have sufficient access to create node with class: ' . $contentClassID . ' on node '. $aId;
                    }
                    else
                    {
                        echo '<br />You don\'t have sufficient access to read node '. $aId;
                    }
                }
            }
        }
        eZDebug::writeNotice( 'Publishing object!', 'ezcore/publish' );
        // publish the newly created object
        eZOperationHandler::execute( 'content', 'publish', array( 'object_id' => $contentObject->attribute( 'id' ),
                                                                  'version'   => $contentObject->attribute( 'current_version' ) ) );
        
        eZDebug::writeNotice( 'Trying to commit changes!', 'ezcore/publish' );
        $db->commit();

        if ( $http->hasPostVariable( 'AdditionalNodeCacheClean' ) )
        {
            $additionalNode = (int) $http->postVariable( 'AdditionalNodeCacheClean' );
            if ( is_array( $additionalNode) ) $additionalCacheNodeList = array_merge($additionalCacheNodeList, $additionalNode );
            else $additionalCacheNodeList[] = $additionalNode;
        }
        eZDebug::writeNotice( 'Clearing content cache!', 'ezcore/publish' );
        eZContentCacheManager::clearContentCache( $contentObject->attribute( 'id' ), true, ($additionalCacheNodeList ? $additionalCacheNodeList : false));

        // redirect if this post is set, if it is empty(but set) we redirect to parentNode
        if ( $http->hasPostVariable( 'RedirectURIAfterPublish' ) )
        {
            return $Module->redirectTo( ( $http->postVariable( 'RedirectURIAfterPublish' ) ) ? $http->postVariable( 'RedirectURIAfterPublish' ) : $parentNode->attribute( 'url_alias' ) );
        }
        
        // else generate line view of the newly created object
        echo generateNodeViewLite($contentObject->attribute('main_node'), $contentObject, $parentClass, $languageCode, 'line' );
        eZDB::checkTransactionCounter();
        eZExecution::cleanExit();
    }
}

echo "You do not have sufficient rights to create a '" . $classIdentifier . "' on this page!";
echo '<br /><a href="/">Return to website</a>';
eZDB::checkTransactionCounter();
eZExecution::cleanExit();

