<?php
//
// Definition of NodeHasChildrenFilter class
//
// Created on: <10-Oct-2007 12:42:08 ar>
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

class ObjectIdsFilter
{
    function ObjectIdsFilter()
    {
    }

    function createSqlParts( $params )
    {
        /*
         * Filter out/in objects from fetch based on object id's
         * 
         * param 1: id or array of id's
         * param 2: optional, if set to true only id's in param 1 is returned
         *         default : false
         * 
         * Full example for fetching other articles in same folder expect the one currently viewed:
         * 
         * {def $array_of_object_ids = array( $node.contentobject_id )
         *      $other_articles = fetch( 'content', 'tree', hash(
                                      'parent_node_id', $node.parent_node_id,
                                      'limit', 3,
                                      'sort_by', array( 'published', false() ),
                                      'class_filter_type', 'include',
                                      'class_filter_array', array( 'articles' ),
                                      'extended_attribute_filter', hash( 'id', 'ObjectIdsFilter', 'params', array( $array_of_object_ids ) )
                                      ) )}
         * 
         */
        $idList = false;
        $optIn = 'NOT ';

        if ( isset( $params[0] ) && is_numeric( $params[0] ) )
        {
            $idList = array( $params[0] );
        }
        else if ( isset( $params[0] ) && is_array( $params[0] ) )
        {
            $idList = $params[0];
        }

        if( isset( $params[1] ) )
        {
            $optIn = $params[1] ? '' : 'NOT ';
        }

        if ( $idList )
        {
            $sqlJoins = 'ezcontentobject.id ' . $optIn . 'IN (' . implode( ', ', $idList ) . ') AND';
        }
        else
        {
            $sqlJoins = '';
        }

        return array('tables' => '', 'joins' => $sqlJoins, 'columns' => '');
    }
}
?>