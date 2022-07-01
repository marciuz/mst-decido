<?php

namespace Mst;

class Document extends EntityMst {
    
    public function __construct() {
        parent::__construct();
        
        $this->pk = 'id_speech';
        $this->nometabella = 'speech';
        $this->fk = 'id_ch';
    }
    
    public function ins($arr) {
        
        // rewrite the alias
        if(isset($arr['document_id'])) {
            $arr[$this->pk] = $arr['document_id'];
            unset($arr['document_id']);
        }
        
        $data = parent::ins($arr);
        return $data;
    } 
    
}