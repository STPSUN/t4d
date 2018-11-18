<?php

namespace addons\config\model;

/**
 * Description of ExchangeRate
 * 汇率设置
 * @author shilinqing
 */
class ExchangeRate extends \web\common\model\BaseModel {
    
    protected function _initialize() {
        $this->tableName = 'exchange_rate';
    }
    
    public function getID($key){
        $where['name'] = $key;
        $data = $this->where($where)->field('id')->find();
        if(!empty($data))
            return $data['id'];
        else
            return -1;
    }
    
    public function getRate($key){
        $where['name'] = $key;
        $data = $this->where($where)->field('rate')->find();
        if(!empty($data))
            return $data['rate'];
        else
            return '';
    }
    
    
}
