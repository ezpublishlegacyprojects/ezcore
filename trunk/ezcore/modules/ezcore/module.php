<?php

$Module = array( 'name' => 'eZCore Module and Views' );


$ViewList = array();

$ViewList['rate'] = array(
    'functions' => array( 'rate_content' ),
    'script' => 'rate_content.php',
    'params' => array( 'contentobject_id', 'object_rating' )
    );

/*
$ViewList['rate_get'] = array(
    'functions' => array( 'rate' ),
    'script' => 'rate_get.php',
    'params' => array( 'contentobject_id', 'id_is_node' )
    );
*/

$ViewList['publish'] = array(
    'functions' => array( 'publish' ),
    'script' => 'publish.php',
    'params' => array( 'parent_node_id', 'class_identifier', 'language_code' )
    );

$ViewList['remove_assignment'] = array(
    'functions' => array( 'remove_assignment' ),
    'script' => 'remove_assignment.php',
    'params' => array( 'parent_node_id', 'delete_id' )
    );

$ViewList['attribute_edit'] = array(
    'functions' => array( 'attribute_edit' ),
    'script' => 'attribute_edit.php',
    'params' => array( 'object_id', 'attribute_identifier', 'attribute_value' )
    );
    


$ViewList['relate'] = array(
    'functions' => array( 'relate' ),
    'script' => 'relate.php',
    'params' => array( 'contentobject_id',
                       'attribute_identifier',
                       'relate_to_object_id',
                       'relation_var1',
                       'relation_var2',
                       'replace_by_vars' )
    );
    
$ViewList['unrelate'] = array(
    'functions' => array( 'unrelate' ),
    'script' => 'unrelate.php',
    'params' => array( 'contentobject_id',
                       'attribute_identifier',
                       'relate_to_object_id' )
    );

$ViewList['get_related'] = array(
    'functions' => array( 'get_related' ),
    'script' => 'get_related.php',
    'params' => array( 'contentobject_id',
                       'attribute_identifier',
                       'class_identifier',
                       'reverse_mode',
                       'related_to_id' )
    );

$ViewList['get_children'] = array(
    'functions' => array( 'get_children' ),
    'script' => 'get_children.php',
    'params' => array( 'parent_node_id',
                       'class_identifier',
                       'related_to_id' )
    );
    
$ViewList['call'] = array(
    'functions' => array( 'call' ),
    'script' => 'call.php',
    'params' => array( 'function_arguments', 'content_type', 'type', 'interval' )
    );
    



$FunctionList = array();
$FunctionList['rate_content'] = array();
$FunctionList['publish'] = array();
$FunctionList['remove_assignment'] = array();
$FunctionList['attribute_edit'] = array();
$FunctionList['relate'] = array();
$FunctionList['unrelate'] = array();
$FunctionList['get_related'] = array();
$FunctionList['get_children'] = array();
$FunctionList['call'] = array();




?>