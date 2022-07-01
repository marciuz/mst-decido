<?php

namespace Mst;

class Paragraph extends \Mst\EntityMst {
    
    public function __construct() {
        parent::__construct();
        $this->nometabella = 'speech_par';
        $this->pk = 'id_par';
    }
}