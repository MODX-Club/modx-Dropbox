<?php

require_once dirname(__FILE__) . "/dropbox/lib/Dropbox/autoload.php";
use \Dropbox as dbx;

class DropboxClient extends dbx\Client{

    
    public function getLastCursor($pathPrefix = '/'){
        dbx\Path::checkArgOrNull("pathPrefix", $pathPrefix);

        $response = $this->doPost($this->host->getApi(), "1/delta/latest_cursor", array(
            "path_prefix" => $pathPrefix
        ));

        if ($response->statusCode !== 200) throw dbx\RequestUtil::unexpectedStatus($response);

        if($result = dbx\RequestUtil::parseResponseJson($response->body)){
            $result['path'] = $pathPrefix;
        }

        return $result;
    }
    
}
