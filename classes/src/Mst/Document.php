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
    
    public function get_n_comments($id) {
        
        $sql = "SELECT count(*) 
                FROM comm c
                INNER JOIN speech_par sp ON sp.id_p = c.id_p
                WHERE sp.id_speech = ?
                ";
        
        return $this->vmsql->get_item($sql, [$id]);
    }
    
    public function select($id, $fields = '*') {
        
        $data = parent::select($id, $fields);
        if(!empty($data)) {
            $data['n_comm'] = $this->get_n_comments($id);
        }
        
        return $data;
    }
    
}