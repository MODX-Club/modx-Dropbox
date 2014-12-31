<?php
$this->loadClass('DropboxObject');

class DropboxEntry extends DropboxObject {
    
    
    public function get($k, $format = null, $formatTemplate= null) {
        
        switch($k){
            case 'content':
                if(!$this->content){
                    $this->content = $this->getContent();
                }
                break;
        }
        
        return parent::get($k, $format, $formatTemplate);
    }
    
     
    public function save($cacheFlag= null) {
        
        if($this->modified){
            $this->modified_time = strtotime($this->modified);
        }
        
        return parent::save($cacheFlag);
    }
    
     
    public function getContent(){
        
        if(!$source = $this->Source){
            $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, 'Attempt to get content without MediaSource: ' . print_r($this->toArray('', true), 1));
            return false;
        }
        
        if(!$this->path){
            $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, 'Attempt to get content without path: ' . print_r($this->toArray('', true), 1));
            return false;
        }
        
        if($this->is_dir){
            $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, 'Attempt to get content for directory: ' . print_r($this->toArray('', true), 1));
            return false;
        }
        
        return $source->getContent($this->path);
    }
    
    
    public function getRevisions( $limit = null){
        
        if(!$source = $this->Source){
            $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, 'Attempt to get revisions without MediaSource: ' . print_r($this->toArray('', true), 1));
            return false;
        }
        
        if(!$this->path){
            $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, 'Attempt to get revisions without path: ' . print_r($this->toArray('', true), 1));
            return false;
        }
        
        if($this->is_dir){
            $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, 'Attempt to get revisions for directory: ' . print_r($this->toArray('', true), 1));
            return false;
        }
        
        return $this->Source->getRevisions($this->path, $limit);
    }
    
    public function restoreFile($rev){
        $result = null;
        
        if($client = & $this->getClient()){
            $result = $client->restoreFile($this->path, $rev);
        }
        
        return $result;
    }
     
} 
