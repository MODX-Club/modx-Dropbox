<?php
class DropboxObject extends xPDOSimpleObject {
    
    
    /*
        Get MediaSource
    */
    public function getSource(){
        if($this->Source){
            if(!$this->Source->initialize()){
                return false;
            }
        }
        return $this->Source;
    }
    
    /*
        Get Dropbox client for current MediaSource
    */
    public function getClient(){
        if($source = $this->getSource()){
            return $source->getClient();
        }
        return null;
    }
    
    
    public function save($cacheFlag= null) {
        
        if(!$this->Source){
            $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, 'Attempt to save object without MediaSource: ' . print_r($this->toArray('', true), 1));
            return false;
        }
        
        $this->path = $this->Source->getPath($this->path);
        
        return parent::save($cacheFlag);
    }
    
}