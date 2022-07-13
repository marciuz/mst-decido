<?php

namespace Mst;

class Comment extends \Mst\EntityMst {
    
    public function __construct() {
        parent::__construct();
        $this->nometabella = 'comm';
        $this->pk = 'id';
    }
    
    public function get_comments($id) {
        
        $sql = "SELECT *
                FROM comm c
                INNER JOIN speech_par sp ON sp.id_p = c.id_p
                WHERE sp.id_speech = ?
                ";
        
        return $this->vmsql->get_item($sql, [$id]);
    }
}