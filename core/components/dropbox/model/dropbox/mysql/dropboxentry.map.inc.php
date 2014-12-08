<?php
$xpdo_meta_map['DropboxEntry']= array (
  'package' => 'Dropbox',
  'version' => '1.1',
  'table' => 'dropbox_entries',
  'extends' => 'DropboxObject',
  'fields' => 
  array (
    'size' => NULL,
    'bytes' => 0,
    'is_dir' => 0,
    'is_deleted' => 0,
    'rev' => NULL,
    'hash' => NULL,
    'thumb_exists' => 0,
    'photo_info' => NULL,
    'video_info' => NULL,
    'icon' => NULL,
    'modified' => NULL,
    'modified_time' => 0,
    'client_mtime' => NULL,
    'root' => NULL,
    'shared_folder' => NULL,
    'read_only' => 0,
    'parent_shared_folder_id' => NULL,
    'modifier' => NULL,
    'content' => NULL,
  ),
  'fieldMeta' => 
  array (
    'size' => 
    array (
      'dbtype' => 'varchar',
      'precision' => '24',
      'phptype' => 'string',
      'null' => false,
    ),
    'bytes' => 
    array (
      'dbtype' => 'int',
      'precision' => '10',
      'attributes' => 'unsigned',
      'phptype' => 'integer',
      'null' => false,
      'default' => 0,
    ),
    'is_dir' => 
    array (
      'dbtype' => 'tinyint',
      'precision' => '1',
      'attributes' => 'unsigned',
      'phptype' => 'integer',
      'null' => false,
      'default' => 0,
    ),
    'is_deleted' => 
    array (
      'dbtype' => 'tinyint',
      'precision' => '1',
      'phptype' => 'integer',
      'null' => false,
      'default' => 0,
    ),
    'rev' => 
    array (
      'dbtype' => 'varchar',
      'precision' => '13',
      'phptype' => 'string',
      'null' => false,
      'index' => 'unique',
    ),
    'hash' => 
    array (
      'dbtype' => 'varchar',
      'precision' => '32',
      'phptype' => 'string',
      'null' => false,
    ),
    'thumb_exists' => 
    array (
      'dbtype' => 'tinyint',
      'precision' => '1',
      'phptype' => 'integer',
      'null' => false,
      'default' => 0,
    ),
    'photo_info' => 
    array (
      'dbtype' => 'varchar',
      'precision' => '1024',
      'phptype' => 'string',
      'null' => false,
    ),
    'video_info' => 
    array (
      'dbtype' => 'varchar',
      'precision' => '1024',
      'phptype' => 'string',
      'null' => false,
    ),
    'icon' => 
    array (
      'dbtype' => 'varchar',
      'precision' => '128',
      'phptype' => 'string',
      'null' => false,
    ),
    'modified' => 
    array (
      'dbtype' => 'varchar',
      'precision' => '48',
      'phptype' => 'string',
      'null' => false,
    ),
    'modified_time' => 
    array (
      'dbtype' => 'int',
      'precision' => '11',
      'attributes' => 'unsigned',
      'phptype' => 'integer',
      'null' => false,
      'default' => 0,
      'index' => 'index',
    ),
    'client_mtime' => 
    array (
      'dbtype' => 'varchar',
      'precision' => '48',
      'phptype' => 'string',
      'null' => false,
    ),
    'root' => 
    array (
      'dbtype' => 'varchar',
      'precision' => '1024',
      'phptype' => 'string',
      'null' => false,
    ),
    'shared_folder' => 
    array (
      'dbtype' => 'varchar',
      'precision' => '1024',
      'phptype' => 'string',
      'null' => false,
    ),
    'read_only' => 
    array (
      'dbtype' => 'tinyint',
      'precision' => '1',
      'phptype' => 'integer',
      'null' => false,
      'default' => 0,
    ),
    'parent_shared_folder_id' => 
    array (
      'dbtype' => 'varchar',
      'precision' => '1024',
      'phptype' => 'string',
      'null' => false,
    ),
    'modifier' => 
    array (
      'dbtype' => 'varchar',
      'precision' => '128',
      'phptype' => 'string',
      'null' => false,
    ),
    'content' => 
    array (
      'dbtype' => 'blob',
      'phptype' => 'binary',
      'null' => false,
    ),
  ),
  'indexes' => 
  array (
    'rev' => 
    array (
      'alias' => 'rev',
      'primary' => false,
      'unique' => true,
      'type' => 'BTREE',
      'columns' => 
      array (
        'rev' => 
        array (
          'length' => '',
          'collation' => 'A',
          'null' => false,
        ),
      ),
    ),
    'modified_time' => 
    array (
      'alias' => 'modified_time',
      'primary' => false,
      'unique' => false,
      'type' => 'BTREE',
      'columns' => 
      array (
        'modified_time' => 
        array (
          'length' => '',
          'collation' => 'A',
          'null' => false,
        ),
      ),
    ),
  ),
);
