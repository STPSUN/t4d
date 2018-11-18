<?php

namespace addons\fomo\model;

/**
 * @author shilinqing
 */
class Conf extends \web\common\model\BaseModel{
    
    protected function _initialize() {
        $this->tableName = 'fomo_conf';
    }
    
    public function getValByName($name){
        $where['field'] = $name;
        $data = $this->where($where)->field('parameter_val')->find();
        if(!empty($data)){
            return $data['parameter_val'];
        }else{
            return '';
        }
    }
    
    public function getDataByName($name){
        $where['field'] = $name;
        return $this->where($where)->field('id,parameter_val')->find();
    }
    
    public function getDataByControlType($type="textarea",$fields = 'field,parameter_val'){
        $where['control_type'] = $type;
        return $this->where($where)->field($fields)->select();
    }
    
    
}
