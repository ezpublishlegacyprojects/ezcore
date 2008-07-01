<?php

$eZTemplateOperatorArray = array();
$eZTemplateOperatorArray[] = array( 'script' => 'extension/ezcore/autoloads/ezrating.php',
                                    'class' => 'eZContentRating',
                                    'operator_names' => array( 'ezrating',
                                                               'fetch_by_rating') );

$eZTemplateOperatorArray[] = array( 'script' => 'extension/ezcore/autoloads/ezpackertemplatefunctions.php',
                                    'class' => 'eZPackerTemplateFunctions',
                                    'operator_names' => array( 'ezscript',
                                                               'ezscriptfiles',
                                                               'ezcss',
                                                               'ezcssfiles' ) );


$eZTemplateOperatorArray[] = array( 'script' => 'extension/ezcore/autoloads/ezcoreutils.php',
                                    'class' => 'eZCoreUtils',
                                    'operator_names' => array( 'ezweeknumber',
                                                               'fetch_main_node',
                                                               'json_encode',
                                                               'xml_encode',
                                                               'node_encode'
) );
?>
