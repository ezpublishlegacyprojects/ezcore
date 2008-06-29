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

class NodeHasChildrenFilter
{
    function NodeHasChildrenFilter()
    {
    }

    function createSqlParts( $params )
    {
        /*
         * Filtering nodes with a certain amount of children
         * 
         * By default it will only return nodes that have more then 0 children
         * but this can be customized to you need.
         * 
         * param 1: amount of children, default: 0
         * param 2: comparison clause, example: '!=', '=', '<=', '>=', '<', '>', default is '!='
         * 
         * Full example for fetching nodes that don't have children nodes:
         * ( in this case a 'is question anweared?' functionality )
         * 
         * {def $qa_list = fetch( 'content', 'tree', hash(
                                      'parent_node_id', 1503,
                                      'limit', 3,
                                      'sort_by', array( 'published', false() ),
                                      'class_filter_type', 'include',
                                      'class_filter_array', array( 'forum_topic' ),
                                      'extended_attribute_filter', hash( 'id', 'NodeHasChildrenFilter', 'params', array( 0, '=' ) )
                                      ) )}
         * 
         */
        $count = 0;
        $clause = '!=';

        if( isset( $params[0] ) && is_numeric( $params[0] ) )
        {
            $count = (int) $params[0];
        }

        if( isset( $params[1] ) && is_string( $params[1] ) )
        {
            $clause = $params[1];
        }

        $sqlJoins = "$count $clause ( SELECT
                                   count(*)
                                 FROM
                                   ezcontentobject_tree ezcontentobject_tree_2
                                 WHERE
                                   ezcontentobject_tree_2.parent_node_id = ezcontentobject_tree.node_id) AND";
        return array('tables' => '', 'joins' => $sqlJoins, 'columns' => '');
    }
}
?>