<?php
//
// ## BEGIN COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
// SOFTWARE NAME: eZ Publish Website Interface
// SOFTWARE RELEASE: 1.4-0
// COPYRIGHT NOTICE: Copyright (C) 1999-2009 eZ Systems AS
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

class eZFlashTagCloud
{
    function eZFlashTagCloud()
    {
    }

    function operatorList()
    {
        return array( 'ezflashtagcloud' );
    }

    function namedParameterPerOperator()
    {
        return true;
    }

    function namedParameterList()
    {
        return array( 'ezflashtagcloud' => array( 'params' => array( 'type' => 'array',
                        'required' => false,
                        'default' => array() ) ) );
    }

    function modify( $tpl, $operatorName, $operatorParameters, &$rootNamespace, &$currentNamespace, &$operatorValue, &$namedParameters )
    {
        switch ( $operatorName )
        {
            case 'ezflashtagcloud':
            {

                $tags = array();
                $tagCloud = array();
                $tagCloudIni = eZINI::instance('ezflashtagcloud.ini');
                $parentNodeID = 0;
                $classIdentifier = '';
                $classIdentifierSQL = '';
                $pathString = '';
                $parentNodeIDSQL = '';
                $dbParams = array();
                $params = $namedParameters['params'];
                $orderBySql = 'ORDER BY ezkeyword.keyword ASC';

                if ( isset( $params['class_identifier'] ) )
                    $classIdentifier = $params['class_identifier'];

                if ( isset( $params['parent_node_id'] ) )
                    $parentNodeID = $params['parent_node_id'];

                if ( isset( $params['limit'] ) )
                    $dbParams['limit'] = $params['limit'];

                if ( isset( $params['offset'] ) )
                    $dbParams['offset'] = $params['offset'];
                    
                if ( isset( $params['width'] ) )
                    $width = $params['width'];
                else
                	$width =$tagCloudIni->variable('FlashSettings', 'Width');
                    
                if ( isset( $params['height'] ) )
                    $height = $params['height'];
                else 
                	$height = $tagCloudIni->variable('FlashSettings', 'Height');

	            if ( isset( $params['sort_by'] ) && is_array( $params['sort_by'] ) && count(  $params['sort_by'] ) )
                {
                    $orderBySql = 'ORDER BY ';
                    $orderArr = is_string( $params['sort_by'][0] ) ? array( $params['sort_by'] ) : $params['sort_by'];

                    foreach( $orderArr as $key => $order )
                    {
                        if ( $key !== 0 ) $orderBySql .= ', ';
                        $direction = isset( $order[1] ) ? $order[1] : false;
                        switch( $order[0] )
                        {
                            case 'keyword':
                            {
                                $orderBySql .= 'ezkeyword.keyword ' . ( $direction ? 'ASC' : 'DESC');
                            }break;
                            case 'count':
                            {
                                $orderBySql .= 'keyword_count ' . ( $direction ? 'ASC' : 'DESC');
                            }break;
                        }
                    }
                }

                $db = eZDB::instance();

                if( $classIdentifier )
                {
                    $classID = eZContentObjectTreeNode::classIDByIdentifier( $classIdentifier );
                    $classIdentifierSQL = "AND ezcontentobject.contentclass_id = '" . $classID . "'";
                }

                if( $parentNodeID )
                {
                    $node = eZContentObjectTreeNode::fetch( $parentNodeID );
                    if ( $node )
                        $pathString = "AND ezcontentobject_tree.path_string like '" . $node->attribute( 'path_string' ) . "%'";
                    $parentNodeIDSQL = 'AND ezcontentobject_tree.node_id != ' . (int)$parentNodeID;
                }

                $showInvisibleNodesCond = eZContentObjectTreeNode::createShowInvisibleSQLString( true, false );
                $limitation = false;
                $limitationList = eZContentObjectTreeNode::getLimitationList( $limitation );
                $sqlPermissionChecking = eZContentObjectTreeNode::createPermissionCheckingSQL( $limitationList );

                $languageFilter = 'AND ' . eZContentLanguage::languagesSQLFilter( 'ezcontentobject' );

                $rs = $db->arrayQuery( "SELECT ezkeyword.keyword, count(*) as keyword_count
                                        FROM ezkeyword,
                                            ezkeyword_attribute_link,
                                            ezcontentobject,
                                            ezcontentobject_attribute,
                                            ezcontentobject_tree
                                            $sqlPermissionChecking[from]
                                        WHERE ezkeyword.id = ezkeyword_attribute_link.keyword_id
                                            AND ezkeyword_attribute_link.objectattribute_id = ezcontentobject_attribute.id
                                            AND ezcontentobject_attribute.contentobject_id = ezcontentobject_tree.contentobject_id
                                            AND ezcontentobject_attribute.contentobject_id = ezcontentobject.id
                                            AND ezcontentobject.status = " . eZContentObject::STATUS_PUBLISHED . "
                                            AND ezcontentobject_attribute.version = ezcontentobject.current_version
                                            AND ezcontentobject_tree.main_node_id = ezcontentobject_tree.node_id
                                            $pathString
                                            $parentNodeIDSQL
                                            $classIdentifierSQL
                                            $showInvisibleNodesCond
                                            $sqlPermissionChecking[where]
                                            $languageFilter
                                        GROUP BY ezkeyword.id, ezkeyword.keyword
                                        $orderBySql", $dbParams );

                foreach( $rs as $row )
                {
                    $tags[$row['keyword']] = $row['keyword_count'];
                }

                $maxFontSize = 35;
                $minFontSize = 8;

                $maxCount = 0;
                $minCount = 0;

                if( count( $tags ) != 0 )
                {
                    $maxCount = max( array_values( $tags ) );
                    $minCount = min( array_values($tags ) );
                }

                $spread = $maxCount - $minCount;
                if ( $spread == 0 )
                    $spread = 1;

                $step = ( $maxFontSize - $minFontSize )/( $spread );

                foreach ($tags as $key => $value)
                {
                    $size = $minFontSize + ( ( $value - $minCount ) * $step * 1.5 );
                    $tagCloud[] = array( 'font_size' => $size,
                                         'count' => $value,
                                         'tag' => $key );
                }

                require_once( 'kernel/common/template.php' );
                $tpl = templateInit();
                $tpl->setVariable( 'tag_cloud', $tagCloud );
                $tpl->setVariable( 'width', $width );
                $tpl->setVariable( 'height', $height );
                $operatorValue = $tpl->fetch( 'design:flashtagcloud/tagcloud.tpl' );
            } break;
        }
    }
}

?>