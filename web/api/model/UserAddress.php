<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace web\api\model;

/**
 * Description of UserAddress
 *
 * @author shilinqing
 */
class UserAddress extends \web\common\model\BaseModel{
    
    protected function _initialize() {
        $this->tableName = 'user_address';
    }
    
    public function getUserAddr($user_id){
        $where['user_id'] = $user_id;
        return $this->where($where)->field('id,name,address,remark,update_time')->select();
    }
    
}
