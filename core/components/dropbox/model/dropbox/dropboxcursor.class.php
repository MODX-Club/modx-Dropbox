<?php
$this->loadClass('DropboxObject');
 
class DropboxCursor extends DropboxObject {

    
    public function save($cacheFlag= null) {
        
        if(!$this->cursor){
            $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, 'Attempt to save object without cursor: ' . print_r($this->toArray('', true), 1));
            return false;
        } 
        
        return parent::save($cacheFlag);
    }

    public function getDelta(){
        
        if(
            // !$this->cursor
            // OR 
            !$client = $this->getClient()
        ){
            return null;
        } 
        
        return $client->getDelta($this->cursor, $this->path);
    }    


    public function getLast(){
        
        if(
            !$this->path
            OR !$client = $this->getClient()
        ){
            return null;
        } 
        
        return $client->getLastCursor($this->path);
    }    
    
}
