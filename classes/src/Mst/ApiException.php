<?php

namespace Mst;

class ApiException extends \EccException {
    
    public function __construct($message, $code = 0, $additional_details = []) {
        parent::__construct($message, $code, $additional_details);
    }
}