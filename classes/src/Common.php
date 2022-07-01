<?php

class Common {
    
    public static function tk_last($string, $sep='/') {
        return substr($string, strrpos($string, $sep) + 1);
    }
    
    public static function class_page_active($nome) {
        return (self::tk_last($_SERVER['REQUEST_URI']) == $nome) ? 'active' : '';
    }
    
    
    // Function to get the user IP address
    public static function getUserIP() {
        $ipaddress = '';
        if (isset($_SERVER['HTTP_CLIENT_IP']))
            $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
        else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
            $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        else if(isset($_SERVER['HTTP_X_FORWARDED']))
            $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
        else if(isset($_SERVER['HTTP_X_CLUSTER_CLIENT_IP']))
            $ipaddress = $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'];
        else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
            $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
        else if(isset($_SERVER['HTTP_FORWARDED']))
            $ipaddress = $_SERVER['HTTP_FORWARDED'];
        else if(isset($_SERVER['REMOTE_ADDR']))
            $ipaddress = $_SERVER['REMOTE_ADDR'];
        else
            $ipaddress = 'UNKNOWN';
        return $ipaddress;
    }
    
    public static function send_404() {
        
        ob_clean();
        http_response_code(404);
        
        /*
        $View = new View_Public();
        $View->_set('title',  'Errore 404 - '. PROJ_NAME);
        $View->_set('body', $View->render('special/404'));
        print $View->render('design/html');
        */
        exit;
    }
    
    public static function send_500($errorn) {
        
        ob_clean();
        http_response_code(500);
        /*
        $View = new View_Public();
        $View->_set('title',  'Errore 500 - '. PROJ_NAME);
        $View->_set('body', $View->render('special/500', ['suberror'=>$errorn]));
        print $View->render('design/html');
        */
        exit;
    }
    
    public static function send_403() {
        
        ob_clean();
        http_response_code(403);
        
        /*
        $View = new View_Public();
        $View->_set('title',  'Errore 403 - '. PROJ_NAME);
        $View->_set('body', $View->render('special/403'));
        print $View->render('design/html');
        */
        exit;
    }
    
    public static function ob_started($set=false) {
        
        if(isset($GLOBALS['ob_started']) && $GLOBALS['ob_started']) {
            return true;
        }
        else{
            if($set) {
                $GLOBALS['ob_started'] = true;
            }
            return false;
        }
    }
    
}

