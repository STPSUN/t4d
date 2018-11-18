<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace web\api\model;

/**
 * Description of UserProduct
 *
 * @author shilinqing
 */
class UserProduct extends \web\common\model\BaseModel{
    //put your code here
    protected function _initialize() {
        $this->tableName = 'user_product';
    }
    
    /**
     * 用户组合
     * @param type $user_id
     * @return type
     */
    public function getListByUserID($user_id){
        $m = new \addons\config\model\Coins();
        $planM = new \addons\financing\model\Product();
        $sql = 'select a.add_time,a.release_time ,a.is_safe,a.safe_amount,a.amount,b.coin_name,(a.amount * c.rate * c.duration / 100) as reward_money';
        $sql .= ' from '.$this->getTableName().' a inner join '.$m->getTableName().' b on a.coin_id=b.id';
        $sql .= ' inner join '.$planM->getTableName().' c on a.plan_id=c.id where a.user_id='.$user_id;
        return $this->query($sql);
        
    }
    
}
