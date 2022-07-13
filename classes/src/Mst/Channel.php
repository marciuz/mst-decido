<?php

namespace Mst;

class Channel extends EntityMst {
    
    public $author;
    
    public function __construct() {
        parent::__construct();
        
        $this->pk = 'id_ch';
        $this->nometabella = 'ch';
    }
    
    public function set_author($author){
        $this->author = $author;
    }
    
    /**
     * Override get method.
     * Get the fields with the inclusion of the count.
     * 
     * @param type $campi
     * @param type $where
     * @param type $params
     * @param type $orderby
     * @param type $limit
     * @param type $offset
     * @return type
     */
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
    
    /**
     * Check the owner of the record.
     *
     * @param type $ch_id
     * @param type $author_id
     * @return boolean
     */
    public function check_owner($ch_id, $author_id) {
        return true;
    }
    
    
    /**
     * Override the Entity::select() function.
     *
     * @param int $id
     * @param string $fields
     * @return array
     */
    public function select($id, $fields='*') {
        $data = parent::select($id, $fields);
        if(!empty($data)) {
            $data['n_docs'] = $this->get_n_docs($id);
        }
        
        return $data;
    }
    
    
    /**
     * Override the Entity::update() function.
     * 
     * @param string $arr
     */
    public function update($arr) {
        
        if(!$this->check_owner($arr['id_ch'], $arr['author'])) {

            \Rpc::json_output([
                'result' => false,
                'error'=> 'Owner not match',
                'error_details' => ['id_ch' => $arr['id_ch']],
            ], \HttpStatusCode::UNAUTHORIZED);
        }
        else if(!$this->record_exists($arr['id_ch'])) {

            \Rpc::json_output([
                'result' => false,
                'error'=> 'The record you\'re trying to update not exists.',
                'error_details' => ['id_ch' => $arr['id_ch']],
            ], \HttpStatusCode::NOT_ACCEPTABLE);
        }

        // The creation date not be updated
        if(isset($arr['creation'])) {
            unset($arr['creation']);
        }
        
        // Exec... 
        $ch_id = parent::update($arr);

        if($ch_id > 0) {

            \Rpc::json_output([
                'result' => [
                    'id_ch' => $ch_id,
                ],
                'error'=> null,
            ], \HttpStatusCode::OK);
        }
        else{
            
            \Rpc::json_output([
                'result' => [
                    'id_ch' => $ch_id,
                ],
                'error'=> 'Record not created/updated',
            ], \HttpStatusCode::BAD_REQUEST);
        }
    }
    
    /**
     * Override the Entity::insert() function.
     * 
     * @param array $arr
     */
    public function insert($arr) {
        
        if($this->chname_exists($arr['chname'])) {
            
            \Rpc::json_output([
                'result' => false,
                'error'=> 'Channel short name (chname) already exists. If you are trying to update the record, you must specify its id_ch in the call.',
            ], \HttpStatusCode::CONFLICT);
        }
        else{
            
            $this->_exec_insert($arr);
        }
    }
    
    /**
     * Override the Entity::delete() function.
     * 
     * @param int $id_ch
     */
    public function delete($id_ch) {
        
        $sql="SELECT count(*) FROM $this->nometabella WHERE id_ch=? ";
        
        $exists = (bool) $this->vmsql->get_item($sql . ' AND author=? ', [$id_ch, $this->author]);
        
        $id_exists = ($exists) ? true : (bool) $this->vmsql->get_item($sql, [$id_ch]);
        
        // Exist the recor with ID and AUTHOR
        if($exists) {
            
            $res = parent::delete($id_ch);
            
            \Rpc::json_output([
                'result' => $res,
                'error'=> false,
            ], \HttpStatusCode::OK);
        }
        else{
        
            // Exist the recor with ID and AUTHOR
            if($id_exists) {
                $msg = 'Impossible to delete the record, the author sent in the JWT (user_id) don\'t match in the record.';
                $http_code = \HttpStatusCode::UNAUTHORIZED;
            }
            else {
                $msg = 'I insist on refusing to delete a record that does not exist!';
                $http_code = \HttpStatusCode::NOT_ACCEPTABLE;
            }
            
            
            \Rpc::json_output([
                'result' => false,
                'error'=> $msg,
            ], $http_code);
        }
        
    }

    /**
     * Execute the insert actions.
     *
     * @param array $arr
     */
    private function _exec_insert($arr) {
        
        try {
            
            $parsed = [
                'chname' => (string) preg_replace("/[\W]+/", '_', $arr['chname']),
                'title' => (string) $arr['title'],
                'author' => (string) $arr['author'],
                'description' => (string) $arr['description'] ?? '',
                'introduction' => (string) $arr['introduction'] ?? '',
                'creation' => date(FRONT_DATETIME),
                'last_mod' => date(FRONT_DATETIME),
                'lang' => $arr['lang'] ?? 'en_US',
                'public' => $arr['public'] ?? 0,
                'logo' => $arr['logo'] ?? null,
            ];

            $parsed['lang'] = substr($parsed['lang'], 0, 5);

            // Case UPDATE
            $this->mandatory_fields($arr, [
                'chname',
                'title',
                'author',
            ]);

            // Exec... 
            $ch_id = parent::insert($parsed);

            if($ch_id > 0) {

                \Rpc::json_output([
                    'result' => [
                        'id_ch' => $ch_id,
                    ],
                    'error'=> null,
                ], \HttpStatusCode::CREATED);
            }
            else{
                \Rpc::json_output([
                    'result' => [
                        'id_ch' => $ch_id,
                    ],
                    'error'=> 'Record not created/updated',
                ], \HttpStatusCode::BAD_REQUEST);
            }

        }
        catch(\Mst\ApiException $e) {

            $e->setLog(\Monolog\Logger::INFO);

            \Rpc::json_output([
                'result' => false,
                'error'=> $e->getMessage(),
            ], \HttpStatusCode::BAD_REQUEST);
        }
    }

    /**
     * Search the unique chname in the table.
     *
     * @param string $chname
     * @return bool
     */
    private function chname_exists($chname) {
        $sql = "SELECT count(*) FROM ch WHERE chname=?";
        return (bool) $this->vmsql->get_item($sql, [$chname]);
    }
    
    
    /**
     * Search the ID of the channel in the table.
     *
     * @param int $id_ch
     * @return bool
     */
    private function record_exists($id_ch) {
        $sql = "SELECT count(*) FROM ch WHERE id_ch=?";
        return (bool) $this->vmsql->get_item($sql, [$id_ch]);
    }
}