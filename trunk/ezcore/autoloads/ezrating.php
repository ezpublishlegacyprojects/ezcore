<?php
//
// Definition of eZContentRatingclass
//
// Created on: <17-Sep-2007 12:42:08 ar>
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
  Template operator for getting user rating of content
*/


class eZContentRating
{
    function eZContentRating()
    {
    }

    function operatorList()
    {
        return array( 'ezrating', 'fetch_by_rating');
    }

    function namedParameterPerOperator()
    {
        return true;
    }

    function namedParameterList()
    {
        return array( 'ezrating' => array( 'params' => array( 'type' => 'array',
                                              'required' => true,
                                              'default' => array() )),
                      'fetch_by_rating' => array( 'params' => array( 'type' => 'array',
                                              'required' => false,
                                              'default' => array() )));
                                              
    }

    function modify( &$tpl, &$operatorName, &$operatorParameters, &$rootNamespace, &$currentNamespace, &$operatorValue, &$namedParameters )
    {
        switch ( $operatorName )
        {
            case 'ezrating':
            {
                $ret = false;
                $params = $namedParameters['params'];
                $where = array();
                
                if ( isset( $params['node_id'] ))
                {
                    $obj = eZContentObject::fetchByNodeID( $params['node_id'], false );
                    if ( isset( $obj['id'] ) ) $params['contentobject_id'] = $obj['id'];
                }

                if ( isset( $params['contentobject_id'] ))
                {
                    $where[] = eZContentRating::getRatingByObjectSql( $params['contentobject_id'] );
                }

                if ( isset( $params['user_id'] ))
                {
                    $where[] = eZContentRating::getRatingByUserSql( $params['user_id'] );
                }

                if ( isset( $params['owner_id'] ))
                {
                    $where[] = eZContentRating::getRatingByOwnerSql( $params['owner_id'] );
                }
                $ret = eZContentRating::getRatingWhere( $where );
            } break;
            case 'fetch_by_rating':
            {
                $ret = self::fetchNodeByRating( $namedParameters['params'] );
            } break;
        }

        $operatorValue = $ret;

    }
    
    static function fetchNodeByRating( $params )
    {
         /*
         * Fetch top/bottom content (nodes) by rating++
         * 
         * Parms:
         * sort_by (def: array(array('rate', false ),array('rate_count', false)) controlls sorting
         *     possible sortings are rate_count, rate, object_count, view_count, published and modified 
         *     possible direction are true (ASC) and false (DESC)
         *     Note: 'object_count' makes only sense when combined with group_by_owner
         * class_identifier (def: empty) limit fetch to a specific classes
         * offset  (def: 0) set offset on returned list
         * limit (def: 10) limit number of objects returned
         * group_by_owner (def: false) will give you result grouped by owner instead
         *                and the node of the owner (user object) is
         *                fetched intead
         * main_parent_node_id (def: none) Limit result based on parent main node id
         * main_parent_node_path (def: none) Alternative to above param, uses path string
         *                instead for recursive fetch, format $node.path_string: '/1/2/144/'
         * owner_main_parent_node_id (def: none) Limit result based on parent main 
         *                node id of owner ( main user group ) 
         * owner_main_parent_node_path (def: none) Alternative to above param, uses path string
         *                instead for recursive fetch, format $node.path_string: '/1/2/144/'
         * owner_id (def: none) filters by owner object id
         * as_object (def: true) make node objects or not (rating ) 
         * load_data_map (def: false) preload data_map 
         */

        $ret         = array();
        $whereSql    = array();
        $offset      = 0;
        $limit       = 10;
        $fromSql     = '';
        $asObject    = isset( $params['as_object'] ) ? $params['as_object'] : true;
        $loadDataMap = isset( $params['load_data_map'] ) ? $params['load_data_map'] : false;
        $selectSql   = 'ezcontentobject.*, node_tree.*,';
        $groupBySql  = 'GROUP BY ezcontentobject.id';
        $orderBySql  = 'ORDER BY rate DESC, rate_count DESC';// default sorting
        
        // WARNING: group_by_owner only works as intended if user is owner of him self..
        if (isset( $params['group_by_owner'] ) && $params['group_by_owner'] )
        {
            // group by owner instead of content object and fetch users instead of content objects
            $selectSql  = 'ezcontentobject.*, owner_tree.*,';
            $groupBySql = 'GROUP BY ezcontentobject.owner_id';
        }
        
        if (isset( $params['owner_main_parent_node_id'] ) and is_numeric($params['owner_main_parent_node_id']) )
        {
            // filter by main parent node of owner (main user group)
            $parentNodeId = $params['owner_main_parent_node_id'];
            $whereSql[] = 'owner_tree.parent_node_id = ' . $parentNodeId;
        }
        else if (isset( $params['owner_main_parent_node_path'] ) and is_string($params['owner_main_parent_node_path']) )
        {
            // filter recursivly by main parent node id
            // supported format is /1/2/144/256/ ( $node.path_string )
            $parentNodePath = $params['owner_main_parent_node_path'];
            $whereSql[] = "owner_tree.path_string != '$parentNodePath'";
            $whereSql[] = "owner_tree.path_string like '$parentNodePath%'";
        }
        else if (isset( $params['owner_id'] ) and is_numeric($params['owner_id']) )
        {
            // filter by owner_id ( user / contentobject id)
            $ownerId = $params['owner_id'];
            $whereSql[] = 'ezcontentobject.owner_id = ' . $ownerId;
        }
        
        if (isset( $params['main_parent_node_id'] ) and is_numeric($params['main_parent_node_id']) )
        {
            // filter by main parent node id
            $parentNodeId = $params['main_parent_node_id'];
            $whereSql[] = 'node_tree.parent_node_id = ' . $parentNodeId;
        }
        else if (isset( $params['main_parent_node_path'] ) and is_string($params['main_parent_node_path']) )
        {
            // filter recursivly by main parent node id
            // supported format is /1/2/144/256/ ( $node.path_string )
            $parentNodePath = $params['main_parent_node_path'];
            $whereSql[] = "node_tree.path_string != '$parentNodePath'";
            $whereSql[] = "node_tree.path_string like '$parentNodePath%'";
        }
        
        if (isset( $params['class_identifier'] ))
        {
            // filter by class id
            $classID = array();
            $classIdentifier = $params['class_identifier'];
            if ( !is_array( $classIdentifier )) $classIdentifier = array( $classIdentifier );
            
            foreach ( $classIdentifier as $id )
            {
                $classID[] = is_string( $id ) ? eZContentObjectTreeNode::classIDByIdentifier( $id ) : $id;
            }
            if ( $classID )
            {
                $whereSql[] = 'ezcontentobject.contentclass_id in (' . implode( ',', $classID ) . ')';
            }
        }

        if (isset( $params['limit'] ))
        {
            $limit = (int) $params['limit'];
        }

        if (isset( $params['offset'] ))
        {
            $offset = (int) $params['offset'];
        }
        
        if ( isset( $params['sort_by'] ) && is_array( $params['sort_by'] ) )
        {
            $orderBySql = 'ORDER BY ';
            $orderArr = is_string( $params['sort_by'][0] ) ? array( $params['sort_by'] ) : $params['sort_by'];
            foreach( $orderArr as $key => $order )
            {
                if ( $key !== 0 ) $orderBySql .= ',';
                $direction = isset( $order[1] ) ? $order[1] : false;
                switch( $order[0] )
                {
                    case 'rate':
                    {
                        $orderBySql .= 'rate ' . ( $direction ? 'ASC' : 'DESC');
                    }break;
                    case 'rate_count':
                    {
                        $orderBySql .= 'rate_count ' . ( $direction ? 'ASC' : 'DESC');
                    }break;
                    case 'object_count':
                    {
                        $orderBySql .= 'object_count ' . ( $direction ? 'ASC' : 'DESC');
                    }break;
                    case 'published':
                    {
                        $orderBySql .= 'ezcontentobject.published ' . ( $direction ? 'ASC' : 'DESC');
                    }break;
                    case 'modified':
                    {
                        $orderBySql .= 'ezcontentobject.modified ' . ( $direction ? 'ASC' : 'DESC');
                    }break;
                    case 'view_count':
                    {
                        // notice: will only fetch nodes that HAVE a entry in the ezview_counter table!!!
                        $selectSql  .= 'ezview_counter.count as view_count,';
                        $fromSql    .= 'ezview_counter,';
                        $whereSql[]  = 'node_tree.node_id = ezview_counter.node_id';
                        $orderBySql .= 'view_count ' . ( $direction ? 'ASC' : 'DESC');                        
                    }break;
                }
            }
        }

        $whereSql = $whereSql ? ' AND ' . implode( $whereSql, ' AND '): '';

        $db  = eZDB::instance();
        $sql = "SELECT
                             $selectSql
                             AVG( ezcontentobject_rating.rating  ) as rate,
                             COUNT( ezcontentobject_rating.rating  ) as rate_count,
                             COUNT( ezcontentobject.id ) as object_count,
                             ezcontentclass.serialized_name_list as class_serialized_name_list,
                             ezcontentclass.identifier as class_identifier,
                             ezcontentclass.is_container as is_container
                            FROM
                             ezcontentobject_tree node_tree,
                             ezcontentobject_tree owner_tree,
                             ezcontentclass,
                             $fromSql
                             ezcontentobject
                            LEFT JOIN ezcontentobject_rating
                             ON ezcontentobject_rating.contentobject_id = ezcontentobject.id
                            WHERE
                             ezcontentobject.id = node_tree.contentobject_id AND
                             node_tree.node_id = node_tree.main_node_id AND
                             ezcontentobject.owner_id = owner_tree.contentobject_id AND
                             owner_tree.node_id = owner_tree.main_node_id AND
                             ezcontentclass.version=0 AND
                             ezcontentclass.id = ezcontentobject.contentclass_id
                             $whereSql
                            $groupBySql
                            $orderBySql";
        $ret = $db->arrayQuery( $sql, array( 'offset' => $offset, 'limit' => $limit ) );
        unset($db);
        if ( is_array( $ret ) && count( $ret ) !== 0 )
        {
            if ( $asObject )
            {
                $ret = eZContentObjectTreeNode::makeObjectsArray( $ret );
                if ( $loadDataMap )
                    eZContentObject::fillNodeListAttributes( $ret );
            }
            else
            {
                //$ret = $ret;
            }
            
        }
        else if ( $ret === false )
        {
            eZDebug::writeError( 'The ezcontentobject_rating table seems to be missing,
                          contact your administrator', 'eZContentRating' );
            $ret = array();
        }
        else
        {
            $ret = array();
        }
        return $ret;
    }
    
    static function getRatingByObjectSql( $id )
    {
        return 'ezcontentobject.id=' . (int) $id;
    }

    static function getRatingByUserSql( $id )
    {
        return 'ezcontentobject_rating.user_id=' . (int) $id;
    }

    static function getRatingByOwnerSql( $id )
    {
        return 'ezcontentobject.owner_id=' . (int) $id;
    }

    static function getRatingWhere( $where )
    {
        $ret = array('rating' => 0,
                     'rating_int' => 0,
                     'count' => 0,
                     'total' => 0,
                     'total_int' => 0,
                     'data' => array()
                     //'where' => $where
                     );
        if ( is_array( $where ) )
            $where = 'AND ' . implode(' AND ', $where );
        else if ( $where )
            $where = 'AND ' . $where;
            
        $db = eZDB::instance();        
        $rs = $db->arrayQuery( "SELECT ezcontentobject_rating.*,
                                       ezcontentobject.owner_id,
                                       ezcontentobject.published,
                                       ezcontentobject.modified
                            FROM
                             ezcontentobject_rating,
                             ezcontentobject
                            WHERE
                             ezcontentobject.id = ezcontentobject_rating.contentobject_id
                             $where" );
        unset($db);
        if ( $rs )
        {
            $ret['data'] = $rs;
            $ret['count'] = count( $rs );
            foreach ($rs as $row )
            {
                $ret['total'] += $row['rating'];
            }
            $ret['rating'] = $ret['total'] / $ret['count'];
            $ret['rating_int'] = (int) round( $ret['rating'] );
            $ret['total_int'] = (int) round( $ret['total'] );
        
        }
        else if ( $rs === false )
        {
            eZDebug::writeError( 'The ezcontentobject_rating table seems to be missing,
                                  contact your administrator', 'eZContentRating' );
        }
        return $ret;
    }
}

?>
