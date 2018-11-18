<?php

namespace addons\eth\user\controller;

class Trade extends \web\user\controller\AddonUserBase{


    public function index(){
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
        $type = $this->_get('type');
        $filter = 'status='.$status;
        if($type != ''){
            $filter .= ' and type='.$type;
        }
        if ($keyword != null) {
            $filter .= ' and b.username like \'%' . $keyword . '%\'';
        }
        $m = new \addons\eth\model\EthTradingOrder();
        $total = $m->getTotal($filter);
        $rows = $m->getList($this->getPageIndex(), $this->getPageSize(), $filter);
        $count_total = $m->getCountTotal($filter);
        return $this->toTotalDataGrid($total, $rows,$count_total);
    }

    /**
     * 审核
     */
    public function appr(){
        if(IS_POST){
            $id = $this->_post('id');
            try{
                $tradeM = new \addons\eth\model\EthTradingOrder();
                $data = $tradeM->getUncheckDataByID($id);
                if(!$data){
                    return $this->failData("订单数据异常");
                }
                //初始化参数 eth api
                $msg = '';
                $ethApi = $this->_initArguments($msg);
                if($ethApi == false){
                    return $this->failData($msg);
                }
                $id = $data['id'];
                $to = $data['to_address'];
                $contract_address = $data['contract_address'];
                $byte = $data['byte'];
                if($data['coin_id'] !=1  && empty($contract_address))
                    return $this->failData ('未设置合约地址');
                $frex_to = strtolower(substr($to,0,2));
                if(($frex_to !== "0x" || strlen($to) !== 42)){
                    //异常订单处理 更新订单状态非未通过
                    $tradeM->updateStatus($id,5,NOW_DATETIME,'','转出地址格式错误');
                }
//                $ret = $ethApi->send($to, $data['amount'], $contract_address, $byte);
//                if($ret['success']){

                    $tradeM->startTrans();
                    //更新订单txhash
                    $has_update = $tradeM->updateStatus($id, 1, NOW_DATETIME, '', '审核成功，等待打款');
                    if(empty($has_update)){
                        return $this->failData('更新订单失败');
                    }
                    $tradeM->commit();
                    //确认订单完成时扣除冻结金额
                    return $this->successData('id:'.$id.' 转出成功。');
//                }else{
//                            //异常订单
//                    $tradeM->updateStatus($id, 5, NOW_DATETIME, '', $ret['message']);
//                    return $this->failData($ret['message']);
//                }
            } catch (\Exception $ex) {
                return $this->failData($ex->getMessage());
            }
        }
    }


    /**
     * 反审核-不通过
     */
    public function cancel_appr(){
        if(IS_POST){
            $id = $this->_post('id');
            try{
                $tradeM = new \addons\eth\model\EthTradingOrder();
                $data = $tradeM->getDetail($id);
                if(!empty($data)){
                    $user_id = $data['user_id'];
                    $amount = $data['amount'];
                    $coin_id = $data['coin_id'];
                    $tax = $data['tax'];
                    $tradeM->startTrans();
                    $ret = $tradeM->updateStatus($id, 2, NOW_DATETIME, '', '转出审核不通过');
                    if($ret > 0){
                        if($data['tax'] > 0) $amount += $data['tax'];
                        //返还金额
                        $balanceM = new \addons\member\model\Balance();
                        $balance = $balanceM->updateBalance($user_id, $amount, $coin_id, true);
                        if(!$balance){
                            $tradeM->rollback();
                            return $this->failData('退单失败');
                        }
                        $type = 9;
                        $before_amount = $balance['before_amount'];
                        $after_amount = $balance['amount'];
                        $change_type = 1; //增加
                        $remark = '转出可用余额';

                        $recordM = new \addons\member\model\TradingRecord();
                        $r_id = $recordM->addRecord($user_id, $coin_id, $amount, $before_amount, $after_amount, $type, $change_type, $user_id, '', '', $remark);
                        if(!$r_id ){
                            $tradeM->rollback();
                            return $this->failJSON('提交申请失败');
                        }
                        $tradeM->commit();
                        return $this->successData('退还金额成功');
                    }else{
                        $tradeM->rollback();
                        return $this->failData('更新订单状态失败');
                    }
                }
            } catch (\Exception $ex) {
                return $this->failData($ex->getMessage());
            }
        }
    }



}


