<?php

class EccPhpException extends EccExc {
    
    public function __construct($message, $code=0, $additional_details=array()) {
        $this->logger_name='php_logger';
        $this->logfile = FRONT_ROOT.'/log/php.log';
        $this->additional_details=$additional_details;
        parent::__construct($message, $code);
    }
}
