<?php

abstract class Controller {
    
    public static function load($pathf, $args=[]){
        
        try{
            if(file_exists($pathf) && is_readable($pathf)) {
                extract($args);
                require $pathf;
            }
            else{
                throw new RpcRuntimeException('Controller file missed', AppEnum::ERROR_CONTROLLER_NOT_EXISTS);
            }
        } 
        catch (RpcRuntimeException $ex) {
            $ex->setLog(\Monolog\Logger::ALERT);
            Common::send_error(AppEnum::ERROR_CONTROLLER_NOT_EXISTS);
        } 
        catch (EccException $e) {
            $e->setLog(\Monolog\Logger::ALERT);
        }
    }
}
