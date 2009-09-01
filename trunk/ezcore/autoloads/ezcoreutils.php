<?php
//
// Definition of eZCoreUtils
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
  Toolkit of handy template operators:
  * ezweeknumber( $timestamp = time())
    A operator to get  weeknumber of a given time
*/

include_once( 'extension/ezcore/classes/ezajaxcontent.php' );

class eZCoreUtils
{
    function eZCoreUtils()
    {
    }

    function operatorList()
    {
        return array( 'ezweeknumber',
                      'fetch_main_node',
                      'json_encode',
                      'xml_encode',
                      'node_encode'
                      );
    }

    function namedParameterPerOperator()
    {
        return true;
    }

    function namedParameterList()
    {
        return array( 'ezweeknumber' => array( 'timestamp' => array( 'type' => 'int',
                                               'required' => false,
                                               'default' => time() )),
                      'fetch_main_node' => array( 'object_id' => array( 'type' => 'int',
                                                'required' => true,
                                                'default' => 0 ),
                                               'as_object' => array( 'type' => 'boolean',
                                                'required' => false,
                                                'default' => true )),
                      'json_encode' => array( 'hash' => array( 'type' => 'hash',
                                                'required' => true,
                                                'default' => array() )),
                      'xml_encode' => array( 'hash' => array( 'type' => 'hash',
                                                'required' => true,
                                                'default' => array() )),
                      'node_encode' => array( 'node' => array( 'type' => 'object',
                                                'required' => true,
                                                'default' => array() ),
                                              'params' => array( 'type' => 'hash',
                                                'required' => false,
                                                'default' => array() ),
                                              'type' => array( 'type' => 'string',
                                                'required' => false,
                                                'default' => 'json' ))
        );
                                              
    }

    function modify( $tpl, $operatorName, $operatorParameters, $rootNamespace, $currentNamespace, &$operatorValue, $namedParameters )
    {
        switch ( $operatorName )
        {
            case 'ezweeknumber':
            {            
                /*
                 * Returns the week, year, day, start time and end time of week as defined in ISO 8601
                 * First parameter is optional if you want to use a different timestamp then 'now'.
                 * ISO 8601:
                 * Rule 1: 4. January is always in week number 1
                 * Rule 2: It's always 52 or 53 ISO-weeks in a year.
                 *         A year ha a extra week if the korosponding year beginns on a thursday
                 *         or on a wednesday/thursday and has a leap day (feb. 29).
                 * Rule 3: A ISO-week starts on a Monday (1) and ends on a Sunday (7)
                 * 
                 * Returns week number data in according to ISO 8601
                 */
                $operatorValue = array('week' => 1, 'year' => 1970, 'day' => 1);
                
                $ts                    = $namedParameters['timestamp'];
                $day                   = date('N', $ts);     
                $operatorValue['week'] = date('W', $ts);
                $operatorValue['year'] = date('o', $ts);
                $operatorValue['day']  = $day;

                $m = date('m', $ts);
                $d = date('d', $ts);
                $Y = date('Y', $ts);
                
                $operatorValue['start'] = mktime(0, 0, 0, $m, $d - ($day - 1), $Y);
                $operatorValue['end']   = mktime(23, 59, 59, $m, $d + (7 - $day), $Y);
            } break;
            case 'fetch_main_node':
            {
                // Lets you use eZContentObjectTreeNode::findMainNode from templates
                // Notice: if as_object is false, only node id is returned
                
                $operatorValue = eZContentObjectTreeNode::findMainNode( $namedParameters['object_id'], $namedParameters['as_object'] );
                //if array findMainNodeArray( id, false );
            } break;
            case 'json_encode':
            {
                // Lets you use eZAjaxContent::jsonEncode from templates
                
                $operatorValue = eZAjaxContent::jsonEncode( $namedParameters['hash'] );
            } break;
            case 'xml_encode':
            {
                // Lets you use eZAjaxContent::xmlEncode from templates
                
                $operatorValue = eZAjaxContent::xmlEncode( $namedParameters['hash'] );
            } break;
            case 'node_encode':
            {
                // Lets you use eZAjaxContent::nodeEncode from templates
                
                $operatorValue = eZAjaxContent::nodeEncode( $namedParameters['node'], $namedParameters['params'], $namedParameters['type'] );
            } break;
        }
    }
}

?>