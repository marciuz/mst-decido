<?php

namespace Mst;

class Channel extends EntityMst {
    
    public function __construct() {
        parent::__construct();
        
        $this->pk = 'id_ch';
        $this->nometabella = 'ch';
    }
    
    public function get($campi = ['*'], $where = '', $params = [], $orderby = '', $limit = 0, $offset = 0) {
        
        $cc = implode(",", $campi);
        
        $orderby = ($orderby == '') ? 'id_ch' : $orderby;
        
        $sql = "SELECT $cc , count(*) n_docs 
            FROM $this->nometabella ch
            LEFT JOIN speech s ON s.id_ch = ch.id_ch
            WHERE 1=1 
            $where
            GROUP BY id_ch 
            ORDER BY $orderby 
            ";
        
        return $this->vmsql->get($sql,$params);
        
    }
    
    
    /**
     * Get the number of docs.
     *
     * @param int $id_ch
     * @return int
     */
    public function get_n_docs($id_ch) {
        
        $sql = "SELECT count(*) FROM speech WHERE id_ch=?";
        $n = $this->vmsql->get_item($sql, [$id_ch]);
        
        return $n;
    }
    
    
    /**
     * Check if the channel is empty.
     *
     * @param int $id_ch
     * @return bool
     */
    public function is_empty($id_ch) {
        
        return ($this->get_n_docs($id_ch) == 0);
    }
    
    public function check_owner($ch_id, $author_id) {
        return true;
    }
    
    public function select($id) {
        $data = parent::select($id);
        $data['n_docs'] = $this->get_n_docs($id);
        
        return $data;
    }
    
    public function ins($arr) {
        
        try {
            $this->mandatory_fields($arr, [
                'chname',
                'title',
                'author',
            ]);
            
            $parsed = [
                'chname' => (string) preg_replace("/[\W]+/", '_', $arr['chname']),
                'title' => (string) $arr['title'],
                'author' => (int) $arr['author'],
                'description' => (int) $arr['description'],
                'creation' => date(FRONT_DATETIME),
                'last_mod' => date(FRONT_DATETIME),
                'lang' => $arr['lang'] ?? 'en_US',
                'public' => $arr['public'] ?? 0,
            ];
            
            if(isset($arr['ch_id'])) {
                
                if(!$this->check_owner($arr['ch_id'], $arr['author'])) {
                    \Rpc::json_output([
                        'result' => false,
                        'error'=> 'Owner not match',
                        'id_ch' => $ch_id,
                    ], \HttpStatusCode::UNAUTHORIZED);
                }
                
                unset($parsed['creation']);
            }
            else{
                $parsed['ch_id'] = $parsed;
            }
        
            $ch_id = parent::ins($parsed);
            
            \Rpc::json_output([
                'result' => true,
                'error'=> null,
                'id_ch' => $ch_id,
            ], \HttpStatusCode::CREATED);
            
        }
        catch(\Mst\ApiException $e) {
            
            $e->setLog(\Monolog\Logger::INFO);
            
            \Rpc::json_output([
                'result' => false,
                'error'=> $e->getMessage(),
            ], \HttpStatusCode::BAD_REQUEST);
        }
    }
    
}