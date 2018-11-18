<?php

namespace addons\config\model;

/**
 * Description of Help
 *
 * @author shilinqing
 */
class Help extends \web\common\model\BaseModel {
    
    protected function _initialize() {
        $this->tableName = 'sys_help';
    }
    
}