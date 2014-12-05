<?php
/**
 * @var modX $modx
 */

switch($modx->event->name){
    
    case 'OnSiteRefresh':
        
        if(!function_exists('cleanUpDirectory')){
            function cleanUpDirectory($path)
            {
            	if (!file_exists($path)) {
            		return;
            	}
            	$iterator = new RecursiveDirectoryIterator($path);
            	foreach (new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::CHILD_FIRST) as $file) {
            		if ($file->isDir()) {
            			@rmdir($file->getPathname());
            		} else {
            			@unlink($file->getPathname());
            		}
            	}
            }
        }
        
    	$cachePath = $modx->getOption('assets_path', null, MODX_ASSETS_PATH) . 'components/dropbox/cache';
    	if (file_exists($cachePath)) {
    	    cleanUpDirectory($cachePath);
    // 		foreach (new DirectoryIterator($cachePath) as $file) {
    // 			if (
    // 			    $file->isDir()
    // 			    AND !$file->isDot()
    // 			) {
    // 				cleanUpDirectory($file->getPathname());
    // 				// cleanUpDirectory($file->getPathname() );
    // 				// $modx->log(1, $file->getPathname());
    // 			}
    // 		}
    	}
        
        
        break;
        
    default:;
}
 
return;