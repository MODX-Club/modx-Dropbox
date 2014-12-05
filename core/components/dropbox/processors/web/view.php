<?php
$content = '';
$sourceId = intval($modx->getOption('source', $scriptProperties, 0));
$path = trim($modx->getOption('path', $scriptProperties, '/'));



if ($sourceId > 0 && !empty($path)) {
	/**
	 * @var dropboxMediaSource $source
	 */
     
    # $modx->switchContext('mgr');
     
	$source = $modx->getObject('sources.modMediaSource', $sourceId);
	if (!empty($source)) {
		if ($source->initialize()) {
			$content = $source->getContent($path);
		}
	}
	if (!empty($content)) {
		$ext = @pathinfo($path, PATHINFO_EXTENSION);
		header('Content-Disposition: attachment; filename=' . rawurlencode(basename($path)));
		header('Content-Type: ' . $source->getContentType($ext));
	}
}
return $content;