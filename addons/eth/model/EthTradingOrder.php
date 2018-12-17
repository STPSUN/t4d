<?php

namespace addons\eth\model;

/**
 * 用户提取记录
 */
class EthTradingOrder extends \web\common\model\BaseModel {
    
    protected function _initialize() {
        $this->tableName = 'eth_trading_order';
    }
    
    
    /**
     * $id asset表 id
     * 是否存在提取中的提取记录
     */
    public function hasRecord($user_id, $id, $status = 0){
        $m = new \addons\member\model\AssetModel();
        $where['id'] = $id;
        $where['user_id'] = $user_id;
        $res = $m->where($where)->find();
        $coin_id = $res['coin_id'];
        $where1['coin_id'] = $coin_id;
        $where1['user_id'] = $user_id;
        $where1['status'] = $status;
        return $this->where($where1)->find();
    }
    
    /**
     * @param type $txid 交易hash串
     * @param type $status 交易状态
     */
    public function getDetailByTxHash($txhash, $status = ''){
        $where['txhash'] = $txhash;
        if($status != '')
            $where['status'] = $status;
        
        return $this->where($where)->field('id')->find();
    }

    /**
     * 外网转入代币
     * @param type $user_id
     * @param type $to_address
     * @param type $coin_id
     * @param type $amount
     * @param type $txhash
     * @param type $type
     * @param type $status
     * @param type $remark
     */
    public function transactionIn($user_id, $from_address, $to_address, $coin_id, $amount, $txhash, $block_number = 0, $tax = 0, $type=1, $status=1, $remark='用户外网转入',$eops_amount){
        $data['user_id'] = $user_id;
        $data['from_address'] = $from_address;
        $data['to_address'] = $to_address;
        $data['coin_id'] = $coin_id;
        $data['amount'] = $amount;
        $data['tax'] = $tax;
        $data['txhash'] = $txhash;
        $data['block_number'] = $block_number;
        $data['type'] = $type;
        $data['status'] = $status;
        $data['remark'] = $remark;
        $data['update_time'] = NOW_DATETIME;
        $data['eops_amount'] = $eops_amount;
        return $this->add($data);

    }
    
    
    /**
     * 获取未转出数据
     * @return type
     */
    public function getUncheckDataByID($id){
        $m = new \addons\config\model\Coins();
        $sql = 'select a.*,b.is_token,b.contract_address,b.byte from '.$this->getTableName().' a ,'.$m->getTableName().' b where a.id= '.$id.' and a.status=0 and a.txhash="" and a.coin_id=b.id limit 0,1';
        $data = $this->query($sql);
        if(!empty($data)){
            return $data[0];
        }else{
            return '';
        }
    }
    
    /**
     * 获取未确认完成数据
     * @return type
     */
    public function getUnCompliteData(){
        $where['type'] = 0;
        $where['status'] = 4;
        $where['txhash'] = array('<>' ,'');
        return $this->where($where)->select();
    }
    
    /**
     * 更新订单状态 默认未通过
     * @param type $id
     * @param type $status
     * @param type $txhash
     * @return type
     */
    public function updateStatus($id, $status, $update_time, $txhash='', $remark=''){
        $where['id'] = $id;
        $where['type'] = 0;
        $data['remark'] = $remark;
        $data['status'] = $status;
        $data['update_time'] = $update_time;
        if(!empty($txhash)){
            $data['txhash'] = $txhash;
        }
        return $this->where($where)->update($data);
    }

    /**
     * 订单转出成功 改变状态
     */
    public function compliteOrder($id, $status, $update_time, $cumulative_gas_used, $remark=''){
        $where['id'] = $id;
        $where['type'] = 0;
        $data['cumulative_gas_used'] = $cumulative_gas_used;
        $data['remark'] = $remark;
        $data['status'] = $status;
        $data['update_time'] = $update_time;
        return $this->where($where)->update($data);
    }

    public function getList($pageIndex = -1, $pageSize = -1, $filter = '', $order = 'id asc') {
        $m = new \addons\member\model\MemberAccountModel();
        $coinM = new \addons\config\model\Coins();
        $sql = 'select a.*,b.phone,b.username,c.coin_name,case when a.status=0 then "待审核" when a.status=1 then "已完成" when a.status=2 then "待转账" when a.status=3 then "转账中" when a.status=-1 then "未通过" when a.status=-2 then "订单异常" end as status_name, case when a.type=0 then "提现转出" when a.status=1 then "外网转入"  end as trade_type from ' . $this->getTableName() . ' a,'.$m->getTableName().' b,'.$coinM->getTableName().' c where a.user_id=b.id and a.coin_id=c.id';
        if (!empty($filter))
            $sql .=  ' and '.$filter;
        return $this->getDataListBySQL($sql, $pageIndex, $pageSize, $order);
    }

    public function getList2($pageIndex = -1, $pageSize = -1, $filter = '', $order = 'id asc') {
//        $m = new \addons\member\model\MemberAccountModel();
//        $coinM = new \addons\config\model\Coins();
//        $sql = 'select a.*,b.phone,b.username,c.coin_name,case when a.status=0 then "待审核" when a.status=1 then "已完成" when a.status=2 then "待转账" when a.status=3 then "转账中" when a.status=-1 then "未通过" when a.status=-2 then "订单异常" end as status_name, case when a.type=0 then "提现转出" when a.status=1 then "外网转入"  end as trade_type from ' . $this->getTableName() . ' a,'.$m->getTableName().' b,'.$coinM->getTableName().' c where a.user_id=b.id and a.coin_id=c.id';

        $sql = "SELECT o.*,m.username,m.phone,c.coin_name "
            . " FROM tp_eth_trading_order AS o "
            . " JOIN tp_member_account AS m ON m.id = o.user_id "
            . " JOIN tp_coins AS c ON o.coin_id = c.id "
            . " where 1=1 and ";

        if (!empty($filter))
            $sql .= $filter;
        return $this->getDataListBySQL($sql, $pageIndex, $pageSize, $order);
    }
    
    public function getTotal($filter = '') {
        $m = new \addons\member\model\MemberAccountModel();
        $coinM = new \addons\config\model\Coins();
        $sql = 'select a.* from ' . $this->getTableName() . ' a,'.$m->getTableName().' b,'.$coinM->getTableName().' c where a.user_id=b.id and a.coin_id=c.id';
        if($filter!=''){
            $sql = 'select count(*) as c from ('.$sql.') as tab where '.$filter;
        }
        $count = $this->query($sql);
        return $count[0]['c'];
    }

    public function getTotal2($filter = '')
    {
        $sql = "SELECT o.* "
            . " FROM tp_eth_trading_order AS o "
            . " JOIN tp_member_account AS m ON m.id = o.user_id "
            . " JOIN tp_coins AS c ON o.coin_id = c.id "
            . " where 1=1 and ";
        if($filter)
            $sql .= $filter;

//        print_r($sql);exit();
        $count = $this->query($sql);
        return count($count);
    }
    
    public function getCountTotal($filter = '') {
        $m = new \addons\member\model\MemberAccountModel();
        $coinM = new \addons\config\model\Coins();
        $sql = 'select a.* from ' . $this->getTableName() . ' a,'.$m->getTableName().' b,'.$coinM->getTableName().' c where a.user_id=b.id and a.coin_id=c.id';
        if($filter!=''){
            $sql = 'select sum(amount) as count_total from ('.$sql.') as tab where '.$filter;
        }
        print_r($sql);exit();
        $count = $this->query($sql);
        return $count[0]['count_total'];
    }

    public function getCountTotal2($filter = '') {
        $sql = "SELECT SUM(amount) AS count_total,SUM(eops_amount) AS eosp_amount "
            . " FROM tp_eth_trading_order o "
            . " LEFT JOIN tp_member_account b ON b.id = o.user_id "
            . " LEFT JOIN tp_coins c ON c.id = o.coin_id "
            . " WHERE 1=1 and";
        if($filter)
            $sql .= $filter;
        $count = $this->query($sql);
        return $count[0]['count_total'];
    }
    

    /*
     * 获取用户订单总额
     * @$user_id 用户id
     */
    public function getUserTOtal($user_id, $filter = ''){
        $where = ['user_id'=>$user_id];
        if(!empty($filter)){
            $this->where($filter);
        }
        return $this->where($where)->sum("amount");
    }
    /*
     * 获取转入记录 地址数据与密码
     */

    public function getRechargeByCoin($coin_id){
        $filter = array(
            'type' => 1,
            'status' => 1,
            'coin_id' => $coin_id
        );
        $list = $this
            ->where($filter)
            ->field("sum(a.amount) total_amount,b.address,b.eth_pass,a.user_id")
            ->alias("a")
            ->join("tp_member_account b","a.to_address = b.address","left")
            ->group('a.to_address')
            ->order("sum(a.amount)")
            ->select();
        return $list;
    }
    
}