<?php

namespace addons\otc\model;

/**
 * @author shilinqing
 * otc收款信息
 */
class PayConfig extends \web\common\model\BaseModel{

    protected function _initialize() {
        $this->tableName = 'member_pay_config';
    }

    public function getUserPayDetail($user_id,$type=1){
        $where['user_id'] = $user_id;
        $where['type'] = $type;
        return $this->where($where)->find();
    }

    public function getDetailForJSON($user_id,$type=1){
        $where['user_id'] = $user_id;
        $where['type'] = $type;
        if($type == 3){ //银行卡
            $fields = 'account,name,bank_address';
        }else{
            $fields = 'account';
        }
        $data = $this->where($where)->field($fields)->find();
        return $data;
    }

    public function getUserPay($user_id){
        $where['user_id'] = $user_id;
        $fields = '*';
        $data = $this->where($where)->field($fields)->select();
        return $data;
    }

}
