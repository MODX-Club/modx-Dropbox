<?php
$isWeb = isset($_REQUEST['action']) && strpos($_REQUEST['action'], 'web/') === 0;
if ($isWeb) {
	define('MODX_REQP', false);
}

require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/config.core.php';
require_once MODX_CORE_PATH . 'config/' . MODX_CONFIG_KEY . '.inc.php';
require_once MODX_CONNECTORS_PATH . 'index.php';

$corePath = $modx->getOption('dropbox.core_path', null, $modx->getOption('core_path') . 'components/dropbox/');

if ($isWeb) {
	$version = $modx->getVersionData();
	if ($modx->user->hasSessionContext($modx->context->get('key'))) {
		$_SERVER['HTTP_MODAUTH'] = $_SESSION['modx.' . $modx->context->get('key') . '.user.token'];
	} else {
		$_SESSION['modx.' . $modx->context->get('key') . '.user.token'] = 0;
		$_SERVER['HTTP_MODAUTH'] = 0;
	}
	$_REQUEST['HTTP_MODAUTH'] = $_SERVER['HTTP_MODAUTH'];
}
$path = $modx->getOption('processorsPath', null, $corePath . 'processors/');
$modx->request->handleRequest(array(
	'processors_path' => $path,
	'location' => '',
));
