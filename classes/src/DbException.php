<?php

/**
 * Description of DbException
 *
 * @author marcello
 */
class DbException extends Exception {
    
    public $additional_details=array();
    protected $logger_name = 'ecc_logger';
    protected $logfile;
    protected $backtrace = false;
    protected $message;
    protected $code;
    
    public function __construct($message, $code=0, $additional_details=array()) {
        parent::__construct($message, $code);
    }
    
    public function enable_backtrace($status = true) {
        $this->backtrace = $status;
    }
    
    public function setLog($level=null, $force_mail=false){
        
        // here the logging... 
        print_r($this->additional_details);
    }
    
    public function redirect() {
        
        // here the redirect in HTML if needed.
    }
}
