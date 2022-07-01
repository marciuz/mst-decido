<?php

class Controller_Private extends Controller {
    
    public static function load($file, $args=[]) {
        
        /*
        if(!User_Session::exists()){
            Common::send_403();
            return null;
        }
        */
        
        parent::load($file, $args);
    }
}