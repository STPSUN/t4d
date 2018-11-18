<?php

namespace addons\config\model;

class Coins extends \web\common\model\BaseModel{
    
    protected function _initialize(){
        $this->tableName = 'coins';
    }
    
    public function getCoinName($is_token =''){
        if($is_token !=''){
            $this->where('is_token='.$is_token);
        }
        return $this->field('id,coin_name')->select(); 
    }
    
    public function getCoinByName($name='eth',$fields = 'id'){
        $where['coin_name'] = $name;
        return $this->where($where)->field($fields)->find();
    }
    
    public function getCoinField($id,$field='without_rate'){
        $where['id'] = $id;
        $data = $this->where($where)->field($field)->find();
        if(!empty($data)){
            return '';
        }else{
            return $data[$field];
        }
        
    }
    
    
   
}
