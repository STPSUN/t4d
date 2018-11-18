<?php

namespace addons\financing\model;

class Market extends \web\common\model\BaseModel{
    
    protected function _initialize() {
        $this->tableName = 'market';
    }
    
    /**
     * 根据币种名称获取数据
     * @param type $coin_name
     */
    public function getDetailByCoinName($coin_name='ETH',$field = ''){
        $where['coin_name'] = $coin_name;
        if($field != '')
            $this->field($field);
        $data = $this->where($where)->find();
        if(!empty($data) && $field != ''){
            return $data[$field];
        }else{
            return $data;
        }
    }
    
    
}

