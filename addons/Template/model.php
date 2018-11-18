<?php

namespace addons\config\model;

class Notice extends \web\common\model\BaseModel{
    
    protected function _initialize(){
        $this->tableName = 'sys_notice';
    }
    
   
}
