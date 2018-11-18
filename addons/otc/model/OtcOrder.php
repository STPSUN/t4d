<?php

namespace addons\otc\model;

/**
 * @author shilinqing
 * otc交易表
 * 订单状态:-1=撤单，0=未成交，2=已匹配（委托买单，卖方下单需填写收款地址），3=待确认 ，4=已完成
 */
class OtcOrder extends \web\common\model\BaseModel{
    
    protected function  _initialize(){
        $this->tableName = 'otc_order';
    }
    
    public function getOrderDetail($id){
        $m = new \addons\member\model\MemberAccountModel();
        $sql = 'select a.*,b.username,b.phone phone,c.phone buy_phone,c.username as buy_username from '.$this->getTableName().' a left join '.$m->getTableName().' b on a.user_id=b.id left join '.$m->getTableName().' c on a.buy_user_id=c.id where a.id='.$id.' limit 0,1';
        $data = $this->query($sql);
        if(!empty(($data))){
            return $data[0];
        }else{
            return '';
        }
    }
    
    public function getList($pageIndex = -1, $pageSize = -1, $filter = '', $fields = '', $order = 'id desc'){
        $m = new \addons\member\model\MemberAccountModel();
        $coinM = new \addons\config\model\Coins();
        $sql1 = 'select a.*,b.username ,c.username as buy_username,d.coin_name from '.$this->getTableName().' a left join '.$m->getTableName().' b on a.user_id=b.id left join '.$m->getTableName().' c on a.buy_user_id=c.id left join '.$coinM->getTableName().' d on a.coin_id=d.id';
        $sql = 'select ';
        if(!empty($fields)){
            $sql .= $fields;
        }else{
            $sql .= '*';
        }
        $sql .= ' from ('.$sql1.') as tab';
        if(!empty($filter)){
            $sql .= ' where '.$filter;
        }
        return $this->getDataListBySQL($sql, $pageIndex, $pageSize, $order);
    }
    
    /**
     * 设置付款凭证
     * @param type $id
     * @param type $user_id
     * @param type $proof_pic
     * @return type
     */
    public function setProofPic($id, $user_id, $type, $proof_pic){
        $where['id'] = $id;
        if($type == 1){
            $where['user_id'] = $user_id;
        }else{
            $where['buy_user_id'] = $user_id;
        }
        $where['status'] = 2;
        return $this->where($where)->update(array('pic' => $proof_pic,'status'=> 3));
    }
    
    
    
    /**
     * 添加otc订单
     * @param type $user_id
     * @param type $coin_id
     * @param type $type
     * @param type $amount
     * @param type $tax_amount
     * @param type $total_amount
     * @param type $price
     * @param type $pay_amount
     * @param type $pay_type
     * @param type $pay_detail_json
     * @param type $remark
     * @return type
     */
    public function addOrder($user_id,$coin_id,$type,$amount,$tax_amount,$total_amount,$price,$pay_amount,$pay_type=1,$pay_detail_json='',$remark=''){
        $data['user_id'] = $user_id;
        $data['coin_id'] = $coin_id;
        $data['type'] = $type;
        $data['pay_type'] = $pay_type;
        $data['pay_detail_json'] = $pay_detail_json;
        $data['amount'] = $amount;
        $data['tax_amount'] = $tax_amount;
        $data['total_amount'] = $total_amount;
        $data['price'] = $price;
        $data['pay_amount'] = $pay_amount;
        $data['remark'] = $remark;
        $data['add_time'] = NOW_DATETIME;
        $data['status'] = 0;
        return $this->add($data,false);
    }
    
    /**
     * 根据状态获取订单
     * @param type $order_id
     * @param type $user_id
     */
    public function getOrderByStatus($order_id,$status = 0){
        $where['id'] = $order_id;
        $where['status'] = $status;
        return $this->where($where)->find();
    }
    
    /**
     * 获取用户订单
     * @param type $order_id
     * @param type $user_id
     */
    public function getOrderWithUserID($order_id,$user_id){
        $where['id'] = $order_id;
        $where['user_id'] = $user_id;
        return $this->where($where)->find();
    }
    
    /**
     * 获取均价
     * @param type $begin_time
     * @param type $end_time
     * @param type $status
     * @return type
     */
    public function getTotalDataByTime($begin_time, $end_time,$status=''){
        $m = new \addons\config\model\Coins();
        $sql = 'select b.coin_name as coin_name, sum(a.amount) total_amount, sum(a.pay_amount) total_amount , (round(sum(a.pay_amount) / sum(a.amount),5)) avg_price from '.$this->getTableName().' as a ,'.$m->getTableName().' b';
        $sql .= ' where a.coin_id=b.id and a.add_time >=\''.$begin_time.'\' and a.add_time <=\''.$end_time.'\'';
        if($status != ''){
            $sql .= ' a.status='.$status;
        }
        $sql .= ' group by a.coin_id';
        return $this->query($sql);
    }
    
    /**
     * 获取过时订单
     */
    public function getOverTimeOrder($end_time, $status=2, $fileds= 'user_id,buy_user_id,coin_id,type,total_amount,deal_time'){
        $where['status'] = $status;
        $where['deal_time'] = array('>',$end_time);
        return $this->where($where)->field($fileds)->select();
        
    }
    
}
