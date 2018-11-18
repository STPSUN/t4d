<?php

namespace addons\config\model;

/**
 * Description of Sms
 * 短信运营商配置
 * @author shilinqing
 */
class Sms extends \web\common\model\BaseModel {
    
    protected function _initialize() {
        $this->tableName = 'sys_sms';
    }
    
    /**
     * 获取已开启配置
     */
    public function getAllowConfig($id =''){
        $where['is_allow'] = 1;
        if($id !='')
            $where['id'] = array('<>',$id);
        return $this->where($where)->find();
    }
    
}
