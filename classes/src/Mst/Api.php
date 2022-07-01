<?php

namespace Mst;

class Api {
    
    
    public function jwt_encode($payload, $validity=3600) {
        
        $payload['iat'] = time();
        $payload['nbf'] = time() + $validity;

        /**
         * IMPORTANT:
         * You must specify supported algorithms for your application. See
         * https://tools.ietf.org/html/draft-ietf-jose-json-web-algorithms-40
         * for a list of spec-compliant algorithms.
         */
        return \Firebase\JWT\JWT::encode($payload, JWT_KEY, 'HS256');
    }
    
    public function jwt_decode($jwt) {
       
        $decoded = \Firebase\JWT\JWT::decode($jwt, new \Firebase\JWT\Key(JWT_KEY, 'HS256'));
        return $decoded;
    }
    
    
    public function get_authorization() {
        
        $headers = apache_request_headers();
        foreach($headers as $k => $value) {
            echo $k .'=>'. $value;
            echo "<br>";
        }
    }
    
    public static function list_channels() {
        
        $Chs = new \Mst\Channel();
        
        $fl = [
            'ch.id_ch',
            'ch.chname',
            'ch.title',
            'ch.introduction',
            'ch.description',
            'ch.author',
            'ch.creation',
            'ch.last_mod',
            'ch.lang',
            'ch.public',
            ];
        
        $res = $Chs->get($fl, '');
        
        \Rpc::json_output($res, \HttpStatusCode::OK);
    }
    
    public static function get_channel($id_ch) {
        
        $Channel = new Channel();
        $data = $Channel->select($id_ch);
        
        if(empty($data)) {
            \Rpc::json_output($data, \HttpStatusCode::NOT_FOUND);
        }
        else{
            \Rpc::json_output($data, \HttpStatusCode::OK);
        }
    }
    
    /**
     * 
     * @param type $id_ch
     * @todo check ownership
     */
    public static function list_documents($id_ch) {
        
        // Check ownership
        
        $Doc = new \Mst\Document();
        $data = $Doc->get(['*'], 'id_ch=?', [$id_ch]);
        
        if(empty($data)) {
            \Rpc::json_output($data, \HttpStatusCode::NOT_FOUND);
        }
        else {
            \Rpc::json_output($data, \HttpStatusCode::OK);
        }
    }
    
    public static function get_document($id_doc) {
        
        $Doc = new \Mst\Document();
        $data = $Doc->select($id_doc);
        
        $Par = new Paragraph();
        $data['pars'];
        
        if(empty($data)) {
            \Rpc::json_output($data, \HttpStatusCode::NOT_FOUND);
        }
        else {
            \Rpc::json_output($data, \HttpStatusCode::OK);
        }
    }
    
    public static function create_channel($_data) {
        
        $res='to be implemented';
        \Rpc::json_output($res, \HttpStatusCode::NOT_IMPLEMENTED);
    }
    
    public static function create_document() {
        
        $res='to be implemented';
        \Rpc::json_output($res, \HttpStatusCode::NOT_IMPLEMENTED);
    }
    
    public static function update_channel($id_ch) {
        
        $res='to be implemented';
        \Rpc::json_output($res, \HttpStatusCode::NOT_IMPLEMENTED);
    }
    
    
    public static function update_document($id_doc) {
        
        $res='to be implemented';
        \Rpc::json_output($res, \HttpStatusCode::NOT_IMPLEMENTED);
    }
    
    public static function update_comment($id_comm) {
        
        $res='to be implemented';
        \Rpc::json_output($res, \HttpStatusCode::NOT_IMPLEMENTED);
    }
    
    public static function delete_channel($id_ch) {
        
        // check if the channel is empty
        
        $res='to be implemented';
        \Rpc::json_output($res, \HttpStatusCode::NOT_IMPLEMENTED);
    }
    
    public static function delete_document($id_doc) {
        
        $res='to be implemented';
        \Rpc::json_output($res, \HttpStatusCode::NOT_IMPLEMENTED);
    }
    
    public static function delete_comment($id_comm) {
        
        $res='to be implemented';
        \Rpc::json_output($res, \HttpStatusCode::NOT_IMPLEMENTED);
    }
    
    /**
     * Parse the JSON in the document body
     *
     * @return array
     */
    private function _get_raw_data() {
        
        $_data = json_decode(file_get_contents('php://input'), true);
        return $_data;
    }
    
    
}