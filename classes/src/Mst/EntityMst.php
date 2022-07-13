<?php

namespace Mst;

class EntityMst extends \Entity {
    
    protected function mandatory_fields($data, $man_fields) {
        
        foreach($man_fields as $ff) {
            
            if(!isset($data[$ff]) || empty($data[$ff])) {
                
                throw new ApiException('Mandatory field(s) not present', 
                        \Enum::MANDATORY_FIELDS,
                        ['data'=>$data, 'mandatory'=>$man_fields]);
            }
        }
        
        return true;
    }
    
    
}