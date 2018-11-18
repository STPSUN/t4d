<?php

namespace addons\otc\user\controller;
/**
 * Description of Order
 *
 * @author shilinqing
 */
class Order extends \web\user\controller\AddonUserBase{
    
    public function index(){
//        -1=撤单，0=未成交，2=已匹配（委托买单，卖方下单需填写收款地址），3=待确认 ，4=已完成
        $status = $this->_get('status');
        if($status == ''){
            $status = 0; //未确认
        }
        $this->assign('status',$status);
        return $this->fetch();
    }
    
    public function loadList(){
        $keyword = $this->_get('keyword');
        $status = $this->_get('status');
        $filter = 'status='.$status;
        if ($keyword != null) {
            $filter .= ' and b.phone like \'%' . $keyword . '%\'';
        }
        $m = new \addons\otc\model\OtcOrder();
        $total = $m->getTotal($filter);
        $rows = $m->getList($this->getPageIndex(), $this->getPageSize(), $filter);
        return $this->toDataGrid($total, $rows);
    }
    
    public function detail(){
        $this->assign('id',$this->_get('id'));
        $this->setLoadDataAction('loadData');
        return $this->fetch();
    }
    
    public function loadData(){
        $id = $this->_get('id');
        $m = new \addons\otc\model\OtcOrder();
        $data = $m->getOrderDetail($id);
        return $data;
    }
    
    public function cancel(){
        $order_id = $this->_post('id');
        $m = new \addons\otc\model\OtcOrder();
        $order = $m->getOrderByStatus($order_id,3);
        $user_id = $order['buy_user_id'];
        if(empty($order)){
            return $this->failData('订单不存在');
        }
        try{
            $m->startTrans();
            $pic = $order['pic']; //图片地址
            $order['buy_user_id'] = 0;
            $order['pay_type'] = 0;
            $order['status'] = 0;
            $order['pic'] = '';
            $order['pay_detail_json'] = '';
            $res = $m->save($order);
            if($res > 0){
                if($order['type'] == 1){
                    //买单 退还冻结金额 
                    $coin_id = $order['coin_id'];
                    $total_amount = $order['total_amount'];
                    $balanceM = new \addons\member\model\Balance();
                    $balance = $balanceM->getBalanceByCoinID($user_id, $coin_id);
                    if(empty($balance)){
                        $m->rollback ();
                        return $this->failData ('指定余额不存在');
                    }
                    $before_amount = $balance['amount'];
                    $balance['before_amount'] = $before_amount;
                    $balance['amount'] = $before_amount + $total_amount;
                    $balance['otc_frozen_amount'] = $balance['otc_frozen_amount'] - $total_amount;
                    $balance['update_time'] = NOW_DATETIME;
                    $is_save = $balanceM->save($balance);
                    if(empty($is_save)){
                        $m->rollback ();
                        return $this->failData('更新余额失败');
                    }
                }
                $m->commit();
                $pic = $_SERVER["DOCUMENT_ROOT"].$pic; //删除无效图片
                unlink($pic);
                return $this->successData();
            }else{
                $m->rollback();
                return $this->failData('取消交易失败');
            } 
            
        } catch (\Exception $ex) {
            return $this->failData($ex->getMessage());
        }
        
    }
    
    public function confirm(){
        $order_id = $this->_post('id');
        $m = new \addons\otc\model\OtcOrder();
        $order = $m->getOrderByStatus($order_id,3);
        if(empty($order)){
            return $this->failData('订单不存在');
        }
        try{
            $balanceM = new \addons\member\model\Balance();
            $ret = $balanceM->otcTradingConfirm($order_id);
            if($ret){
                return $this->successData();
            }else{
                return $this->failData('确认订单失败');
            }
            
        } catch (\Exception $ex) {
            return $this->failData($ex->getMessage());
        }
        
    }
    
}
