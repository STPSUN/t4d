<?php

namespace addons\config\model;

/**
 * Description of RegisterConf
 * 注册所需字段配置
 * @author shilinqing
 */
class RegisterConf extends \web\common\model\BaseModel{
    
    protected function _initialize(){
        $this->tableName = 'sys_register_conf';
    }
    
    
}
