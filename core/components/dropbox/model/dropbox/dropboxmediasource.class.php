<?php

require_once MODX_CORE_PATH . 'model/modx/sources/modmediasource.class.php';
require_once dirname(dirname(dirname(__FILE__))) . "/include/client.class.php";  

use \Dropbox as dbx;


class dropboxMediaSource extends modMediaSource implements modMediaSourceInterface
{
	/**
	 * @var DropboxClient
	 */
	private $client;
	private $connectorUrl;
    
    const THUMBNAIL_SIZE_SMALL = 'small';
	const THUMBNAIL_SIZE_MEDIUM = 's';
	const THUMBNAIL_SIZE_LARGE = 'm';
	const THUMBNAIL_FORMAT_JPG = 'jpeg';
	const THUMBNAIL_FORMAT_PNG = 'png';
    
    protected $_properties = array();
    
    protected $initialized = false;

	/**
	 * Override the constructor to always force Dropbox sources not to be streams
	 * @param xPDO $xpdo
	 */
	public function __construct(xPDO &$xpdo)
	{
		parent::__construct($xpdo);
		$this->set('is_stream', false);
		$this->xpdo->lexicon->load('dropbox:default');
	}

	/**
	 * Initialize the source
	 * @return boolean
	 */
	public function initialize()
	{
        $ok = parent::initialize();
        
        if($ok !== true){
            return $ok;
        }
         
		if (!$this->ctx) {
			$this->ctx = &$this->xpdo->context;
		}
        $this->ctx->prepare();
        
        $this->_properties = $this->getPropertyList(); 
        
        return true; 
	}
    
    public function & getClient(){
        if(!$this->client){
            if(
                !$this->initialized
                AND $this->initialize() !== true
            ){
                return $this->client;
            }
            $accessToken = $this->getProperty('authToken');
            $this->client = new DropboxClient($accessToken, "PHP-Example/1.0"); 
        }
        return $this->client;
    }
    
    function getProperty($key, $value = null){
        if(isset($this->_properties[$key])){
            $value = $this->_properties[$key];
        }
        return $value;
    }

	/**
	 * Return an array of containers at this current level in the container structure. Used for the tree navigation on the files tree
	 * @param string $path
	 * @return array
	 */
	public function getContainerList($path){
		$versionData = $this->xpdo->getVersionData();
        	$modx_2_2 = !version_compare($versionData['full_version'], "2.3"); 
        
		$response = null;
		try {
			$response = $this->getClient()->getMetadataWithChildren($this->getPath($path));
		} catch (Exception $e) {
			$this->xpdo->log(xPDO::LOG_LEVEL_ERROR, $this->lexicon('containerList', array(
				'path' => $path,
				'message' => $e->getMessage(),
			), 'error'));
			return array();
		}
         
        
		if (!$response || empty($response['contents'])) {
			return array();
		}
        
		$properties = $this->getPropertyList();
		$useMultibyte = $this->ctx->getOption('use_multibyte', false);
		$encoding = $this->ctx->getOption('modx_charset', 'UTF-8');
		$hideFiles = !empty($properties['hideFiles']) && $properties['hideFiles'] != 'false' ? true : false;
		$skipFiles = array_unique(array_filter(array_map('trim', explode(',', $this->getOption('skipFiles', $properties, '.svn,.git,_notes,.DS_Store,nbproject,.idea')))));
		$directories = array();
		$hasDirectoryPermissions = $this->hasPermission('directory_list');
		$canSave = $this->checkPolicy('save');
		$canRemove = $this->checkPolicy('remove');
		$canCreate = $this->checkPolicy('create');
		$directoryClasses = array( 
            "folder icon-folder"
        ); 
		if ($this->hasPermission('directory_chmod') && $canSave) {
			$directoryClasses[] = 'pchmod';
		}
		if ($this->hasPermission('directory_create') && $canCreate) {
			$directoryClasses[] = 'pcreate';
		}
		if ($this->hasPermission('directory_remove') && $canRemove) {
			$directoryClasses[] = 'premove';
		}
		if ($this->hasPermission('directory_update') && $canSave) {
			$directoryClasses[] = 'pupdate';
		}
		if ($this->hasPermission('file_upload') && $canCreate) {
			$directoryClasses[] = 'pupload';
		}
		if ($this->hasPermission('file_create') && $canCreate) {
			$directoryClasses[] = 'pcreate';
		}
		$files = array();
		$fileClasses = array(
			'icon-file',
		);
		if ($this->hasPermission('file_remove') && $canRemove) {
			$fileClasses[] = 'premove';
		}
		if ($this->hasPermission('file_update') && $canSave) {
			$fileClasses[] = 'pupdate';
		}
		$hasFilePermissions = $this->hasPermission('file_list');
		$assetsUrl = $this->ctx->getOption('assets_url', MODX_ASSETS_URL);
        
        $editAction = $this->getEditActionId();
         
		foreach ($response['contents'] as $entry) { 
			$baseName = $this->basename($entry['path']); 
			if (in_array($baseName, $skipFiles)) {
				continue;
			}
			$entryPath = $entry['path'];
			if ($entry['is_dir']) {
				if ($hasDirectoryPermissions) { 
                    
                    $classes = implode(' ', array_unique(array_filter(array_merge($directoryClasses, array($entry['icon'])))));
                    
					$directories[$baseName] = array(
						'id' => $entryPath . '/',
						'text' => $baseName, 
						'type' => 'dir',
						'leaf' => false,
						'path' => $entryPath,
						'pathRelative' => $entryPath,
						'perms' => '',
						'menu' => array(),
					); 
                    
                    if($modx_2_2){
                        $directories[$baseName]['cls'] = $classes;
                    }
                    else{
                        $directories[$baseName]['iconCls'] = $classes;
                    } 
                    
					$directories[$baseName]['menu'] = array(
						'items' => $this->getListContextMenu(true, $directories[$baseName]),
					);
				}
			} else {
				if (!$hideFiles && $hasFilePermissions) {
					$ext = pathinfo($baseName, PATHINFO_EXTENSION);
					$ext = $useMultibyte ? mb_strtolower($ext, $encoding) : strtolower($ext);
					$cls = array();
					$cls[] = 'icon-' . $ext;
					$cls[] = $entry['icon'];
					$qTip = '';
                    
                    # print '<pre>';
                    # 
                    # print 'Xheck 1';
                    # 
                    # print_r($entry);
                    
					if ($entry['thumb_exists']) {
						$fileName = $this->getThumbnail($entryPath);
						if ($fileName != '') {
							$qTip = '<img src="' . $assetsUrl . $fileName . '" alt="' . $baseName . '" />';
						}
					}
					$objectUrl = $this->getUrl($entryPath);
                    
                    $page = !empty($editAction) ? '?a='.$editAction.'&file='.$entry['path'].'&wctx='.$this->ctx->get('key').'&source='.$this->get('id') : null;
                     
                    $classes = implode(' ', array_unique(array_filter(array_merge($fileClasses, $cls))));
                    
					$files[$baseName] = array(
						'id' => $entryPath,
						'text' => $baseName, 
						'type' => 'file',
						'leaf' => true,
						'qtip' => $qTip,
						'page' => $this->fileIsWritable($entry) ? $page : 0,
						'perms' => '',
						'path' => $entryPath,
						'pathRelative' => $entryPath,
						'directory' => $path,
						'url' => $objectUrl,
						'file' => $this->ctx->getOption('base_url', MODX_BASE_URL) . $objectUrl,
						'menu' => array(),
					); 
                    
                    if($modx_2_2){
                        $files[$baseName]['cls'] = $classes;
                    }
                    else{
                        $files[$baseName]['iconCls'] = $classes;
                    }
                     
					$files[$baseName]['menu'] = array(
						'items' => $this->getListContextMenu(false, $files[$baseName]),
					);
				}
			}
		}
		ksort($directories);
		ksort($files);
		return array_merge(array_values($directories), array_values($files));
	}

	/**
	 * Return a detailed list of objects in a specific path. Used for thumbnails in the Browser
	 * @param string $path
	 * @return array
	 */
	public function getObjectsInContainer($path)
	{
        # die('getObjectsInContainer');
		$response = null;
		try {
			$response = $this->getClient()->getMetadataWithChildren($this->getPath($path));
		} catch (Exception $e) {
			$this->xpdo->log(xPDO::LOG_LEVEL_ERROR, $this->lexicon('objectsInContainer', array(
				'path' => $path,
				'message' => $e->getMessage(),
			), 'error'));
			return array();
		}
		# if (!($response instanceof ResponseMetadata) || !isset($response->contents) || count($response->contents) == 0) {
		# 	return array();
		# }
		if (!$response || empty($response['contents'])) {
			return array();
		}
		$useMultibyte = $this->ctx->getOption('use_multibyte', false);
		$encoding = $this->ctx->getOption('modx_charset', 'UTF-8');
		$allowedFileTypes = $this->getOption('allowedFileTypes', $this->properties, '');
		$allowedFileTypes = !empty($allowedFileTypes) && is_string($allowedFileTypes) ? explode(',', $allowedFileTypes) : $allowedFileTypes;
		$allowedFileTypes = is_array($allowedFileTypes) ? array_unique(array_filter(array_map('trim', $allowedFileTypes))) : array();
		$imageExtensions = $this->getOption('imageExtensions', $this->properties, 'jpg,jpeg,png,gif');
		$imageExtensions = explode(',', $imageExtensions);
		$skipFiles = array_unique(array_filter(array_map('trim', explode(',', $this->getOption('skipFiles', $this->properties, '.svn,.git,_notes,.DS_Store,nbproject,.idea')))));
		$files = array();
        
        
        # print '<pre>'; 
        #             print 'Xheck 2'; 
                    
                    
		foreach ($response['contents'] as $entry) {
			if ($entry['is_dir']) {
				continue;
			}
            
            
            
			$entryPath = $entry['path'];
			$fileName = $this->basename($entryPath);
			if (in_array($fileName, $skipFiles)) {
				continue;
			}
			$ext = pathinfo($fileName, PATHINFO_EXTENSION);
			$ext = $useMultibyte ? mb_strtolower($ext, $encoding) : strtolower($ext);
			if (!empty($allowedFileTypes) && !in_array($ext, $allowedFileTypes)) {
				continue;
			}
            
            # print_r($entry);
			
            $objectUrl = $this->getUrl($entryPath);
			$thumbWidth = $this->ctx->getOption('filemanager_thumb_width', 80);
			$thumbHeight = $this->ctx->getOption('filemanager_thumb_height', 60);
			$file = array(
				'id' => $entryPath,
				'name' => $fileName,
				'url' => $entryPath,
				'relativeUrl' => $entryPath,
				'fullRelativeUrl' => $objectUrl,
				'ext' => $ext,
				'pathname' => $entryPath,
				'lastmod' => strtotime($entry['modified']),
				'leaf' => true,
				'size' => $entry['bytes'],
				'thumb' => $this->ctx->getOption('manager_url', MODX_MANAGER_URL) . 'templates/default/images/restyle/nopreview.jpg',
				'thumbWidth' => $thumbWidth,
				'thumbHeight' => $thumbHeight,
				'menu' => array(
					array(
						'text' => $this->xpdo->lexicon('file_remove'),
						'handler' => 'this.removeFile',
					),
				),
			);
			if (in_array($ext, $imageExtensions)) {
				$imageWidth = $this->ctx->getOption('filemanager_image_width', 400);
				$imageHeight = $this->ctx->getOption('filemanager_image_height', 300);
				/*$size = @getimagesize($objectUrl);
				if (is_array($size)) {
					$imageWidth = $size[0] > 800 ? 800 : $size[0];
					$imageHeight = $size[1] > 600 ? 600 : $size[1];
				}*/
				if ($thumbWidth > $imageWidth) {
					$thumbWidth = $imageWidth;
				}
				if ($thumbHeight > $imageHeight) {
					$thumbHeight = $imageHeight;
				}
				# $objectUrl = urlencode($this->ctx->getOption('base_url', MODX_BASE_URL) . $objectUrl);
				$thumbUrl = '';
                    
				if ($entry['thumb_exists']) {
					$thumbUrl = $this->getThumbnail($entryPath);
				}
				$thumbUrl = !empty($thumbUrl) ? ($this->ctx->getOption('assets_url', MODX_ASSETS_URL) . $thumbUrl) : $objectUrl;
				$file['thumb'] = $thumbUrl;
				# $thumbParams = array(
				# 	'f' => $this->getOption('thumbnailType', $this->properties, 'png'),
				# 	'q' => $this->getOption('thumbnailQuality', $this->properties, 90),
				# 	'HTTP_MODAUTH' => $this->xpdo->user->getUserToken($this->xpdo->context->get('key')),
				# 	'wctx' => $this->ctx->get('key'),
				# 	'source' => $this->get('id'),
				# );
				# $thumbQuery = http_build_query(array_merge($thumbParams, array(
				# 	'src' => $thumbUrl,
				# 	'w' => $thumbWidth,
				# 	'h' => $thumbHeight,
				# )));
				# $imageQuery = http_build_query(array_merge($thumbParams, array(
				# 	'src' => $objectUrl,
				# 	'w' => $imageWidth,
				# 	'h' => $imageHeight,
				# )));
				# # $file['thumb'] = $this->ctx->getOption('connectors_url', MODX_CONNECTORS_URL) . 'system/phpthumb.php?' . urldecode($thumbQuery);
				# $file['image'] = $this->ctx->getOption('connectors_url', MODX_CONNECTORS_URL) . 'system/phpthumb.php?' . urldecode($imageQuery);
				$file['image'] = $objectUrl;
			}
            
            # print_r($file);
            
			$files[] = $file;
		}
		return $files;
	}

	/**
	 * Create a container at the passed location with the passed name
	 * @param string $name
	 * @param string $parentContainer
	 * @return boolean
	 */
	public function createContainer($name, $parentContainer)
	{
		$response = null;
		$path = rtrim($parentContainer, '/') . '/' . trim($name, '/');
		try {
			$response = $this->getClient()->createFolder($this->getPath($path));
		} catch (Exception $e) {
			$this->xpdo->log(xPDO::LOG_LEVEL_ERROR, $this->lexicon('createContainer', array(
				'path' => $path,
				'message' => $e->getMessage(),
			), 'error'));
			$this->addError('name', $this->xpdo->lexicon('file_folder_err_create'));
			return false;
		}
		if (!$response) {
			$this->addError('name', $this->xpdo->lexicon('file_folder_err_create'));
			return false;
		}
        
        // Wait for update Dropbox data
        sleep(1);
        
		$this->xpdo->logManagerAction('directory_create', '', $path);
		return true;
	}

	/**
	 * Remove the specified container
	 * @param string $path
	 * @return boolean
	 */
	public function removeContainer($path)
	{
		$response = null;
		try {
			$response = $this->getClient()->delete($this->getPath($path));
		} catch (Exception $e) {
			$this->xpdo->log(xPDO::LOG_LEVEL_ERROR, $this->lexicon('removeContainer', array(
				'path' => $path,
				'message' => $e->getMessage(),
			), 'error'));
			$this->addError('path', $this->xpdo->lexicon('file_folder_err_remove'));
			return false;
		}
		if (!$response) {
			$this->addError('path', $this->xpdo->lexicon('file_folder_err_remove'));
			return false;
		}
		$this->xpdo->logManagerAction('directory_remove', '', $path);
		return true;
	}

	/**
	 * Rename a container
	 * @param string $oldPath
	 * @param string $newName
	 * @return boolean
	 */
	public function renameContainer($oldPath, $newName)
	{
		$response = null;
		try {
			$response = $this->getClient()->move($this->getPath($path), $this->getPath(dirname($oldPath) . '/' . $newName));
		} catch (Exception $e) {
			$this->xpdo->log(xPDO::LOG_LEVEL_ERROR, $this->lexicon('renameContainer', array(
				'path' => $oldPath,
				'name' => $newName,
				'message' => $e->getMessage(),
			), 'error'));
			$this->addError('name', $this->xpdo->lexicon('file_folder_err_rename'));
			return false;
		}
		if (!$response) {
			$this->addError('name', $this->xpdo->lexicon('file_folder_err_rename'));
			return false;
		}
		$this->xpdo->logManagerAction('directory_rename', '', $oldPath);
		return true;
	}

	/**
	 * Upload objects to a specific container
	 * @param string $container
	 * @param array $objects
	 * @return boolean
	 */
	public function uploadObjectsToContainer($container, array $objects = array())
    {
        
        if(mb_strpos($container, '/', null, $this->xpdo->getOption('charset', 'utf-8')) !== 0){
            $container = '/'.$container;
        }
        
		$tempPath = $this->xpdo->getOption(xPDO::OPT_CACHE_PATH) . 'dropbox/' . uniqid() . '/';
		if (!file_exists($tempPath)) {
			$this->xpdo->cacheManager->writeTree($tempPath);
		}
		 
		foreach ($objects as $file) {
			if ($file['error'] != UPLOAD_ERR_OK || empty($file['name'])) {
				continue;
			}
			
            if($this->checkExt($file['name']) !== true){
                continue;   
            }
            
			$fileName = $tempPath . $file['name'];
			$success = false;
			
    		if (move_uploaded_file($file['tmp_name'], $fileName)) {
				$response = null;
				try {
					$f = fopen($fileName, "rb");
                    $response = $this->getClient()->uploadFile($this->getPath($container . $file['name']), dbx\WriteMode::add(), $f);
                    fclose($f);
				} catch (Exception $e) {
					$this->xpdo->log(xPDO::LOG_LEVEL_ERROR, $this->lexicon('uploadObjectsToContainer', array(
						'path' => $container,
						'message' => $e->getMessage(),
					), 'error'));
				}
				@unlink($fileName);
				if ($response) {
					$success = true;
				}
			}
            
			if (!$success) {
				$this->addError('path', $this->xpdo->lexicon('file_err_upload'));
				continue;
			}
		}
		@rmdir($tempPath);
		$this->xpdo->invokeEvent('OnFileManagerUpload', array(
			'files' => &$objects,
			'directory' => $container,
			'source' => &$this,
		));
		$this->xpdo->logManagerAction('file_upload', '', $container);
        
        // Wait for update Dropbox data
        sleep(1);
		return !$this->getErrors();
	}
     
    
    protected function checkExt($fileName){
        
        $allowedFileTypes = explode(',', $this->xpdo->getOption('upload_files', null, ''));
        $allowedFileTypes = array_merge(
            explode(',', $this->xpdo->getOption('upload_images')), 
            explode(',', $this->xpdo->getOption('upload_media')), 
            explode(',', $this->xpdo->getOption('upload_flash')), 
            $allowedFileTypes
        );
		$allowedFileTypes = array_unique($allowedFileTypes);
        
        $ext = @pathinfo($fileName, PATHINFO_EXTENSION);
		$ext = strtolower($ext);
        
		if (empty($ext) || !in_array($ext, $allowedFileTypes)) {
			$this->addError('path', $this->xpdo->lexicon('file_err_ext_not_allowed', array(
				'ext' => $ext,
			)));
			return false;
		}
        
        return true;
    }
    

	/**
	 * Update the contents of a specific object
	 * @param string $objectPath
	 * @param string $content
	 * @return boolean
	 */
	public function updateObject($objectPath, $content)
	{
         
        # if($this->checkExt($objectPath) !== true){
        #     return false;   
        # } 
        
            
        # if(mb_strpos($objectPath, '/', null, $this->xpdo->getOption('charset', 'utf-8')) !== 0){
        #     $objectPath = '/'.$objectPath;
        # }
        
        $path = $this->getPath($objectPath);
        
        // Check file exists
        $response = null;
        try {
            $response = $this->getClient()->getMetadata($path);
    	} catch (Exception $e) {
            $error = $this->lexicon('getContent', array(
    			'path' => $path,
				'message' => $e->getMessage(),
			), 'error');
			$this->xpdo->log(xPDO::LOG_LEVEL_ERROR, $error);
            $this->addError('file', $error);
            return false;
		}
        
        if(!$response){
            $error = $this->lexicon('objectNotExists', array(
        		'path' => $path,
			), 'error');
			$this->xpdo->log(xPDO::LOG_LEVEL_ERROR, $error);
            $this->addError('file', $error);
            return false;
        }
        
        
        $response = null;
    	try {
            $response = $this->getClient()->uploadFileFromString($path, dbx\WriteMode::update(null), $content);
		} catch (Exception $e) {
            $error = $this->lexicon('updateObject', array(
    			'path' => $path,
				'message' => $e->getMessage(),
			), 'error');
			$this->xpdo->log(xPDO::LOG_LEVEL_ERROR, $error);
            $this->addError('file', $error);
            return false;
		}
        
        // Remove cache
    	$fileName = $this->xpdo->getOption(xPDO::OPT_CACHE_PATH) . $this->getCacheFileName($path, 'content');
		if (file_exists($fileName)) {
			@unlink($fileName);
		}
        
        $key = $this->getCacheFileName($path, 'meta');
        $this->xpdo->cacheManager->delete($key);
        
		// TODO: Need to be implemented
		return true;
	}

	/**
	 * Create an object from a path
	 * @param string $objectPath
	 * @param string $name
	 * @param string $content
	 * @return boolean|string
	 */
	public function createObject($objectPath, $name, $content)
	{
		$success = false;
        
        $path = $this->getPath($objectPath);
        
        
        
        $tempPath = $this->xpdo->getOption(xPDO::OPT_CACHE_PATH) . 'dropbox/' . uniqid() . '/';
    	if (!file_exists($tempPath)) {
			$this->xpdo->cacheManager->writeTree($tempPath);
		}
		
        
		$fileName = $tempPath . $name;
        
        if(!$this->xpdo->cacheManager->writeFile($fileName, $content)){
            $this->addError('file', $this->xpdo->lexicon('file_err_create'));
            return false;
        }
        
        $response = null;
		try {
			# $response = $this->getClient()->putFile($container, $fileName);
			$f = fopen($fileName, "rb");
            $response = $this->getClient()->uploadFile($path . '/' . $name, dbx\WriteMode::add(), $f);
            fclose($f);
		} catch (Exception $e) {
            $error = $this->lexicon('uploadObjectsToContainer', array(
    			'path' => $path,
				'message' => $e->getMessage(),
			), 'error');
			$this->xpdo->log(xPDO::LOG_LEVEL_ERROR, $error);
            $this->addError('file', $error);
            return $success;
		}
		@unlink($fileName);
		if ($response) {
			$success = true;
		}
        
        # 
        # die('public function createObject');
        
		return $success;
	}

	/**
	 * Remove an object
	 * @param string $objectPath
	 * @return boolean
	 */
	public function removeObject($objectPath)
	{
        $path = $this->getPath($objectPath);
        
		$response = null;
		try {
			$response = $this->getClient()->delete($path);
		} catch (Exception $e) {
			$this->xpdo->log(xPDO::LOG_LEVEL_ERROR, $this->lexicon('removeObject', array(
				'path' => $path,
				'message' => $e->getMessage(),
			), 'error'));
			$this->addError('file', $this->xpdo->lexicon('file_err_remove'));
			return false;
		}
		if (!$response) {
			$this->addError('file', $this->xpdo->lexicon('file_err_remove'));
			return false;
		}
		$this->xpdo->logManagerAction('file_remove', '', $path);
		return true;
	}

	/**
	 * Rename a file/object
	 * @param string $oldPath
	 * @param string $newName
	 * @return bool
	 */
	public function renameObject($oldPath, $newName)
	{
		$response = null;
		try {
			$response = $this->getClient()->move($this->getPath($oldPath), $this->getPath(dirname($oldPath) . '/' . $newName));
		} catch (Exception $e) {
			$this->xpdo->log(xPDO::LOG_LEVEL_ERROR, $this->lexicon('removeObject', array(
				'path' => $oldPath,
				'name' => $newName,
				'message' => $e->getMessage(),
			), 'error'));
			$this->addError('name', $this->xpdo->lexicon('file_folder_err_rename'));
			return false;
		}
		if (!$response) {
			$this->addError('name', $this->xpdo->lexicon('file_folder_err_rename'));
			return false;
		}
		$this->xpdo->logManagerAction('file_rename', '', $oldPath);
		return true;
	}

	/**
	 * Get the URL for an object in this source
	 * @param string $object
	 * @return string
	 */
	public function getObjectUrl($object = '')
	{
		return $this->xpdo->getOption('url_scheme', null, MODX_URL_SCHEME) . $this->xpdo->getOption('http_host', null, MODX_HTTP_HOST) . $this->xpdo->getOption('base_url', MODX_BASE_URL) . ($object == '' ? '' : $this->getUrl($object));
	}

	/**
	 * Prepares the output URL when the Source is being used in an Element
	 * @param string $value
	 * @return string
	 */
	public function prepareOutputUrl($value)
	{
		return $this->getObjectUrl($value);
	}

	/**
	 * Move a file or folder to a specific location
	 * @param string $from The location to move from
	 * @param string $to The location to move to
	 * @param string $point The type of move; append, above, below
	 * @return boolean
	 */
	public function moveObject($from, $to, $point = 'append')
	{
		$response = null;
		try {
			$response = $this->getClient()->move($this->getPath($from), $this->getPath($to . '/' . $this->basename($from)));
		} catch (Exception $e) {
			$this->xpdo->log(xPDO::LOG_LEVEL_ERROR, $this->lexicon('moveObject', array(
				'path' => $from,
				'name' => $to,
				'message' => $e->getMessage(),
			), 'error'));
			return false;
		}
		return (bool)$response;
	}

	/**
	 * Get the name of this source type
	 * @return string
	 */
	public function getTypeName()
	{
		return $this->lexicon('name');
	}

	/**
	 * Get a short description of this source type
	 * @return string
	 */
	public function getTypeDescription()
	{
		return $this->lexicon('description');
	}
 

	/**
	 * Get the default properties for this source. Override this in your custom source driver to provide custom
	 * properties for your source type.
	 * @return array
	 */
	public function getDefaultProperties()
	{ 
          
		$properties = array(
			'consumerKey' => array(
				'name' => 'consumerKey',
				'type' => 'password',
				'options' => '',
				'value' => '',
			),
			'consumerSecret' => array(
				'name' => 'consumerSecret',
				'type' => 'password',
				'options' => '',
				'value' => '',
			), 
			'authToken' => array(
				'name' => 'authToken',
				'type' => 'password',
				'options' => '',
				'value' => '',
			),
			'cacheable' => array(
				'name' => 'cacheable',
				'type' => 'combo-boolean',
				'options' => '',
				'value' => '1',
			),
			'skipFiles' => array(
				'name' => 'skipFiles',
				'type' => 'textfield',
				'options' => '',
				'value' => '.dropbox,.svn,.git,_notes,nbproject,.idea,.DS_Store',
			),
		);
		foreach ($properties as $key => $property) {
			$properties[$key]['desc'] = 'dropbox.prop.' . $property['name'];
			$properties[$key]['lexicon'] = 'dropbox:properties';
		} 
        
		return $properties;
	}

	public function getListContextMenu($isDir, array $fileArray)
	{
		$canSave = $this->checkPolicy('save');
		$canRemove = $this->checkPolicy('remove');
		$canCreate = $this->checkPolicy('create');
		$canView = $this->checkPolicy('view');
		$menu = array();
		if ($isDir) {
			if ($this->hasPermission('directory_create') && $canCreate) {
				$menu[] = array(
					'text' => $this->xpdo->lexicon('file_folder_create_here'),
					'handler' => 'this.createDirectory',
				);
			}
			if ($this->hasPermission('directory_update') && $canSave) {
				$menu[] = array(
					'text' => $this->xpdo->lexicon('rename'),
					'handler' => 'this.renameDirectory',
				);
			}
			$menu[] = array(
				'text' => $this->xpdo->lexicon('directory_refresh'),
				'handler' => 'this.refreshActiveNode',
			);
			if ($this->hasPermission('file_upload') && $canCreate) {
				$menu[] = '-';
				$menu[] = array(
					'text' => $this->xpdo->lexicon('upload_files'),
					'handler' => 'this.uploadFiles',
				);
			}
			if ($this->hasPermission('directory_remove') && $canRemove) {
				$menu[] = '-';
				$menu[] = array(
					'text' => $this->xpdo->lexicon('file_folder_remove'),
					'handler' => 'this.removeDirectory',
				);
			}
		} else {
			if ($this->hasPermission('file_update') && $canSave) {
                if (!empty($fileArray['page'])) {
                    $menu[] = array(
                        'text' => $this->xpdo->lexicon('file_edit'),
                        'handler' => 'this.editFile',
                    );
                    $menu[] = array(
                        'text' => $this->xpdo->lexicon('quick_update_file'),
                        'handler' => 'this.quickUpdateFile',
                    );
                }
                $menu[] = array(
                    'text' => $this->xpdo->lexicon('rename'),
                    'handler' => 'this.renameFile',
                );
			}
			if ($this->hasPermission('file_view') && $canView) {
				$menu[] = array(
					'text' => $this->xpdo->lexicon('file_download'),
					'handler' => 'this.downloadFile',
				);
			}
			if ($this->hasPermission('file_remove') && $canRemove) {
				if (!empty($menu)) {
					$menu[] = '-';
				}
				$menu[] = array(
					'text' => $this->xpdo->lexicon('file_remove'),
					'handler' => 'this.removeFile',
				);
			}
		}
		return $menu;
	}

	/**
	 * Prepare the source path for phpThumb
	 * @param string $src
	 * @return string
	 */
	public function prepareSrcForThumb($src)
	{
        $charset = $this->xpdo->getOption('charset', 'utf-8');
		if (mb_strpos($src, $this->getUniqueKey() . '/thumbnails', null, $charset) !== false) {
			return $src;
		} else {
			if (mb_strpos($src, $this->getConnectorUrl(), null, $charset) === false) {
				$src = $this->xpdo->getOption('url_scheme', null, MODX_URL_SCHEME) . $this->xpdo->getOption('http_host', null, MODX_HTTP_HOST) . $this->ctx->getOption('base_url', MODX_BASE_URL) . $this->getUrl($src);
			}
		}
		return $src;
	} 

	public function getContent($path)
	{
        
        if(!$meta = $this->getObjectContents($path)){
            return;
        }
        
        // else
        return $meta['content'];
	}

    /**
	 * Get the contents of an object
	 * @param string $objectPath
	 * @return boolean
	 */
	public function getObjectContents($objectPath)
	{
        
        # return $this->getContent($objectPath);
        
        
        $path = $this->getPath($objectPath);
        
        if(!$path || $path == '/'){
            return;
        }
        
        $meta = null;
        $content = null;
        
        $cacheable = $this->getProperty('cacheable');
        
        
         
            
        if($cacheable){
            $key = $this->getCacheFileName($path, 'meta');
        	
        	$fileName = $this->getContentCacheFilename($path);
            
            if($meta = $this->xpdo->cacheManager->get($key)){
        		if (file_exists($fileName)){
        			$content = file_get_contents($fileName);
        		}
                $meta['content'] = $content;
            }
        }
        
        if(!$meta){ 
    		try {
                $fd = tmpfile();
    			$response = $this->getClient()->getFile($path, $fd); 
    		} catch (Exception $e) {
    			$this->xpdo->log(xPDO::LOG_LEVEL_ERROR, $this->lexicon('getContent', array(
    				'path' => $path,
    				'message' => $e->getMessage(),
    			), 'error'));
                return false;
    		}
            
            // Check response exists
            if(!$response){
                $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, $this->lexicon('getContent', array(
        			'path' => $path
    			), 'error'));
                return false;
            }
            
            # print_r($response);
            # 
            # exit;
            
            fseek($fd, 0);
            $content = fread($fd, $response['bytes']);
            fclose($fd);  
            
            $properties = $this->getPropertyList();
            $imageExtensions = $this->getOption('imageExtensions', $properties, 'jpg,jpeg,png,gif');
    		$imageExtensions = explode(',', $imageExtensions);
    		$fileExtension = pathinfo($path, PATHINFO_EXTENSION);
            
            $meta = array(
        		'name' => $path,
    			'basename' => $this->basename($path),
    			'path' => $path,
    			'size' => $response['bytes'],
    			'last_accessed' => '',
    			'last_modified' => $response['modified'], 
                'content'       => '',
    			'image' => in_array($fileExtension, $imageExtensions) ? true : false,
    			'is_writable' => $this->fileIsWritable($response),
    			'is_readable' => true,
    		);
            
    		if ($cacheable) {
    			$this->xpdo->cacheManager->set($key, $meta);
                
                if(!empty($content)){
        			$this->xpdo->cacheManager->writeFile($fileName, $content);
                }
    		}  
            
            $meta['content'] = $content; 
        }
         
		return $meta;
	}

	public function getThumbnail($path)
	{
		$fileName = $this->getCacheFileName($path);
		$filePath = $this->xpdo->getOption('assets_path', null, MODX_ASSETS_PATH) . $fileName;
		if (file_exists($filePath)) {
			return $fileName;
		}
		$content = '';
		try {
			$result = $this->getClient()->getThumbnail($this->getPath($path), self::THUMBNAIL_FORMAT_JPG, self::THUMBNAIL_SIZE_LARGE);
		} catch (Exception $e) {
			$this->xpdo->log(xPDO::LOG_LEVEL_ERROR, $this->lexicon('getThumbnail', array(
				'path' => $path,
				'message' => $e->getMessage(),
			), 'error'));
		}
        
		if ($result AND $content = $result[1]) {
			if ($this->xpdo->cacheManager->writeFile($filePath, $content)) {
				return $fileName;
			}
		}
		return '';
	}

	public function getContentType($ext)
	{
		$contentType = 'application/octet-stream';
		$mimeTypes = array(
			'323' => 'text/h323',
			'acx' => 'application/internet-property-stream',
			'ai' => 'application/postscript',
			'aif' => 'audio/x-aiff',
			'aifc' => 'audio/x-aiff',
			'aiff' => 'audio/x-aiff',
			'asf' => 'video/x-ms-asf',
			'asr' => 'video/x-ms-asf',
			'asx' => 'video/x-ms-asf',
			'au' => 'audio/basic',
			'avi' => 'video/x-msvideo',
			'axs' => 'application/olescript',
			'bas' => 'text/plain',
			'bcpio' => 'application/x-bcpio',
			'bin' => 'application/octet-stream',
			'bmp' => 'image/bmp',
			'c' => 'text/plain',
			'cat' => 'application/vnd.ms-pkiseccat',
			'cdf' => 'application/x-cdf',
			'cer' => 'application/x-x509-ca-cert',
			'class' => 'application/octet-stream',
			'clp' => 'application/x-msclip',
			'cmx' => 'image/x-cmx',
			'cod' => 'image/cis-cod',
			'cpio' => 'application/x-cpio',
			'crd' => 'application/x-mscardfile',
			'crl' => 'application/pkix-crl',
			'crt' => 'application/x-x509-ca-cert',
			'csh' => 'application/x-csh',
			'css' => 'text/css',
			'dcr' => 'application/x-director',
			'der' => 'application/x-x509-ca-cert',
			'dir' => 'application/x-director',
			'dll' => 'application/x-msdownload',
			'dms' => 'application/octet-stream',
			'doc' => 'application/msword',
			'dot' => 'application/msword',
			'dvi' => 'application/x-dvi',
			'dxr' => 'application/x-director',
			'eps' => 'application/postscript',
			'etx' => 'text/x-setext',
			'evy' => 'application/envoy',
			'exe' => 'application/octet-stream',
			'fif' => 'application/fractals',
			'flr' => 'x-world/x-vrml',
			'gif' => 'image/gif',
			'gtar' => 'application/x-gtar',
			'gz' => 'application/x-gzip',
			'h' => 'text/plain',
			'hdf' => 'application/x-hdf',
			'hlp' => 'application/winhlp',
			'hqx' => 'application/mac-binhex40',
			'hta' => 'application/hta',
			'htc' => 'text/x-component',
			'htm' => 'text/html',
			'html' => 'text/html',
			'htt' => 'text/webviewhtml',
			'ico' => 'image/x-icon',
			'ief' => 'image/ief',
			'iii' => 'application/x-iphone',
			'ins' => 'application/x-internet-signup',
			'isp' => 'application/x-internet-signup',
			'jfif' => 'image/pipeg',
			'jpe' => 'image/jpeg',
			'jpeg' => 'image/jpeg',
			'jpg' => 'image/jpeg',
			'js' => 'application/x-javascript',
			'latex' => 'application/x-latex',
			'lha' => 'application/octet-stream',
			'lsf' => 'video/x-la-asf',
			'lsx' => 'video/x-la-asf',
			'lzh' => 'application/octet-stream',
			'm13' => 'application/x-msmediaview',
			'm14' => 'application/x-msmediaview',
			'm3u' => 'audio/x-mpegurl',
			'man' => 'application/x-troff-man',
			'mdb' => 'application/x-msaccess',
			'me' => 'application/x-troff-me',
			'mht' => 'message/rfc822',
			'mhtml' => 'message/rfc822',
			'mid' => 'audio/mid',
			'mny' => 'application/x-msmoney',
			'mov' => 'video/quicktime',
			'movie' => 'video/x-sgi-movie',
			'mp2' => 'video/mpeg',
			'mp3' => 'audio/mpeg',
			'mpa' => 'video/mpeg',
			'mpe' => 'video/mpeg',
			'mpeg' => 'video/mpeg',
			'mpg' => 'video/mpeg',
			'mpp' => 'application/vnd.ms-project',
			'mpv2' => 'video/mpeg',
			'ms' => 'application/x-troff-ms',
			'mvb' => 'application/x-msmediaview',
			'nws' => 'message/rfc822',
			'oda' => 'application/oda',
			'p10' => 'application/pkcs10',
			'p12' => 'application/x-pkcs12',
			'p7b' => 'application/x-pkcs7-certificates',
			'p7c' => 'application/x-pkcs7-mime',
			'p7m' => 'application/x-pkcs7-mime',
			'p7r' => 'application/x-pkcs7-certreqresp',
			'p7s' => 'application/x-pkcs7-signature',
			'pbm' => 'image/x-portable-bitmap',
			'pdf' => 'application/pdf',
			'pfx' => 'application/x-pkcs12',
			'pgm' => 'image/x-portable-graymap',
			'pko' => 'application/ynd.ms-pkipko',
			'pma' => 'application/x-perfmon',
			'pmc' => 'application/x-perfmon',
			'pml' => 'application/x-perfmon',
			'pmr' => 'application/x-perfmon',
			'pmw' => 'application/x-perfmon',
			'pnm' => 'image/x-portable-anymap',
			'pot' => 'application/vnd.ms-powerpoint',
			'ppm' => 'image/x-portable-pixmap',
			'pps' => 'application/vnd.ms-powerpoint',
			'ppt' => 'application/vnd.ms-powerpoint',
			'prf' => 'application/pics-rules',
			'ps' => 'application/postscript',
			'pub' => 'application/x-mspublisher',
			'qt' => 'video/quicktime',
			'ra' => 'audio/x-pn-realaudio',
			'ram' => 'audio/x-pn-realaudio',
			'ras' => 'image/x-cmu-raster',
			'rgb' => 'image/x-rgb',
			'rmi' => 'audio/mid',
			'roff' => 'application/x-troff',
			'rtf' => 'application/rtf',
			'rtx' => 'text/richtext',
			'scd' => 'application/x-msschedule',
			'sct' => 'text/scriptlet',
			'setpay' => 'application/set-payment-initiation',
			'setreg' => 'application/set-registration-initiation',
			'sh' => 'application/x-sh',
			'shar' => 'application/x-shar',
			'sit' => 'application/x-stuffit',
			'snd' => 'audio/basic',
			'spc' => 'application/x-pkcs7-certificates',
			'spl' => 'application/futuresplash',
			'src' => 'application/x-wais-source',
			'sst' => 'application/vnd.ms-pkicertstore',
			'stl' => 'application/vnd.ms-pkistl',
			'stm' => 'text/html',
			'svg' => 'image/svg+xml',
			'sv4cpio' => 'application/x-sv4cpio',
			'sv4crc' => 'application/x-sv4crc',
			't' => 'application/x-troff',
			'tar' => 'application/x-tar',
			'tcl' => 'application/x-tcl',
			'tex' => 'application/x-tex',
			'texi' => 'application/x-texinfo',
			'texinfo' => 'application/x-texinfo',
			'tgz' => 'application/x-compressed',
			'tif' => 'image/tiff',
			'tiff' => 'image/tiff',
			'tr' => 'application/x-troff',
			'trm' => 'application/x-msterminal',
			'tsv' => 'text/tab-separated-values',
			'txt' => 'text/plain',
			'uls' => 'text/iuls',
			'ustar' => 'application/x-ustar',
			'vcf' => 'text/x-vcard',
			'vrml' => 'x-world/x-vrml',
			'wav' => 'audio/x-wav',
			'wcm' => 'application/vnd.ms-works',
			'wdb' => 'application/vnd.ms-works',
			'wks' => 'application/vnd.ms-works',
			'wmf' => 'application/x-msmetafile',
			'wps' => 'application/vnd.ms-works',
			'wri' => 'application/x-mswrite',
			'wrl' => 'x-world/x-vrml',
			'wrz' => 'x-world/x-vrml',
			'xaf' => 'x-world/x-vrml',
			'xbm' => 'image/x-xbitmap',
			'xla' => 'application/vnd.ms-excel',
			'xlc' => 'application/vnd.ms-excel',
			'xlm' => 'application/vnd.ms-excel',
			'xls' => 'application/vnd.ms-excel',
			'xlt' => 'application/vnd.ms-excel',
			'xlw' => 'application/vnd.ms-excel',
			'xof' => 'x-world/x-vrml',
			'xpm' => 'image/x-xpixmap',
			'xwd' => 'image/x-xwindowdump',
			'z' => 'application/x-compress',
			'zip' => 'application/zip'
		);
		if (isset($mimeTypes[strtolower($ext)])) {
			$contentType = $mimeTypes[$ext];
		}
		return $contentType;
	}

	protected function getConnectorUrl()
	{
		if ($this->connectorUrl == null) {
			$id = $this->get('id');
			if ($id <= 0) {
				$id = $this->get('source');
			}
    		$this->connectorUrl = $this->ctx->getOption('assets_url', MODX_ASSETS_URL) . 'components/dropbox/connector.php?source=' . $id;
		}
		return $this->connectorUrl;
	}

	protected function getUrl($path)
	{
		return $this->getConnectorUrl() . '&' . http_build_query(array(
			'action' => 'web/view',
			'path' => $path,
		), '', '&');
	}

	protected function getCacheFileName($path, $type = 'thumbnail')
	{
        $prefix = 'components/dropbox/';
		if ($type == 'thumbnail') {
            $prefix .= "cache/{$type}/";
    		$ext = 'jpg';
		}
        else{
			# $ext = @pathinfo($path, PATHINFO_EXTENSION);
			# if (empty($ext)) {
			# 	$ext = 'bin';
			# }
            $ext = 'txt';
        }
  
        
        # die($path);
        
		$hash = md5(trim($path, '/'));
		return $prefix . $this->getUniqueKey() . '/' . $hash . '_' .$type . '.' . $ext;
		# return 'components/dropbox/' . $this->getUniqueKey() . '/' . $type . 's/' . substr($hash, 0, 2) . '/' . $hash . '.' . $ext;
	}

	protected function lexicon($key, array $params = array(), $category = 'source')
	{
		return $this->xpdo->lexicon('dropbox.' . $category . '.' . $key, $params);
	}
 
    
    public function getUniqueKey()
	{
        return $this->get('id');
		# $token = $this->getProperty('authToken');
		# return md5($token);
	}
    
    
    protected function basename($param, $suffix=null){
        $charset = $this->xpdo->getOption('charset', 'utf-8');
        if ( $suffix ) { 
            $tmpstr = ltrim(mb_substr($param, mb_strrpos($param, DIRECTORY_SEPARATOR, null, $charset), null, $charset), DIRECTORY_SEPARATOR); 
            if ( (mb_strpos($param, $suffix, null, $charset)+mb_strlen($suffix, $charset) )  ==  mb_strlen($param, $charset) ) { 
                return str_ireplace( $suffix, '', $tmpstr); 
            } else { 
                return ltrim(mb_substr($param, mb_strrpos($param, DIRECTORY_SEPARATOR, null, $charset), null, $charset), DIRECTORY_SEPARATOR); 
            } 
        } else { 
            return ltrim(mb_substr($param, mb_strrpos($param, DIRECTORY_SEPARATOR, null, $charset), null, $charset), DIRECTORY_SEPARATOR); 
        }
    }

    /**
     * Get the ID of the edit file action
     *
     * @return boolean|int
     */
    public function getEditActionId() {
        $editAction = false;
        /** @var modAction $act */
        $act = $this->xpdo->getObject('modAction',array('controller' => 'system/file/edit'));
        if ($act) { $editAction = $act->get('id'); }
        return $editAction;
    }
    
    
    protected function fileIsWritable($entry){
        return mb_strpos($entry['mime_type'], 'text/', null, $this->xpdo->getOption('charset', 'utf-8')) === 0;
    }
    
    
    public function getPath($path){
        
        return "/" . trim($path, '/ ');
    }
    
    
    protected function getContentCacheFilename($path){
        return $this->xpdo->getOption(xPDO::OPT_CACHE_PATH) .'default/' . $this->getCacheFileName($path, 'content');
    }    
    
    
    public function getRevisions($path, $limit = null){
        $result = null;
        
        if($client = & $this->getClient()){
            $result = $client->getRevisions($path, $limit);
        }
        
        return $result;
    }
    
    
    public function restoreFile($path, $rev){
        $result = null;
        
        if($client = & $this->getClient()){
            $result = $client->restoreFile($path, $rev);
        }
        
        return $result;
    }
    
    public function getLastCursor($pathPrefix = '/'){
        $result = null;
        
        if(!$client = $this->getClient()){
            return $result;
        }
        
        return $client->getLastCursor($pathPrefix);
    }
    
}
