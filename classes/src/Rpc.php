<?php

class Rpc {
    
    static public function json_output($ooo, $response_http_code=200, $json_encode=true){
        
        if(defined('DEBUG_EXEC_STATS') && DEBUG_EXEC_STATS && is_object($ooo)){
            $ooo->debug_performance=new stdClass();
            $ooo->debug_performance->memory_peak = round(memory_get_peak_usage(false) / 1024 / 1024, 3)." Mb";
            $ooo->debug_performance->memory_peak_real = round(memory_get_peak_usage(true) / 1024 / 1024, 3)." Mb";
            $ooo->debug_performance->elapsed_time = round(microtime(true) - $GLOBALS['T0'] , 3);
        }
        
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        header("Content-type: application/json");
        http_response_code($response_http_code);
        
        print ($json_encode) ? json_encode($ooo):$ooo;
        
        exit;
    }
    
    
    static public function json_output_error($error_type=null, $error_message=''){
        
        $o = (object) [
            'error' => true,
            'errorType' => $error_type,
            'errorMsg' => $error_message,
        ];
        
        header("X-".PROJ_ABBR."-Auth: ". intval(User_Session::is_logged()));
        
        print json_encode($o);
    }
    
}