<?php

namespace addons\config\model;

/**
 * Description of Role
 *
 * @author shilinqing
 */
class Role extends \web\common\model\BaseModel {
    
    protected function _initialize() {
        $this->tableName = 'user_role';
    }
    
    /**
     * @param type $id
     */
    public function getDataByID($id, $fields='addons_id'){
        $where['id'] = $id;
        if(!empty($fields))
            $this->field($fields);
        $data = $this->where($where)->find();
        if(!empty($data)){
            return $data[$fields];
        }else{
            return '';
        }
    }
    
    
}
