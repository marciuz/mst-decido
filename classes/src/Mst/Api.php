<?php

namespace Mst;

class Api {
    
    private $header_auth = 'X-Decido-Auth';
    
    private $author = null;
    
    /**
     * if the debug is 
     */
    const AUTH_REQUIRED = false;
    
    const CHECK_USER_OK = 1;
    const CHECK_USER_NOT_EXISTS = -1;
    const CHECK_USER_NOT_MATCHES = -2;
    const CHECK_USER_ROLE_NOT_MATCHES = -3;
    
    
    public function __construct() {
        
        if(self::AUTH_REQUIRED) {
            $auth = $this->get_authorization_data();
            if($auth === false) {
                
                \Rpc::json_output([
                    'error'=> 'An authorization token is required. Please check the documentation for details.'
                    ], \HttpStatusCode::UNAUTHORIZED);
            }
            else{
                $this->author = $auth['user_id'] ?? null;
            }
        }
    }
    
    
    
    public function jwt_encode($payload, $validity=3600) {
        
        $payload['iat'] = time();
        $payload['exp'] = time() + $validity;

        /**
         * IMPORTANT:
         * You must specify supported algorithms for your application. See
         * https://tools.ietf.org/html/draft-ietf-jose-json-web-algorithms-40
         * for a list of spec-compliant algorithms.
         */
        return \Firebase\JWT\JWT::encode($payload, JWT_KEY, 'HS256');
    }
    
    public function jwt_decode($jwt) {
       
        try{
            
            $decoded = \Firebase\JWT\JWT::decode($jwt, new \Firebase\JWT\Key(JWT_KEY, 'HS256'));
            return $decoded;
            
        } catch (\Firebase\JWT\BeforeValidException $e) {
            
            \Rpc::json_output(['error'=>$e->getMessage()], \HttpStatusCode::UNAUTHORIZED);
        }
        catch (\Firebase\JWT\ExpiredException $e) {
            
            \Rpc::json_output(['error'=>$e->getMessage()], \HttpStatusCode::UNAUTHORIZED);
        }
        catch (\Firebase\JWT\SignatureInvalidException $e) {
            
            \Rpc::json_output(['error'=>$e->getMessage()], \HttpStatusCode::BAD_REQUEST);
        }
        catch( \UnexpectedValueException $e) {
            
            \Rpc::json_output(['error'=>$e->getMessage()], \HttpStatusCode::BAD_REQUEST);
        }
        
    }
    
    
    public function get_authorization_data() {
        
        $headers = getallheaders();
        
        if(isset($headers[$this->header_auth]) && !empty($headers[$this->header_auth])) {
            
            return $this->jwt_decode($headers[$this->header_auth]);
        }
        else {
            return false;
        }
    }
    
    
    
    
    /**
     * Get the autorized user of the call.
     *
     * @param string $userkey The index of the expected array
     * @return mixed
     */
    public function get_authorized_user($userkey='user_id') {
        
        $data = $this->get_authorization_data();
        return $data[$userkey] ?? null;
    }
    
    
    
    
    /**
     * Verify if the user in the authentication header is the user expected.
     *
     * @param type $expected_user
     * @param type $jwt_key
     * @return boolean
     */
    public function check_user($expected_user, $jwt_key='user_id') {
        
        $data_jwt = $this->get_authorization_data();
        
        if($data_jwt === false) {
            
        }
        else {
            
            if(isset($data_jwt[$jwt_key]) && $data_jwt[$jwt_key] == $expected_user) {
                return self::CHECK_USER_OK;
            }
            else if (!isset($data_jwt[$jwt_key])){
                return self::CHECK_USER_NOT_EXISTS;
            }
            else if ($data_jwt[$jwt_key] != $expected_user){
                return self::CHECK_USER_NOT_MATCHES;
            }
            else{
                return false;
            }
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
        
        self::send($res, \HttpStatusCode::OK);
    }
    
    public static function get_channel($id_ch) {
        
        $Channel = new Channel();
        $data = $Channel->select($id_ch);
        
        if(empty($data)) {
            self::send($data, \HttpStatusCode::NOT_FOUND);
        }
        else{
            self::send($data, \HttpStatusCode::OK);
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
            self::send($data, \HttpStatusCode::NOT_FOUND);
        }
        else {
            self::send($data, \HttpStatusCode::OK);
        }
    }
    
    public static function get_document($id_doc) {
        
        $Doc = new \Mst\Document();
        $data = $Doc->select($id_doc);
        
        $Par = new Paragraph();
        $data['pars'];
        
        $Par = new Comment();
        $data['pars'];
        
        if(empty($data)) {
            self::send($data, \HttpStatusCode::NOT_FOUND);
        }
        else {
            self::send($data, \HttpStatusCode::OK);
        }
    }
    
    public static function list_comments($id_doc) {
        
        $Comm = new \Mst\Comment();
        $Comm->get_comments($id_doc);
        
        if(empty($data)) {
            self::send([], \HttpStatusCode::NOT_FOUND);
        }
        else {
            self::send($data, \HttpStatusCode::OK);
        }
    }
    
    
    
    
    
    public static function create_channel() {
        
        $_data = self::_get_raw_data();
        
        $Ch = new Channel();
        $Ch->insert((array) $_data) ;
        
    }
    
    public static function create_document() {
        
        $res='to be implemented';
        self::send($res, \HttpStatusCode::NOT_IMPLEMENTED);
    }
    
    public static function update_channel($id_ch) {
        
        $_data = self::_get_raw_data();
        $_data['id_ch'] = intval($id_ch);
        
        $Ch = new Channel();
        $Ch->update((array) $_data) ;
    }
    
     public static function delete_channel($id_ch) {
         
        $that = new self();
        
        $Ch = new Channel();
        $Ch->set_author($that->author);
        
        $Ch->delete($id_ch);
        
    }
    
    
    public static function update_document($id_doc) {
        
        $res='to be implemented';
        self::send($res, \HttpStatusCode::NOT_IMPLEMENTED);
    }
    
    public static function update_comment($id_comm) {
        
        $res='to be implemented';
        self::send($res, \HttpStatusCode::NOT_IMPLEMENTED);
    }
    
   
    
    public static function delete_document($id_doc) {
        
        $res='to be implemented';
        self::send($res, \HttpStatusCode::NOT_IMPLEMENTED);
    }
    
    public static function delete_comment($id_comm) {
        
        $res='To be implemented';
        self::send($res, \HttpStatusCode::NOT_IMPLEMENTED);
    }
    
    /**
     * Parse the JSON in the document body
     *
     * @return array
     */
    private static function _get_raw_data() {
        
        $_data = json_decode(file_get_contents('php://input'), true);
        return $_data;
    }
    
    
    private static function send($result, $status_code = \HttpStatusCode::OK, $error=false) {
        
        $o = new \stdClass();
        $o->result = $result;
        $o->error = $error;
        
        $Api = new self();
        $o->debug_auth_data = $Api->get_authorization_data();
        
        \Rpc::json_output($o, $status_code);
        
    }
    
    
}