<?php

abstract class EccExc extends Exception {
    
    const FILE_ERRORLOG_RUNTIME = '/log/runtime.log';
    
    public $additional_details=array();
    protected $logger_name = 'ecc_logger';
    protected $logfile;
    protected $backtrace = false;


    public function __construct($message, $code=0) {
        
        parent::__construct($message, $code);
    }
    
    public function setLog($level=null, $force_mail=false){
        
        return $this->_setLog($level, $force_mail);
    }
    
    public function enable_backtrace(){
        $this->backtrace = true;
    }
    
    public function disable_backtrace(){
        $this->backtrace = false;
    }
    
    protected function _setLog($_level=null, $force_mail=false, $backtrace=false){
        
        // Default log level
        $level = (empty($_level)) ? \Monolog\Logger::WARNING : $_level;
        
        // Default log file
        if($this->logfile===null || !is_writable(dirname($this->logfile))){
            $logfile = FRONT_ROOT. self::FILE_ERRORLOG_RUNTIME;
        }
        else {
            $logfile = $this->logfile;
        }
        
        // add records to the log
        $message = $this->code . "\t".$this->message;
        
        $file = (isset($this->additional_details['file'])) ? $this->additional_details['file']:$this->file;
        $line = (isset($this->additional_details['line'])) ? $this->additional_details['line']:$this->line;
        
        
        $details = array(
           'file' =>   $file.":".$line,
           'user' => -1,
        );
        
        if(isset($_SERVER['REQUEST_URI'])){
            $details['request_uri']=$_SERVER['REQUEST_URI'];
        }
        
        if($this->backtrace){
            $details['backtrace']=debug_backtrace();
        }
        
        $_details = $details + $this->additional_details;
        
        $log = new \Monolog\Logger($this->logger_name);
        $log->pushHandler(new \Monolog\Handler\StreamHandler($logfile, $level));
        $log->addRecord($level, $message, $_details);
        
        
        // Se >= a WARNING o se forzato, invia anche una mail
        if($level >= \Monolog\Logger::WARNING || $force_mail){
            
            /*
            $mailer = new ExrsMailer();
            $formatter = new \Monolog\Formatter\HtmlFormatter();
            
            $handler=new Exrs_Handler($mailer, $level);
            $handler->setFormatter($formatter);
            
            $log2 = new \Monolog\Logger($this->logger_name);
            $log2->pushHandler($handler);
            $log2->addRecord($level, $message, $_details);
            */
            Common::send_500($this->getCode());
        }
        
    }
}
