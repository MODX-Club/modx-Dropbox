<?php
$xpdo_meta_map['DropboxObject']= array (
  'package' => 'Dropbox',
  'version' => '1.1',
  'extends' => 'xPDOSimpleObject',
  'fields' => 
  array (
    'source_id' => NULL,
    'path' => '/',
  ),
  'fieldMeta' => 
  array (
    'source_id' => 
    array (
      'dbtype' => 'int',
      'precision' => '10',
      'attributes' => 'unsigned',
      'phptype' => 'integer',
      'null' => false,
      'index' => 'index',
    ),
    'path' => 
    array (
      'dbtype' => 'varchar',
      'precision' => '1024',
      'phptype' => 'string',
      'null' => false,
      'default' => '/',
    ),
  ),
  'indexes' => 
  array (
    'source_path_id' => 
    array (
      'alias' => 'source_path_id',
      'primary' => false,
      'unique' => false,
      'type' => 'BTREE',
      'columns' => 
      array (
        'source_id' => 
        array (
          'length' => '',
          'collation' => 'A',
          'null' => false,
        ),
        'path' => 
        array (
          'length' => '128',
          'collation' => 'A',
          'null' => false,
        ),
      ),
    ),
  ),
  'aggregates'  => array(
        "Source"  => array(
            "class" => "modMediaSource",
            "cardinality"   => "one",
            "local" => "source_id",
            "foreign"   => "id",
            "owner"     => "foreign",
        ),
   ),
);
