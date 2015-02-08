<?php
$xpdo_meta_map['DropboxCursor']= array (
  'package' => '_Dropbox',
  'version' => '1.1',
  'table' => 'dropbox_cursors',
  'extends' => 'DropboxObject',
  'fields' => 
  array (
    'cursor' => NULL,
    'has_more' => 0,
    'entries' => NULL,
    'reset' => 0,
  ),
  'fieldMeta' => 
  array (
    'cursor' => 
    array (
      'dbtype' => 'varchar',
      'precision' => '512',
      'phptype' => 'string',
      'null' => true,
    ),
    'has_more' => 
    array (
      'dbtype' => 'tinyint',
      'precision' => '1',
      'phptype' => 'integer',
      'null' => false,
      'default' => 0,
    ),
    'entries' => 
    array (
      'dbtype' => 'text',
      'phptype' => 'json',
      'null' => false,
    ),
    'reset' => 
    array (
      'dbtype' => 'tinyint',
      'precision' => '3',
      'attributes' => 'unsigned',
      'phptype' => 'integer',
      'null' => false,
      'default' => 0,
    ),
  ),
  'indexes' => 
  array (
    'cursor' => 
    array (
      'alias' => 'cursor',
      'primary' => false,
      'unique' => true,
      'type' => 'BTREE',
      'columns' => 
      array (
        'cursor' => 
        array (
          'length' => '128',
          'collation' => 'A',
          'null' => false,
        ),
      ),
    ),
  ),
);
